<?php
if ( ! class_exists( 'WP_List_Table' ) ) {require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php'; }

require_once PRIMER_PATH . 'views/get_order_list.php';
require_once PRIMER_PATH. 'admin/includes/my_data_json.php';
require_once PRIMER_PATH . 'includes/vendor/dompdf/autoload.inc.php';


// reference the Dompdf namespace
use PrimerDompdf\Dompdf;

class PrimerReceipts extends WP_List_Table {


    /**
    * Overwrite the parent WP_List_Table __construct() method.
    * Calls parent function prepare_items(); to prepare the items for display.
    * ?add_action
    */
	function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Order', 'primer' ),
				'plural' => __( 'Orders', 'primer' ),
				'ajax' => true,
			)
		);

		$this->prepare_items();

		add_action( 'wp_print_scripts', [ __CLASS__, '_list_table_css' ] );
	}


    /**
    * Rendering table with orders to admin page.
    * Plus adding search box.
    *
    * @return void
    *
	*/
    public function display() {

		//	echo '<form id="wc-orders-filter" method="get" action="' . esc_url( get_admin_url( null, 'admin.php' ) ) . '">';
			$this->search_box( esc_html__( 'Search  order id', 'primer' ), 'filter_action' );
			parent::display();
		//	echo '</form> </div>';
	}

    /**
    * Define the columns names that are going to be used in the table and translate them.
    * Add check box with HTML input for selection of receipts.
    *
    * @return array with columns names.
    */
	function get_columns() {
		return array(
			'cb'		 	=> '<input type="checkbox" />',
			'order_id'		=> __( 'No.', 'primer' ),
			'order_date' 	=> __( 'Order Date', 'primer' ),
			'order_hour'	=> __( 'Hour', 'primer' ),
			'order_client'	=> __( 'Client', 'primer' ),
			'order_price'	=> __( 'Total Price', 'primer' ),
			'order_status'	=> __( 'Order Status', 'primer' ),
			'payment_status' => __( 'Payment Method', 'primer' ),
            'transmission_failure'	=> __( 'Transmission Status', 'primer'),
			'receipt_date'	=> __( 'Receipt date', 'primer' ),
            'receipt_status'	=> __( 'Receipt Log', 'primer' ),
            'credit_receipt'    =>__('Credit Receipt', 'primer'),
            'credit_log'        =>__('Credit Log', 'primer'),
			'receipt_id'	=> __( 'Receipt ID', 'primer' ),
			'accept_48'     => __('Accept 48','primer')
		);
	}

    /**
    * Gets a list of sortable columns.
    *
    * @return array with sortable columns.
    */
	function get_sortable_columns() {
		return array();
	}


    /**
    * Add the content to display in each column in the admin orders table.
    * Add an HTML link to the edit page of the order in WooCommerce. The links open in a new tab.
    * Secure HTML code from injection.
    * Adding URL view to HTML receipt ?receipt=view from $receipt_id by passing from $item ['receipt_id'];.
    * Checking transmission failure status by getting get_post_meta() from $item [order_id].
    *
    *
    * @param $item
    * @param $column_name
    * @return mixed|string|void
    */
	function column_default( $item, $column_name ) {

		if ($column_name !== 'receipt_date' && $column_name !== 'receipt_status' && $column_name !== 'receipt_id' && $column_name !== 'credit_receipt' && $column_name !== 'credit_log') {
			echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $item['order_id'] ) ) . '&action=edit' ) . '" target="_blank" class="order-view"><strong>' . esc_attr( $item[ $column_name ] ) . '</strong></a>';
		} else {
			if ($column_name == 'receipt_status') {
				if (!empty($item[ $column_name ])) {
                    $allowed_html = array(
                            'a' => array(
                                    'href' => array(),
                                    'title' => array(),
                                    'target' => array()
                                    ),
                                    'strong' => array(
                                            'class' => array(),
                                            )
                                            );
                    $escpe = '<a href="' . $item[ $column_name ] . '" target="_blank" class="order-view"><strong>' . __('Log', 'primer') . '</strong></a>';
                    echo wp_kses($escpe,$allowed_html);
				} else {
					echo '';
				}
			} elseif ($column_name == 'receipt_date') {
				$receipt_id = $item['receipt_id'];
				if (!empty($receipt_id)) {
					$new_url = '';
					$new_url = add_query_arg(array('receipt' => 'view'), rtrim(get_permalink($receipt_id), '/'));
                     $transmission_failure_order = get_post_meta($item['order_id'],'order_date_failed',true);
                     $transmission_failure_check = get_post_meta($item['order_id'],'transmission_failure_check',true);
                     $failed_credit_receipt_to_issue =  get_post_meta($item['order_id'],'credit_receipt_failed_to_issue',true);
                     $receiptId = get_post_meta($item['order_id'],'order_id_from_receipt',true);
                     $verify_success_receipt = get_post_meta($receiptId,'response_invoice_mark',true);

                     if((!empty($transmission_failure_order) && $transmission_failure_check == 2) || empty($verify_success_receipt)){
                         $new_url = add_query_arg(array('receipt' => 'view'), rtrim(get_permalink($receipt_id), '/').'-failed');
                         if(empty($item['credit_receipt_id'])){
                             $new_url = add_query_arg(array('receipt' => 'view'), rtrim(get_permalink($receipt_id), '/'));
                         }
                     }

                     if($failed_credit_receipt_to_issue == 'yes'){
                          if(!empty($transmission_failure_order)){
                         $new_url = add_query_arg(array('receipt' => 'view'), rtrim(get_permalink($receipt_id), '/').'-failed');
                         }
                          if($transmission_failure_check == 2 || !empty($verify_success_receipt)){
                          $new_url = add_query_arg(array('receipt' => 'view'), rtrim(get_permalink($receipt_id), '/'));
                          $new_url = preg_replace('/-failed/', '', $new_url);
                          }
                     }
                     $new_url = preg_replace('/credit-/', '', $new_url);

					echo '<a href="' . esc_url($new_url) . '" target="_blank" class="order-view"><strong>' . esc_attr( $item[ $column_name ] ) . '</strong></a>';
				} else {
					return $item[ $column_name ];
				}
			} elseif ($column_name == 'credit_receipt') {
                   $receipt_id = $item['credit_receipt_id'];
				if (!empty($receipt_id)) {
					$new_url = '';
					$find_invoice_in_slug = '';

                    $credit_log = get_post_meta($item['order_id'],'credit_log_id_for_order',true);
                    	$receipt_status_from_meta_url_prefix = admin_url('admin.php?page=primer_receipts_logs');
							$receipt_status_from_meta_url = $receipt_status_from_meta_url_prefix . '&order_log=' . $credit_log;

					$new_url = add_query_arg(array('receipt' => 'view'), rtrim(get_permalink($receipt_id), '/'));
					echo '<a href="' . esc_url($new_url) . '" target="_blank" class="order-view"><strong>' . esc_attr( $item[ 'credit_receipt_date' ] ) . '</strong></a>';
				} else {
					return '';
				}
			}elseif($column_name == 'credit_log'){
                 $receipt_id = $item['credit_receipt_id'];
				if (!empty($receipt_id)) {
                    $credit_log = get_post_meta($item['order_id'],'credit_log_id_for_order',true);
                    	$receipt_status_from_meta_url_prefix = admin_url('admin.php?page=primer_receipts_logs');
							$receipt_status_from_meta_url = $receipt_status_from_meta_url_prefix . '&order_log=' . $credit_log;
                    echo '<a href="' . esc_url($receipt_status_from_meta_url) . '" target="_blank" class="order-view"><strong> Log </strong></a>';
				} else {
					 return '';
				}
			} else {
				return $item[ $column_name ];
			}
		}
	}


	/**
	 * Array contains slug columns to hide them.
	 *
     * @var array $hidden_columns
	 */
	private $hidden_columns = array(
		'receipt_id', 'accept_48'
	);


    /**
    * Columns Check box.
    *
    * @param $item
    * @return string|void
    */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="orders[]" id="order_'.$item['order_id'].'" value="%s" />',
			$item['order_id']
		);
	}


    /**
    * Getting bulk actions from parent class.
    *
    * @return array with available bulk actions.
    */
	protected function get_bulk_actions() {
		return array();
	}


    /**
    * Calling parent class function extra_tablenav($which) (protected).
    * Creating extra menu in the admin orders table.
    * Adding filters to select order by date range, order status, client.
    *
    * @param $which
    * @return void
    */
	function extra_tablenav( $which ) {
		if ( $which !== 'bottom' ) {
        //
		$primer_orders = new PrimerOrderList();
        // Getting user current page.
        $current_page = $this->get_pagenum();
        // Getting users information by calling get_users_from_orders function from get_order_list class.
		$primer_orders_customers = $primer_orders->get_users_from_orders($current_page);
        // Empty array for unique customers.
		$unique_customers = [];
            // Looping through users array from get_users_from_orders in current page.
			foreach ( $primer_orders_customers as $primer_orders_customer ) {
				$hash = $primer_orders_customer['order_client_id'];
				$unique_customers[$hash] = $primer_orders_customer;
			}
            // Removing keys values from array and storing in $order_customers.
			$order_customers = array_values($unique_customers);
		?>
        <!-- Creating left top menu with filters. -->
		<div class="alignleft actions">
			<h2><?php _e('Filters', 'primer'); ?></h2>
			<h3><?php _e('Date Range:', 'primer'); ?></h3>
			<div class="filter_blocks_wrapper">
				<div class="left_wrap">
					<div class="filter_block">
					<!-- Years Range -->
					<label for="primer_order_year" style="float: left;"><?php _e('Year: ', 'primer'); ?></label>
						<select name="primer_order_year" id="primer_order_year">
							<?php
							// Getting current year and setting it to $current_year.
							$current_year = date('Y');
                            // Getting order current year.
							$primer_order_year = isset($_GET['primer_order_year']) ? sanitize_text_field($_GET['primer_order_year']) : $current_year;
                            // Years range
                            $year_from = 2021;
							$year_to = date('Y');
							$range_years = range($year_from, $year_to);
                            // Loop through years and adding options to select menu.
							foreach ( $range_years as $range_year ) { ?>
								<option value="<?php echo $range_year; ?>" <?php selected($range_year, $primer_order_year); ?>><?php echo $range_year; ?></option>
							<?php }
							?>
						</select>
				</div>
				    <!-- Order From - Order To -->
					<div class="filter_block">
						<label for="order_date_from">
							<?php _e('From: ', 'primer'); ?></label>
							<input type="text" id="order_date_from" name="order_date_from" placeholder="Date From" value="" />
						<label for="order_date_to">
							<?php _e('To: ', 'primer'); ?></label>
							<input type="text" id="order_date_to" name="order_date_to" placeholder="Date To" value="" />
					</div>
					<div class="filter_block">
						<label for="primer_order_client" style="float: left;"><?php _e('Client: ', 'primer'); ?></label>
							<select name="primer_order_client" id="primer_order_client" data-placeholder="<?php _e('Select clients', 'primer'); ?>">
								<option value=""></option>
								<?php
								$get_customer = isset($_GET['primer_order_client']) ? sanitize_text_field($_GET['primer_order_client']) : '';
								foreach ( $order_customers as $primer_orders_customer => $order_customer ) {
									if ($order_customer['order_client_id']) { ?>
										<option value="<?php echo $order_customer['order_client_id']; ?>" <?php selected($get_customer, $order_customer['order_client_id']); ?>><?php echo $order_customer['order_client']; ?></option>
									<?php } else { ?>
										<option value="<?php echo $order_customer['order_client_id']; ?>" <?php selected($get_customer, $order_customer['order_client_id']); ?>><?php _e('Guest client', 'primer'); ?></option>
									<?php }
								} ?>
							</select>
					</div>
				</div>
				<!-- Order Status select options -->
				<div class="right_wrap">
					<div class="filter_block">
						<label for="primer_order_status" style="float: left;"><?php _e('Order Status: ', 'primer'); ?></label>
							<select name="primer_order_status" title="<?php _e('Select order status', 'primer'); ?>" id="primer_order_status">
                                 <option selected disabled>Select order status</option>
								<?php
								$status_of_orders = wc_get_order_statuses();
								$get_order_status = isset($_GET['primer_order_status']) ? sanitize_text_field($_GET['primer_order_status']) : '';

								foreach ( $status_of_orders as $status_k => $status_value ) { ?>
									<option value="<?php echo $status_k; ?>" <?php
										 selected($status_k, $get_order_status);?>
										 ><?php echo $status_value; ?></option>
								<?php }
								?>
							</select>
					</div>
                     <!-- receipt Status select options -->
					<div class="filter_block">
						<label for="primer_receipt_status" style="float: left;"><?php _e('Receipt Status: ', 'primer'); ?></label>
							<select name="primer_receipt_status" id="primer_receipt_status">
							<?php
							$get_status = isset($_GET['primer_receipt_status']) ? sanitize_text_field($_GET['primer_receipt_status']) : '';
							$status_of_receipts = array(
									'' => 'All',
									'issued' => 'Issued',
									'not_issued' => 'Not Issued',
								);

							foreach ( $status_of_receipts as $status_k => $status_value ) { ?>
									<option value="<?php echo $status_k; ?>" <?php selected($status_k, $get_status); ?>><?php echo $status_value; ?></option>
								<?php }
							?>
							</select>
					</div>
                    <!-- Apply Button -->
					<div class="apply_btn btn_order"><input type="submit" class="button" id="filter_action" name="filter_action" value="<?php _e('Apply filter', 'primer'); ?>" /></div>
				</div>
			</div>

		<?php
		$primer_licenses = get_option('primer_licenses');
		if (!(in_array(3, $primer_licenses['wpModules']))) {
			echo '<h4><i>'.__('Orders for invoices are not shown because license key does not support them', 'primer').'</i></h4>';
		}
		if (!(in_array(4, $primer_licenses['wpModules']))) {
			echo '<h4><i>'.__('Orders for invoices-receipts within E.U or out E.U. are not shown because license key does not support them', 'primer').'</i></h4>';
		}
		 ?>
		</div>
        <!-- Loading spinner when order receipts execution -->
		<div class="loadingio-spinner-spinner-chyosfc7wi6" id="mySpinner"><div class="ldio-drsjmtezgls"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>

		<?php
		 $min_order_date = time();
         $max_order_date = time();
		 $formatted_min_order_date = date('m/d/Y', $min_order_date);
		 $formatted_max_order_date = date('m/d/Y', $max_order_date);
		 ?>
		<!-- JavaScript !-->
		<script>
			jQuery(document).ready(function ($) {

			  

			    $('#primer_order_client').selectWoo({
					allowClear:  true,
					placeholder: $( this ).data( 'placeholder' )
				});

			    var select_year = $('select[name="primer_order_year"]').val();

                var max_year = 2035;
                var diff_year = 0;
                var min_order_date = "<?php echo $formatted_min_order_date; ?>";
                var max_order_date = "<?php echo $formatted_max_order_date; ?>";


                var date_from = $('input[name="order_date_from"]'),
                    date_to = $('input[name="order_date_to"]');
                $('input[name="order_date_from"], input[name="order_date_to"]').datepicker({
                    changeMonth: true,
                    changeYear: true,
                    dateFormat: "yy-mm-dd",
                    yearRange: "2021:2035"
                });



                <?php if (isset($_GET['order_date_from']) && !empty($_GET['order_date_from'])) { ?>
                    $('input[name="order_date_from"]').datepicker("setDate", new Date("<?php esc_html_e($_GET['order_date_from'], 'primer'); ?>"));
					<?php } else { ?>
                	// $('input[name="order_date_from"]').datepicker("option", "minDate", new Date(min_order_date));
					<?php } ?>

					<?php if (isset($_GET['order_date_to']) && !empty($_GET['order_date_to'])) { ?>
                    $('input[name="order_date_to"]').datepicker("setDate", new Date("<?php esc_html_e($_GET['order_date_to'], 'primer'); ?>"));
	                <?php } else { ?>
						// $('input[name="order_date_to"]').datepicker("option", "minDate", new Date(max_order_date));
	                <?php } ?>


                $('select[name="primer_order_year"]').on('change', function () {
                    select_year = $(this).val();
                    $('input[name="order_date_from"], input[name="order_date_to"]').datepicker("destroy");
                    $('input[name="order_date_from"], input[name="order_date_to"]').datepicker({
                        changeMonth: true,
                        changeYear: true,
                        dateFormat: "yy-mm-dd",
                        yearRange: `${select_year}:${max_year}`,
                    });
                    var currentDate = new Date();
                    var currentDay = currentDate.getDate();
                    var currentMonth = currentDate.getMonth()+1;
                    var set_current_date = currentMonth + '/' + currentDay + '/' + select_year;
                    $('input[name="order_date_from"]').datepicker("setDate", new Date(set_current_date));
                    $('input[name="order_date_to"]').datepicker( 'option', 'minDate', date_from.val() );
                });

                // the rest part of the script prevents from choosing incorrect date interval
                date_from.on( 'change', function() {
                    date_to.datepicker( 'option', 'minDate', date_from.val() );
                });

                date_to.on('change', function () {
                    date_to.datepicker('option', 'maxDate', date_to.val());
				});

                var atLeastOneIsChecked = $('input[name="orders[]"]:checked').length > 0;
                if (atLeastOneIsChecked) {
                    $('.convert_orders input[type="submit"]').removeAttr('disabled');
                }
                function checker() {
                    var length_inputs = $('input[name="orders[]"]').length;
                    var trues = new Array();
                    $('input[name="orders[]"]').each(function (i, el) {

                        if ($(el).prop('checked') == true || $(el).is(':checked') == true) {
                            $('.convert_orders input[type="submit"]').removeAttr('disabled');
                            trues.push($(el));
                        }
                    })
                    if (trues.length <= 0) {
                        $('.convert_orders input[type="submit"]').attr('disabled', true);
                    }
                }

                $('.wp-list-table #cb input:checkbox').on('click', function () {
                    checker();
                    if ($(this).is(':checked')) {
                        $('.convert_orders input[type="submit"]').removeAttr('disabled');
                    } else {
                        $('.convert_orders input[type="submit"]').attr('disabled', true);
                    }
                });
                $('.wp-list-table input[name="orders[]"]').on('click', function () {
                    checker();
                });

                $('#filter_action').on('click', function() {

                  $('#current-page-selector').val('1');


                });



			});
		</script>
	<?php
		}
	}


    /**
    * Prepare items for the table.
    * Set the number of items per page (20).
    * Set the columns, hidden columns, and sortable columns for the table.
    * Determine the total number of items.
    * Set the pagination information for the table.
    *
    * @return void
    */
	function prepare_items() {
        // Set Items Per Page.
		$per_page = 20;
        // Getting from WP_List_Table pages.
        $current_page = $this->get_pagenum();
		$get_total_orders = new PrimerOrderList();
        $isGuestClient = false;
        if (isset($_GET['primer_order_client']) && $_GET['primer_order_client'] == 0) {
            $isGuestClient = true;
        }

		if ((isset($_GET['primer_order_status'])) || (isset($_GET['primer_order_client']) && (!empty($_GET['primer_order_client'])) || $isGuestClient ) || (isset($_GET['order_date_from']) && !empty($_GET['order_date_from'])) || (isset($_GET['order_date_to']) && !empty($_GET['order_date_to'])) || (isset($_GET['primer_receipt_status']) && !empty($_GET['primer_receipt_status']) ) || (isset($_GET['s']) && !empty($_GET['s']) )) {
            $order_status = isset($_REQUEST['primer_order_status']) ? sanitize_text_field($_REQUEST['primer_order_status']) : '';
            // get_with_params function from get_order_list.php
            $get_orders_list = $get_total_orders->get_with_params($current_page,$_REQUEST['order_date_from'], $_REQUEST['order_date_to'], $_GET['primer_order_client'], $order_status, $_GET['primer_receipt_status']);
            $total_orders = $get_orders_list['total_orders'];
            $get_orders_list = $get_orders_list['orders'];
        } else {
            $get_orders_list = $get_total_orders->get($current_page);
        }
		$columns  = $this->get_columns();
		$hidden   = $this->hidden_columns;
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $get_orders_list;
		$data = $this->items;
		/**
		 * Get current page calling get_pagenum method
		 */
        if (isset($total_orders) ) {
            $total_items = $total_orders;
        }
        else {
            $total_items = array_sum( (array) wp_count_posts( 'shop_order' ) );
        }
        $current_page = $this->get_pagenum();
        $this->items = $data;
		/**
		 * Call parent WP_List_Table -> set_pagination_args method for informations about
		 * total items, items for page, total pages and ordering.
		 */
		$this->set_pagination_args(
			array(
				'total_items'	=> $total_items,
				'per_page'	    => $per_page,
				'total_pages'	=> ceil( $total_items / $per_page ),
			)
		);
	}


    /**
    * Display "No orders found." and translate it to user set language.
    *
    * @return void
    */
	function no_items() {
		_e( 'No orders found.', 'primer' );
	}


    /**
	 * Detects when a bulk action is being triggered and performs the action.
	 *
	 * This function checks if a bulk action is being triggered by checking the value of $_REQUEST['wp_ajax_list_order'].
	 * If the value is set, it sanitizes the array of orders.
	 * If the current action is not empty, it checks if the orders array is empty.
	 * If the orders array is empty, it displays an error message and returns.
	 * If the current action is empty, it returns without performing any action.
	 *
	 * @return void
	 */
	function process_bulk_action() {
		// Detect when a bulk action is being triggered... then perform the action.

		$orders = isset( $_REQUEST['wp_ajax_list_order'] ) ? $_REQUEST['wp_ajax_list_order'] : array();
		$orders = array_map( 'sanitize_text_field', $orders );
        //
		$current_action = $this->current_action();
		if ( ! empty( $current_action ) ) {
			// Bulk operation action. Lets make sure multiple records were selected before going ahead.
			if ( empty( $orders ) ) {
				echo '<div id="message" class="error"><p>Error! You need to select multiple records to perform a bulk action!</p></div>';
				return;
			}
		} else {
			// No bulk operation.
			return;
		}

	}


	function show_all_orders() {
		ob_start();
		$status = filter_input( INPUT_GET, 'status' );
		include_once PRIMER_PATH . 'views/admin_order_list.php';
		$output = ob_get_clean();
		return $output;
	}


	function handle_main_primer_admin_menu() {
		do_action('primer_orders_menu_start');

		$action = filter_input(INPUT_GET, 'primer_action');
		$action = empty($action) ? filter_input(INPUT_POST, 'action') : $action;
		if (empty($action)) {
			$action = sanitize_text_field($_GET['page']);
		}
		$selected = $action;
		?>
		<div class="wrap primer-admin-menu-wrap">
			<div class="plugin_caption_version"><?php echo PRIMER_NAME . ' v'. PRIMER_VERSION; ?></div>
		<?php
		 if ($_GET['page'] === 'wp_ajax_list_order') { ?>
		 	<h2><?php _e('Orders', 'primer'); ?>
			<?php //Trigger hooks that allows an extension to add extra nav tabs in the members menu.
			do_action( 'primer_menu_nav_tabs', $selected ); ?>
			</h2>
			<?php
			//Trigger hook so anyone listening for this particular action can handle the output.
			do_action( 'primer_menu_body_' . $action );

			//Allows an addon to completely override the body section of the members admin menu for a given action.
			$output = apply_filters( 'primer_menu_body_override', '', $action );
			if ( ! empty( $output ) ) {
				//An addon has overriden the body of this page for the given action. So no need to do anything in core.
				$allowed_html = wp_kses_allowed_html();
				echo wp_kses($output, $allowed_html);
				echo '</div>'; //<!-- end of wrap -->
				return;
			} ?>
		 <?php } ?>

			<?php

			//Switch case for the various different actions handled by the core plugin.
			switch ( $action ) {
				case 'orders_list':
					// Show the orders listing
					echo ($this->show_all_orders());
					break;
				case 'primer_receipts':
					echo ($this->show_all_receipts());
					break;
				default:
					// Show the orders listing by default
					echo ($this->show_all_orders());
					break;
			}

			echo '</div>';
	}
}


/**
 * Action wp_ajax for fetching ajax_response
 */
function _ajax_fetch_primer_order_callback() {
	$primer_order_list_table = new PrimerReceipts();
	$primer_order_list_table->ajax_response();
}
//add_action( 'wp_ajax__ajax_fetch_primer_order', '_ajax_fetch_primer_order_callback' );

/**
 * Action wp_ajax for fetching the first time table structure
 */

function ajax_primer_display_callback() {
	check_ajax_referer( 'ajax-order-list-nonce', '_ajax_order_list_nonce', true );

	$primer_order_list_table = new PrimerReceipts();
	$primer_order_list_table->prepare_items();

	ob_start();
	$primer_order_list_table->display();
	$display = ob_get_clean();


	die( wp_json_encode( array( "display" => $display )) );

}

add_action('wp_ajax_convert_select_orders', 'convert_select_orders');
function convert_select_orders() {
	$order_nonce = isset($_POST['order_nonce']) ? $_POST['order_nonce'] : '';
	check_ajax_referer( 'order_nonce', 'order_nonce' );
	if( wp_verify_nonce( $order_nonce, 'order_nonce') ) die('Stop!');
	$mydata_options = get_option('primer_mydata');

    if (!defined('MINUTE_IN_SECONDS')) {
        define('MINUTE_IN_SECONDS', 60); // Define it as 60 seconds if not already defined
    }
    if (get_transient('convert_order_to_invoice_lock')) {
        // Already running, skip execution
        return;
    }

     // Set a transient lock to prevent concurrent execution
    set_transient('convert_order_to_invoice_lock', true, MINUTE_IN_SECONDS);
	$classificationType = '';
	$classificationCategory = '';
	$classificationCategory_en = 'category1_95';
	$api_url = $mydata_options['mydata_api'];
	switch ($api_url) {
		case 'test_api':
			$url = 'https://test-mydataapi.primer.gr/v2/invoices/sendData';
			$api_type = 'test';
			break;
		case 'production_api':
			$url = 'https://wp-mydataapi.primer.gr/v2/invoices/sendData';
			$api_type = 'production';
			break;
		default:
			$url = 'https://test-mydataapi.primer.gr/v2/invoices/sendData';
			$api_type = 'test';
	}
	$primer_license_data = get_option('primer_licenses');
	$username = $primer_license_data['username'] ? $primer_license_data['username'] : '';
	$password = $primer_license_data['password'] ? $primer_license_data['password'] : '';
	$user_vat = $primer_license_data['companyVatNumber'];
	$send_api_invoice = true;
    $callingFunction = 'convert_select_orders';
	$url_slug = 'https://wp-mydataapi.primer.gr';
	$auth = base64_encode( "$username" . ':' . "$password" );
	$curl_args = array(
		'timeout' 		=> 30,
		'redirection' 	=> 10,
		'method'		=> 'POST',
		'httpversion' 	=> '1.1',
		'headers'		=> array(
			'Authorization' => 'Basic ' . $auth,
			'Content-Type'	=> 'application/json',
			'charset=UTF-8'
		),
		'sslverify'		=> false
	);
	$total_vat_number = "$user_vat";
	$log_ids = array();
	$orders = isset($_GET['orders']) ? $_GET['orders'] : '';
    array_map( 'sanitize_text_field', $orders );
	if (!isset($_GET['orders']) && !empty($mydata_options['last_request']) && isset($_GET['resend_html'])) {
		$orders = $mydata_options['last_request'];
	}
	if (!empty($orders)) {
        $mydata_options['already_running_orders'] = count($orders) * 5;
            update_option('primer_mydata', $mydata_options);
		foreach ( $orders as $order_id ) {
            $order = new WC_Order($order_id);
            $id_of_order = $order->get_id();
            $issue_date = new DateTime("now", new DateTimeZone("Europe/Athens"));
            $gr_time = $issue_date->format('Y-m-d');
            $receipt_log_id = wp_insert_post(array(
                'post_type' => 'primer_receipt_log',
                'post_title' => 'Receipt report for #' . $id_of_order,
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_status' => 'publish',
            ));
            if (!ini_get('allow_url_fopen')) {
                $response_data .= '<div class="primer_popup popup_error"><div><h3>'.__('Php option allow_url_fopen is disabled! Please communicate with your hosting provider in order to activate it.', 'primer') .'</h3><br><br><br><br><br></div>';
                $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
                $receipt_log_value_array[] = __('Php option allow_url_fopen is disabled! Please communicate with your hosting provider in order to activate it.', 'primer');
                $receipt_log_value .= __('Php option allow_url_fopen is disabled! Please communicate with your hosting provider in order to activate it.', 'primer');
                break ;
            }
            if ( get_post_meta($id_of_order, 'receipt_status', true) == 'issued' && get_post_meta($id_of_order, 'transmission_failure_check', true) == null ) {
                $response_data .= '<div class="primer_popup popup_error"><div><h3>'.__('This order has already been issued. Please refresh the page.', 'primer') .'</h3><br><br><br><br><br></div>';
                $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
                $receipt_log_value_array[] = __('This order has already been issued by the automation. Press refresh.', 'primer');
                $receipt_log_value .= __('This order has already been issued by the automation. Press refresh.', 'primer');
                break ;
            }
            $i = 1;

            $invoice_data = "";
                while ($i <= 1) {
                    $order_country = '';
                    $order_invoice_type = '';
                    $order_vatNumber = '';
                    $user_order_email = '';
                    $currency = '';
                    $currency_symbol = '';
                    $user_id = '';
                    $order_total_price = '';
                    $user_full_name = '';
                    $user_data = '';
                    $receipt_log_value_array = array();//edw
                    $insert_taxonomy ='receipt_status';
                    $serie = get_post_meta($order_id, '_primer_receipt_series', true) != null ? get_post_meta($order_id, '_primer_receipt_series', true) != null : '';
                    $series = '';
                    $number = get_post_meta($order_id, '_primer_receipt_number', true) != null ? get_post_meta($order_id, '_primer_receipt_number', true) : '';
                    $connectionFailedMessage = get_post_meta($order_id, 'connection_fail_message', true) ? get_post_meta($order_id, 'connection_fail_message', true) : '';
                    $invoice_term = '';
                    $invoice_time = '';
                    $response_data = '';
			        $invoiceType =  '';
                    $receipt_log_value = '';
                    $total = '';
                    //EDW KALW TIN METHODO
                    $create_json_instance = new Create_json();
                    $Vat_exemption_categories = $create_json_instance->getVatExemptionCategories();
                    $Vat_exemption_categories_en = $create_json_instance->getVatExemptionCategoriesEn();
                            $mydata_options = get_option('primer_mydata');
                            $invoice_data = $create_json_instance->create_invoice($user_id, $order_id, $total_vat_number, $mydata_options, $primer_license_data,
                                $total, $series, $serie, $number, $currency, $currency_symbol, $user_data, $insert_taxonomy,
                                $classificationCategory, $classificationCategory_en, $response_data, $receipt_log_value, $receipt_log_value_array,
                                $receipt_log_id, $invoice_term, $gr_time, $invoice_time, $order_total_price, $order_invoice_type,
                                $order_vatNumber, $user_order_email, $order_country, $user_full_name, $primer_smtp, $log_ids, $callingFunction, $invoiceType,
                                $send_api_invoice, $classificationType);
                    if ($invoice_data == "rerun") {
                        $i = 1;
                    } else {
                        $i++;
                    }
                }
            if ($invoice_data == "break") {
                break;
            }
            elseif ($invoice_data == "continue") {
                continue;
            }
            if ($send_api_invoice) {
                $curl_args['body'] = json_encode($invoice_data);
                $response = wp_remote_post($url, $curl_args);
				$response_message = wp_remote_retrieve_body($response);
				$response_code = wp_remote_retrieve_response_code($response);
				$response_to_array = null;
				if ( is_wp_error( $response ) ) {
					$response_data .= '<div class="primer_popup popup_error"><div><h3>'.$response->get_error_message().'</h3><br><br><br><br><br></div>';
                    $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
					$receipt_log_value_array[] = $response->get_error_message();
                    $first_time='';
                    $string_timeout = 'Operation timed out after';
                    $message_timeout = $response->get_error_message();
                    $check_string_timeout = strpos($message_timeout,$string_timeout);
					if ($check_string_timeout) {
                        $is_timeout = true;
					    $first_time = get_post_meta($id_of_order, 'order_date_failed_timeout',true);
					    if($first_time == null){
                        update_post_meta($id_of_order, 'order_date_failed_timeout',$gr_time);
                        $first_time = get_post_meta($id_of_order, 'order_date_failed_timeout',true);
                        }
					}
				} else {
					$response_to_array = wp_remote_retrieve_body($response);
				}
                $time_for_call_timeout_48 = get_post_meta($id_of_order, 'order_datetime_failed',true);
                $time_for_call_timeout_1 = '';
                if($time_for_call_timeout_48 && ($response_code > 500 || $is_timeout)){
                    $time_for_call_timeout_1 = date('Y-m-d H:i:s', strtotime($time_for_call_timeout_48. ' + 2 days'));
                    if($time_for_call_timeout_1 > $gr_time ){
                         $mydata_options['timeout_check_48'] = 0;
                     } else{
                        $mydata_options['timeout_check_48'] = 1;
                        update_post_meta($id_of_order, 'failed_48','yes');
                     }
                     update_option( 'primer_mydata', $mydata_options );
               }
                $general_settings = get_option('primer_generals');
                if($general_settings['primer_cron_transmission_failure'] != 'on' && ($response_code == 502 || $response_code == 512)){
                    $receipt_log_value .= __('Could not connect to AADE. Please check your general settings if you want to enable transmission failure option or contact Primer Software if the problem persists.', 'primer');
                            $response_data .= '<div class="primer_popup popup_error"><div><h3>'.__('Could not connect to AADE. Please check your general settings if you want to enable transmission failure option or contact Primer Software if the problem persists.', 'primer').'</h3><br><br><br><br><br></div>';
                            $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
				$receipt_log_value_array[] = __('Could not connect to AADE. Please check your general settings if you want to enable transmission failure option or contact Primer Software if the problem persists.','primer');
				break;
                }
                $string_0_remaining = 'You have no other Monthly Invoices remaining';
                $check_string_remaining = strpos($response_message,$string_0_remaining);
                if($check_string_remaining !== false && $api_type == 'production'){
                    $mydata_options['check_0_remaining'] = 1;
                    update_option( 'primer_mydata', $mydata_options );
                }
                if($mydata_options['check_0_remaining'] == 1 && $api_type == 'production'){
                    $receipt_log_value .= __('You have no other monthly invoices left.Please go to MyData settings and press "Get Remaining" button if you are sure that the month is passed and you have remaining invoices.', 'primer');
                            $response_data .= '<div class="primer_popup popup_error"><div><h3>'.__('You have no other monthly invoices left.Please go to MyData settings and press "Get Remaining" button if you are sure that the month is passed and you have remaining invoices.', 'primer').'</h3><br><br><br><br><br></div>';
                            $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
				$receipt_log_value_array[] = __('You have no other monthly invoices left.Please go to MyData settings and press "Get Remaining" button if you are sure that the month is passed and you have remaining invoices.','primer');
				break;
                }
                $string_block = 'Unauthorized to send, expecting Invoice file.';
                $string_r ='It has already been sent for another invoice';
                $validate_response_block = strpos($response_message, $string_block);
                $validate_response = strpos($response_message, $string_r);
                $generated_uid = strtoupper(sha1(iconv("UTF-8", "ISO-8859-7",strval($invoice_data['invoice'][0]['issuer']['vatNumber']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['issueDate']).'-'.strval($invoice_data['invoice'][0]['issuer']['branch']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['invoiceType']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['series']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['aa']))));
                if($validate_response !== false || $validate_response_block !== false) {
                    //start checking
                    $create_status_instance = new Create_json();
                    $status = $create_status_instance -> get_invoice_status($api_type, $id_of_order, $gr_time, $serie, $number, $invoiceType, $order_invoice_type, $order_vatNumber, $user_vat,
                                                                            $total, $auth, $invoice_term, $insert_taxonomy,$order, $user_data, $user_id, $order_total_price, $currency_symbol,
                                                                            $order_country, $mydata_options, $series, $total_vat_number, $receipt_log_value, $receipt_log_value_array, $user_order_email,
                                                                             $response_data, $receipt_log_id, $url_slug, $callingFunction,$generated_uid, null,null);

                    if ( $status == "break" ) {
                        continue;
                    }
                    else {
                        //echo "<br>";
                        //echo $callingFunction;
                        update_option('primer_mydata', $mydata_options);
                        $response = array(
                            'status' => 'success',
                            'data' => $response_data
                        );

                        echo json_encode($response);
                        delete_transient('convert_order_to_invoice_lock');
                        wp_die();
                    }
             }else if(!$validate_response){
				$find_code_error = 'Gateway';
				$code_position = strpos($response_message, $find_code_error);
				$response_to_array = json_decode($response_message);
				if ($response_code == 400 || $response_code == 422) {
					$response_data .= '<div class="primer_popup popup_error"><div><h3>'.$response_message.'</h3><br><br><br><br><br></div>';
                    $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
					$receipt_log_value_array[] = $response_to_array;
                    update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                    break;
				}
                if ($response_code == 403) {
					$response_data .= '<div class="primer_popup popup_error"><div><h3>'.$response_message.'</h3><br><br><br><br><br></div>';
                    $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
					$receipt_log_value_array[] = $response_message;
                    update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                    break;
				}
				if (!empty($response_to_array) || ($response_code > 500 || $is_timeout)) {
				   $response_from_array = $response_to_array->response;
					if (!empty($response_from_array) || ($response_code > 500 || $is_timeout)) {
						if (($response_from_array[0]->statusCode == 'Success') || ($response_code > 500 || $is_timeout)) {
							$invoice_uid = $response_from_array[0]->invoiceUid;
							$invoice_mark = $response_from_array[0]->invoiceMark;
							$invoice_authcode = $response_from_array[0]->authenticationCode;
							$post_id = wp_insert_post(array(
								'post_type' => 'primer_receipt',
								'post_title' => 'Receipt for order #' . $id_of_order,
								'comment_status' => 'closed',
								'ping_status' => 'closed',
								'post_status' => 'publish',
							));
							wp_set_object_terms($post_id, $invoice_term, $insert_taxonomy, false);
							if ($post_id) {
								$issue_date = new DateTime("now", new DateTimeZone("Europe/Athens"));
								$invoice_date = $issue_date->format('d/m/Y');
								$invoice_time = $issue_date->format('H:i');
								update_post_meta($post_id, 'success_mydata_date', $invoice_date);
								update_post_meta($post_id, 'success_mydata_time', $invoice_time);
                                update_post_meta($post_id, 'receipt_type', $invoice_term);
								update_post_meta($post_id, 'send_to_api_type', $api_type);
								update_post_meta($post_id, 'order_id_to_receipt', $id_of_order);
								update_post_meta($id_of_order, 'order_id_from_receipt', $post_id);
								add_post_meta($post_id, 'receipt_client', $user_data);
								add_post_meta($post_id, 'receipt_client_id', $user_id);
								add_post_meta($post_id, 'receipt_price', $order_total_price . ' ' .$currency_symbol);
								update_post_meta( $post_id, '_primer_receipt_number', $number );
                                update_post_meta( $post_id, '_primer_receipt_series', $serie );
                                // update_post_meta
								// update the invoice number
                                 $create_json_instance->numbering($order_invoice_type, $order_country,$mydata_options, $series);
                                 update_option('primer_mydata', $mydata_options);
								if (!empty($invoice_uid)) {
									update_post_meta($post_id, 'response_invoice_uid', $invoice_uid);

								}
								if (!empty($invoice_mark)) {
									update_post_meta($post_id, 'response_invoice_mark', $invoice_mark);
								}
								if (!empty($invoice_authcode)) {
									update_post_meta($post_id, 'response_invoice_authcode', $invoice_authcode);
								}
                                if (!isset($primer_license_data['currentBranchID'])) {
                                    $currentBranchID = 0;
                                } else {
                                    $currentBranchID = $primer_license_data['currentBranchID'];
                                }

                                update_post_meta($post_id, 'branchID', $currentBranchID);
                                if ($serie == "EMPTY" ) {
                                    $identifier = "0" . "_" . $number . "_" . $invoice_term . "_" . $currentBranchID;
                                } else {
                                    $identifier = $serie . "_" . $number . "_" . $invoice_term . "_" . $currentBranchID;
                                }
                                update_post_meta($post_id, 'numbering_identifier',$identifier);
                                if ($response_code == 512) {
                                    update_post_meta($post_id, 'connection_fail_message', 'ΑΔΥΝΑΜΙΑ ΔΙΑΣΥΝΔΕΣΗΣ ΠΑΡΟΧΟΥ - ΑΑΔΕ');
                                } elseif ($response_code > 500 || $is_timeout) {
                                    update_post_meta($post_id, 'connection_fail_message', 'ΑΔΥΝΑΜΙΑ ΔΙΑΣΥΝΔΕΣΗΣ ΟΝΤΟΤΗΤΑΣ - ΠΑΡΟΧΟΥ');
                                } else {
                                    update_post_meta($post_id, 'connection_fail_message', '');
                                }
                            }
                            primer_generate_qr($post_id, $generated_uid);
							$primer_options = new Primer_Options();
							$post_ids_str = '';
							if (!empty($post_id)) {
								$post_ids_str = $total_vat_number . $invoice_mark;
							}
							$post_arr_id = explode(" ", $post_id);
							$use_url_params = '?type_logo=id';
							$generate_html_response = $primer_options->export_receipt_as_static_html_by_page_id($post_arr_id, $use_url_params);
							$upload_dir = wp_upload_dir()['basedir'];
							if ($generate_html_response) {
								$all_files = $upload_dir . '/exported_html_files/tmp_files';
								$files = $primer_options->get_all_files_as_array($all_files);
								$zip_file_name = $upload_dir . '/exported_html_files/'.$post_ids_str.'_html.zip';
								ob_start();
								echo $primer_options->create_zip($files, $zip_file_name, $all_files . '/');
								$create_zip = ob_get_clean();

								if ($create_zip == 'created') {
									$primer_options->rmdir_recursive($upload_dir . '/exported_html_files/tmp_files');
								}
							}
									$post_url = get_the_permalink($post_id);
                                    $post_url = $post_url . '?receipt=view&username='.$primer_license_data['username'];
                                    // Create a stream context with appropriate headers
                                    $arrContextOptions = array(
                                        "ssl" => array(
                                            "verify_peer" => false,
                                            "verify_peer_name" => false,
                                        ),
                                        "http" => array(
                                            "header" => "Content-type: text/html; charset=utf-8\r\n",
                                        ),
                                    );

                                    $context = stream_context_create($arrContextOptions);
                                    // Retrieve the HTML content with the specified headers
                                    $homepage = file_get_contents($post_url, false, $context);
                                    $modified_homepage =  $homepage;
                                    $log_id = get_post_meta($id_of_order, 'log_id_for_order', true);
                                    $json = get_post_meta($log_id,'json_send_to_api',true);
                                    $data = json_decode($json, true);
                                    $varExemptionCategory = array();
                                    foreach ($data['invoice'][0]['invoiceDetails'] as $invoiceDetails) {
                                        if ( $invoiceDetails['vatExemptionCategory'] != null){
                                            $varExemptionCategory[] = $invoiceDetails['vatExemptionCategory'];
                                        }
                                    }
                                    $varExemptionCategory = array_unique($varExemptionCategory);
                                    $varExemptionCategory = array_values($varExemptionCategory);
                                    $count = count($varExemptionCategory);
                                    if ( get_post_meta($id_of_order, '_billing_country', true) == "GR") {
                                        if ( $count>0 ) {
                                            $exception_vat = '<div><span class="skin bold">ΑΠΑΛΛΑΓΗ ΑΠΟ ΤΟ Φ.Π.Α :</span></div>';
                                            for ($i = 0; $i < $count; $i++){
                                                $exception_vat .= '<div>'.$Vat_exemption_categories[$varExemptionCategory[$i]].'</div>';
                                            }
                                        } else {
                                            $exception_vat = '';
                                        }
                                        $modified_homepage = str_replace('<div class="cont_notation"><span class="skin bold">ΠΑΡΑΤΗΡΗΣΕΙΣ:</span>', '<div class="cont_notation">' . $exception_vat . '<span class="skin bold">ΠΑΡΑΤΗΡΗΣΕΙΣ:</span>', $modified_homepage);
                                    }
                                    else {
                                        if ( $count>0 ) {
                                            $exception_vat = '<div><span class="skin bold">EXEMPTION FROM VAT :</span></div>';
                                            for ($i = 0; $i < $count; $i++) {
                                                $exception_vat .= '<div>' . $Vat_exemption_categories_en[$varExemptionCategory[$i]] . '</div>';
                                            }
                                        } else {
                                            $exception_vat = '';
                                        }
                                        $modified_homepage = str_replace('<div class="cont_notation"><span class="skin bold">COMMENTS:</span>', '<div class="cont_notation">' . $exception_vat . '<span class="skin bold">COMMENTS:</span>', $modified_homepage);
	                                }
                                    if ( (get_post_meta($id_of_order, '_billing_invoice_type', true) == 'receipt') && (get_post_meta($id_of_order, '_billing_country', true) == "GR") )  {
                                        $modified_homepage = str_replace('<div class="information left">', '<div class="information left" style="height: 120px">', $modified_homepage);
                                        $modified_homepage = str_replace('<div class="information right">', '<div class="information right" style="height: 120px">', $modified_homepage);
                                    }
                                    elseif ( (get_post_meta($id_of_order, '_billing_invoice_type', true) == 'receipt') && (get_post_meta($id_of_order, '_billing_country', true) != "GR")) {
                                        $modified_homepage = str_replace('<div class="information left">', '<div class="information left" style="height: 120px">', $modified_homepage);
                                        $modified_homepage = str_replace('<div class="information right">', '<div class="information right" style="height: 120px">', $modified_homepage);
                                    }
                                    elseif ( (get_post_meta($id_of_order, '_billing_invoice_type', true) != 'receipt') && (get_post_meta($id_of_order, '_billing_country', true) == "GR") ) {
                                        $modified_homepage = str_replace('<div class="information left">', '<div class="information left" style="height: 160px">', $modified_homepage);
                                        $modified_homepage = str_replace('<div class="information right">', '<div class="information right" style="height: 160px">', $modified_homepage);
                                    }
                                    elseif ( (get_post_meta($id_of_order, '_billing_invoice_type', true) != 'receipt') && (get_post_meta($id_of_order, '_billing_country', true) != "GR") ) {
                                        $modified_homepage = str_replace('<div class="information left">', '<div class="information left" style="height: 160px">', $modified_homepage);
                                        $modified_homepage = str_replace('<div class="information right">', '<div class="information right" style="height: 160px">', $modified_homepage);
                                    }
                                    if ( get_post_meta($id_of_order, '_billing_country', true) != "GR") {
                                        $modified_homepage = str_replace('<tr><p class="table_titles">CUSTOMER INFORMATION</p>', '<tr><td colspan="2" style="text-align: center;"><p class="table_titles" style="white-space: nowrap; text-align: center;">CUSTOMER INFORMATION</p></td></tr><tr>',$modified_homepage);
                                        $modified_homepage = str_replace('<tr><p class="table_titles">OTHER INFORMATION</p>', '<tr><td colspan="2" style="text-align: center;"><p class="table_titles">OTHER INFORMATION</p></td></tr><tr>',$modified_homepage);
                                    } else {
                                        $modified_homepage = str_replace('<tr><p class="table_titles">ΣΤΟΙΧΕΙΑ ΠΕΛΑΤΗ</p>', '<tr><td colspan="2" style="text-align: center;"><p class="table_titles" style="white-space: nowrap; text-align: center;">ΣΤΟΙΧΕΙΑ ΠΕΛΑΤΗ</p></td></tr><tr>',$modified_homepage);
                                        $modified_homepage = str_replace('<tr><p class="table_titles">ΛΟΙΠΑ ΣΤΟΙΧΕΙΑ</p>', '<tr><td colspan="2" style="text-align: center;"><p class="table_titles">ΛΟΙΠΑ ΣΤΟΙΧΕΙΑ</p></td></tr><tr>',$modified_homepage);
                                    }
									$dompdf = new Dompdf();
									$options= $dompdf->getOptions();
									$options->setIsHtml5ParserEnabled(true);
									$options->setIsRemoteEnabled(true);
                                    $options->setDefaultFont('DejaVu Sans');
									$dompdf->setOptions($options);
									$dompdf->loadHtml($modified_homepage);
									// Render the HTML as PDF
									$dompdf->render();
									$upload_dir = wp_upload_dir()['basedir'];
									if (!file_exists($upload_dir . '/email-invoices')) {
										mkdir($upload_dir . '/email-invoices');
									}
									$post_name = get_the_title($post_id);
									$post_name = str_replace(' ', '_', $post_name);
									$post_name = str_replace('#', '', $post_name);
									$post_name = strtolower($post_name);
									$post_name = sanitize_text_field($post_name);
									$output = $dompdf->output();
									$upload_dir_file = $upload_dir . '/email-invoices/'.$post_name.'.pdf';
									if (!empty(realpath($upload_dir . '/email-invoices/'))) {
                                        file_put_contents($upload_dir_file, $output);
                                    }
									$attachments = $upload_dir . '/email-invoices/'.$post_name.'.pdf';
									$post_issued = 'issued';
									update_post_meta($post_id, 'receipt_status', $post_issued);
									update_post_meta($id_of_order, 'receipt_status', $post_issued);
									add_post_meta($post_id, 'receipt_client', $user_data);
									add_post_meta($post_id, 'receipt_client_id', $user_id);
									add_post_meta($post_id, 'receipt_price', $order_total_price . ' ' .$currency_symbol);
									$primer_smtp_options = get_option('primer_emails');
									if (!empty($primer_smtp_options['email_subject'])) {
										$primer_smtp_subject = $primer_smtp_options['email_subject'];
									} else {
										$primer_smtp_subject = __('Test email subject', 'primer');
									}
									if (!empty($primer_smtp_options['quote_available_content'])) {
										$primer_smtp_message = $primer_smtp_options['quote_available_content'];
                                        $client_first_name = get_post_meta($order_id, '_billing_first_name', true);
                                        $client_last_name = get_post_meta($order_id, '_billing_last_name', true);
                                        $client_email = get_post_meta($order_id, '_billing_email', true);
                                        $streetAddress = get_post_meta($order_id, '_billing_address_1', true);
                                        $townCity = get_post_meta($order_id, '_billing_city', true);
                                        $phone = get_post_meta($order_id, '_billing_phone' , true);
                                        $primer_smtp_message = str_replace('{ClientFirstName}', $client_first_name, $primer_smtp_message);
                                        $primer_smtp_message = str_replace('{ClientLastName}', $client_last_name, $primer_smtp_message);
                                        $primer_smtp_message = str_replace('{ClientEmail}', $client_email, $primer_smtp_message);
                                        $primer_smtp_message = str_replace('{StreetAddress}', $streetAddress, $primer_smtp_message);
                                        $primer_smtp_message = str_replace('{TownCity}', $townCity, $primer_smtp_message);
                                        $primer_smtp_message = str_replace('{Phone}', $phone, $primer_smtp_message);
									} else {
										$primer_smtp_message = __('Test email message', 'primer');
									}
									$primer_automatically_send_file = $primer_smtp_options['automatically_send_on_conversation'];
                                    $primer_smtp_type = $primer_smtp_options['smtp_type'];
									if (empty($primer_automatically_send_file)) {
										$primer_automatically_send_file = 'yes';
									}
									$order_log_id = get_post_meta($id_of_order, 'log_id_for_order', true);
									if ($order_log_id) {
										update_post_meta($post_id, 'log_id_for_order', $order_log_id);
										$invoice_date = get_the_date('F j, Y', $post_id);
										update_post_meta($order_log_id, 'receipt_log_invoice_id', $post_id);
										update_post_meta($order_log_id, 'receipt_log_invoice_date', $invoice_date);
										update_post_meta($order_log_id, 'receipt_log_client', $user_data);
										$get_issue_status = get_post_meta($post_id, 'receipt_status', true);
										if(empty($get_issue_status)) {
											$get_issue_status = 'issued';
										}
										update_post_meta($order_log_id, 'receipt_log_status', $get_issue_status);
										update_post_meta($order_log_id, 'receipt_log_error', $receipt_log_value);
                                                if($receipt_log_value != null){
                                                    update_post_meta($order_log_id, 'receipt_log_total_status', 'only_errors');
                                                }
									}
									$email_logs = '';
									if (!empty($primer_automatically_send_file) && $primer_automatically_send_file === 'yes' && $order->get_billing_email() != '' && $order->get_billing_email() != null) {
										$mailResult = false;
										$primer_smtp = PrimerSMTP::get_instance();
                                         if (!empty($primer_smtp_options['email_from_name'])) {
												$from_name_email = $primer_smtp_options['email_from_name'];
											} else {
												$from_name_email = '';
											}
                                            if($primer_smtp_type == 'wordpress_default'){
                                                    $headers = array('Content-Type: text/html; charset=UTF-8');
                                                    $mailResultSMTP = wp_mail($order->get_billing_email(),$primer_smtp_subject,$primer_smtp_message,$headers,$attachments);
                                                }else{
												$mailResultSMTP = $primer_smtp->primer_mail_sender($order->get_billing_email(),$from_name_email, $primer_smtp_subject, $primer_smtp_message, $attachments);
                                                }
												if (! $primer_smtp->credentials_configured()) {
													$email_logs .= __('Configure your SMTP credentials', 'primer') ."\n";
												}
										if (! $primer_smtp->credentials_configured()) {
											$email_logs .= __('Configure your SMTP credentials', 'primer') ."\n";
										}

										if (!empty($mailResultSMTP['error']) && ! $primer_smtp->credentials_configured()) {
											$response_data .= '<div class="primer_popup popup_error"><div><h3>'.$GLOBALS['phpmailer']->ErrorInfo.'</h3><br><br><br><br><br></div>';
                                            $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
											update_post_meta($order_log_id, 'receipt_log_email', 'not_sent');
											$email_logs .= $GLOBALS['phpmailer']->ErrorInfo ."\n";
											update_post_meta($order_log_id, 'receipt_log_email_error', $email_logs);
											update_post_meta($order_log_id, 'receipt_log_total_status', 'only_errors');
										} else {
											update_post_meta($order_log_id, 'receipt_log_email', 'sent');
											update_post_meta($order_log_id, 'receipt_log_total_status', 'only_issued');
										}

										update_post_meta($post_id, 'exist_error_log', 'exist_log');
									} else {
										if (! $primer_smtp->credentials_configured()) {
											$email_logs .= __('Configure your SMTP credentials', 'primer') ."\n";
										}
											$email_logs .= __('Send email automatically on order conversion disabled', 'primer') ."\n";
											update_post_meta($order_log_id, 'receipt_log_email', 'not_sent');
											update_post_meta($order_log_id, 'receipt_log_email_error', $email_logs);
											update_post_meta($order_log_id, 'receipt_log_total_status', 'only_issued');
										}
                                    update_post_meta($id_of_order, 'transmission_failure_check', 2);
                                    $mydata_options['timeout_check_48'] = 0;
                                    update_post_meta($id_of_order, 'failed_48','no');
                                    if (($response_code > 500 || $is_timeout) && $general_settings['primer_cron_transmission_failure'] == 'on') {
                                        if ($response_code == 512 ) {
                                            $receipt_log_value .= __('Unable to connect Provider and AADE.', 'primer');
                                            $receipt_log_value_array[] = __('Unable to connect Provider and AADE.', 'primer');
                                            update_post_meta($id_of_order, 'transmission_failure_check', 3);
                                            update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                                        } else {
                                            $receipt_log_value .= __('Unable to connect Entity and provider..', 'primer');
                                            $receipt_log_value_array[] = __('Unable to connect Entity and provider.', 'primer');
                                            update_post_meta($id_of_order, 'transmission_failure_check', 1);
                                            update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                                        }
                                        $gr_timezone = new DateTimeZone("Europe/Athens");
                                        $gr_time = new DateTime("now", $gr_timezone);
                                        update_post_meta($id_of_order, 'order_date_failed', $gr_time->format('Y-m-d'));
                                        update_post_meta($id_of_order, 'order_datetime_failed', $gr_time->format('Y-m-d H:i:s'));
                                    }
									$response_data = '<div class="primer_popup popup_success"><div>';
                                    $response_data .= '<h3>'.__("Orders converted", "primer").'</h3><br><br><br><br><br>';
                                    $response_data .= '<button class="popup_ok button button-primary">OK</button>';
                                    $response_data .= '</div></div>';
						} else {
							update_option('primer_mydata', $mydata_options);
							$response_data .= is_object($response_from_array[0]->errors) ? json_encode($response_from_array[0]->errors) : $response_from_array[0]->errors;
							$receipt_log_value_array[] = $response_data;
                            //sleep(0.5);
							continue;
						}
					}
				} else {
				    $receipt_log_value .= __('API not sent.', 'primer');
				$receipt_log_value_array[] = __('API not sent.', 'primer');
				update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
				$inside_response_msg = $response_message ? $response_message : __('Something wrong', 'primer');
				$response_data .= '<div class="primer_popup popup_error"><div><h3>'.$inside_response_msg.' '.$response_code.'</h3><br><br><br><br><br></div>';
                $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
				}
			} else {
				$receipt_log_value .= __('API not sent.', 'primer');
				$receipt_log_value_array[] = __('API not sent.', 'primer');
				update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
				$response_data .= '<div class="primer_popup popup_error"><div><h3>'.__('API not sent.', 'primer').'</h3><br><br><br><br><br></div>';
                $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
			}

            if (!empty($receipt_log_value_array)) {
            	update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                    update_post_meta($receipt_log_id, 'receipt_log_total_status', 'only_errors');
            }

		}
    //usleep(500000);
	}
		if (!empty($log_ids)) {
			foreach ($log_ids as $log_id) {
				update_post_meta($log_id, 'receipt_log_error', $receipt_log_value_array);
                if(!empty($receipt_log_value_array)){
                    update_post_meta($log_id, 'receipt_log_total_status', 'only_errors');
                }
			}
		}

    $response = array(
        'status' => 'success',
        'data' => $response_data
    );

    echo json_encode($response);

    }
//


    delete_transient('convert_order_to_invoice_lock');
    //update_option('primer_mydata', $mydata_options);
    wp_die();
}

/**
 * fetch_primer_script function
 */
function fetch_primer_script() {
	$screen = get_current_screen();

	?>

	<?php
	 if ( $screen->id != "toplevel_page_wp_ajax_list_order" && $screen->id != "admin_page_primer_receipts_logs" && $screen->id != "admin_page_primer_receipts_logs_automation") {
		return;
	}
	 ?>

	<script>
		/**
		 * Create and download a temporary file.
		 *
		 * @param {string} filename - File name.
		 * @param {string} text - File content.
		 */
		function download(filename, text) {
			// Create temporary element.
			var element = document.createElement('a');
			element.setAttribute('href', 'data:application/json;charset=<?php echo esc_html( get_option( 'blog_charset' ) ); ?>,' + encodeURIComponent(text));
			element.setAttribute('download', filename+'.json');
			// Set the element to not display.
			element.style.display = 'none';
			document.body.appendChild(element);
			// Simulate click on the element.
			element.click();
			// Remove temporary element.
			document.body.removeChild(element);
		}

        (function ($) {
            function check_exist_receipts(orders) {
                var order_arr = new Array();
                $(orders).each(function (i, el) {
                    var tr_parent = $(el).parents('tr');
                    var sibling_td = tr_parent.find('td.receipt_id');
                    var transmission_failure_column =tr_parent.find('td.transmission_failure');
                    var tran_failure = transmission_failure_column.text();
                    if (sibling_td) {
                        var td_status = sibling_td.text();
                    }
                    if (td_status !== '' && tran_failure !=='Αδυναμία σύνδεσης') {
                        $(el).prop('checked', false);
                    }
                    var order_id = $(el).val();
                    if (order_id) {
                        order_arr.push(order_id);
                    }
                })
            }

            function check_request_receipts(orders) {

                var valid = true;
                var data_status = '';
                var data_status_json = '';
                var data_transmission_failure = '';
                var data_already_running = '';
                var data_already_running_orders = '';
                var stop_conversion = '';
                var failed_48_system = '';
                var failed_48 = '';
                <?php
                	$mydata_options = get_option('primer_mydata');
                    if(is_array($mydata_options) && array_key_exists('last_request',$mydata_options)){
                	$last_request = $mydata_options['last_request'];
                    }

                      if(is_array($mydata_options) && !array_key_exists('timeout_check_48',$mydata_options)){
                         $mydata_options['timeout_check_48'] = '0';
                     }
                     $transmission_failure_last_request = '';
                	if (!empty($last_request)) {
                		$order_status = get_post_meta($last_request[0], 'receipt_status', true);
						$send_receipt_json = get_post_meta($last_request[0], 'order_id_from_receipt', true);
                        $transmission_failure_last_request = get_post_meta($last_request[0], 'transmission_failure_check', true);
                		?>
                		data_status = '<?php echo $order_status; ?>';
                		data_status_json = '<?php echo $send_receipt_json; ?>';
                	<?php }
                ?>
                        data_transmission_failure = '<?php echo $transmission_failure_last_request; ?>';
                        //data_already_running = '<?php //echo $mydata_options['running_conversion']; ?>';
                        data_already_running_orders = '<?php echo $mydata_options['already_running_orders']; ?>';
                        failed_48_system = '<?php echo $mydata_options['timeout_check_48']; ?>';
                        $(orders).each(function (i, el) {
                    var tr_parent = $(el).parents('tr');
                    var failure48_column = tr_parent.find('td.accept_48');
                    var failed_48 = failure48_column.text();
                    if (failed_48_system === '1' && failed_48 !== 'yes') {
                     stop_conversion = 'stop';
                    }
                });

                if (data_status === 'not_issued' && data_status_json !== '' &&  data_transmission_failure !== '1') {
                    valid = false;
                    alert('Go to "MyData settings" and click on button "Resend last HTML"');
                    $('.submit_convert_orders').attr('disabled', true);
                }
                else if(data_already_running === 'yes'){
                   /* valid = false;
                    alert(`Another conversion is already running.Please try again in approximately ${data_already_running_orders} seconds.`);
                    $('.submit_convert_orders').attr('disabled', true);*/
                }
               /*  else if(stop_conversion === 'stop'){
                     valid = false;
                    alert('Cound not connect to AADE for more than 48 hours.Please convert the failed orders first to proceed with further conversion.');
                    $('.submit_convert_orders').attr('disabled', true);
                } */ else {
                    valid = true;
                }
                console.log(valid);
                return valid;
            }

            function __(text, domain) {
                return text;
            }

            $('.submit_convert_orders').on('click', function (e) {
                $('.submit_convert_orders').attr('disabled', true);
                e.preventDefault();
                check_exist_receipts($('input[name="orders[]"]:checked'));
                var count_orders = $('input[name="orders[]"]:checked').length;
                var receipt_word = count_orders == 1 ? 'receipt' : 'receipts';
                var confirmation = confirm('You are about to issue ' + count_orders + ' ' + receipt_word + '. Are you sure?');
                var validation = check_request_receipts($('input[name="orders[]"]:checked'));
                if (confirmation == true && count_orders > 0 && validation == true) {
                    var data = $('#tables-filter').serialize();

                    $.ajax({
                        url: ajaxurl,
                        data: data,
                        beforeSend: function () {
                            var table = $('table.table-view-list');
                            table.css({ 'opacity': '0.5' });
                            // Get the z-index of the table-view-list and add 1 to make sure the spinner is on top
                            var tableZIndex = parseInt(table.css('z-index'), 10) || 0;
                            $('.loadingio-spinner-spinner-chyosfc7wi6').css({ 'z-index': tableZIndex + 1 });
                            $('.loadingio-spinner-spinner-chyosfc7wi6').show();
                        },
                        success: function (response) {
                            console.log(response);
                            var parsedResponse = JSON.parse(response);
                            var response_data = parsedResponse.data;
                            $('#wpbody-content').prepend(response_data);
                            popupOpenClose('.primer_popup');
                            // Add event listener to the "OK" button
                            $('.popup_ok').on('click', function () {
                                location.reload();
                            });
                            $('.loadingio-spinner-spinner-chyosfc7wi6').hide();
                            $('table.table-view-list.orders').css({ 'opacity': '1' });
                            popupOpenClose('.primer_popup');
                            $(document).mouseup(function (e) {
                                var container = $('.primer_popup > div');
                                if (!container.is(e.target) && container.has(e.target).length === 0) {
                                    document.location.reload();
                                }
                            });
                            setTimeout( function () {document.location.reload()}, 5000);
                        }
                    });
                } else {
                    return false;
                }
            });

             function popupOpenClose(popup) {
              if ($('.popup_wrapper').length == 0) {
                $(popup).wrapInner("<div class='popup_wrapper'></div>");
              }
              $(popup).show();

              $('.popup_wrapper').on('click', function(e) {
                if (e.target == this) {
                  if ($(popup).is(':visible')) {
                    $(popup).hide();
                    location.reload();
                  }
                }
              });

              $(popup).find('.popup_ok').on('click', function(e) {
                $(popup).hide();
                location.reload();
              });
            }

            $('#primer-download-order-json').on('click', function (e) {
                e.preventDefault();
                var find_field = $(this).prev();
                var input_field;
                if (find_field.is('input')) {
                    input_field = find_field;
                    var data_id = $(input_field).attr('data-parent_order');
                    download( '<?php echo esc_html(__('Send JSON for order ')) ?>' + data_id, $(input_field).val() );
                }

            });

            $('#primer-download-html-json').on('click', function (e) {
                e.preventDefault();
                var find_field = $(this).prev();
                var input_field;
                if (find_field.is('input')) {
                    input_field = find_field;
                    var data_id = $(input_field).attr('data-parent_order');
                    download( '<?php echo esc_html(__('Send HTML JSON for order ')) ?>' + data_id, $(input_field).val() );
                }

            });

		})(jQuery)
	</script>
<?php }

add_action('admin_footer', 'fetch_primer_script');


function check_zone_country($country) {
	$country_response = false;
	$eu_countries = array(
		"AT" => "Austria",
		"BE" => "Belgium",
        "BG" => "Bulgaria",
		"CY" => "Cyprus",
        "HR" => "Croatia",
        "CZ" => "Czechia",
        "DK" => "Denmark",
        "DK" => "Denmark",
		"EE" => "Estonia",
		"FI" => "Finland",
		"FR" => "France",
		"DE" => "Germany",
		"GR" => "Greece",
        "HU" => "Hungary",
		"IE" => "Ireland",
		"IT" => "Italy",
		"LV" => "Latvia",
		"LT" => "Lithuania",
		"LU" => "Luxembourg",
		"MT" => "Malta",
		"NL" => "Netherlands",
        "PL" => "Poland",
		"PT" => "Portugal",
        "RO" => "Romania",
		"SK" => "Slovak Republic",
		"SI" => "Slovenia",
		"ES" => "Spain",
		"SE" => "Sweden"
	);
		if (array_key_exists($country, $eu_countries)) {
		$country_response = true;
	}
	return $country_response;
}