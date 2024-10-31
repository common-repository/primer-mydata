<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

require_once PRIMER_PATH . 'views/get_receipt_list.php';


class PrimerReceipt extends WP_List_Table {

	function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Receipt', 'primer' ),
				'plural' => __( 'Receipts', 'primer' ),
				'ajax' => false,
			)
		);

		$this->prepare_items();

		add_action( 'wp_print_scripts', [ __CLASS__, '_list_table_css' ] );

	}

	function get_columns() {
		return array(
			'cb'		 	=> '<input type="checkbox" />',
			'receipt_id'		=> __( 'No.', 'primer' ),
            'invoice_type'      => __('Invoice Type', 'primer'),
			'receipt_date' 	=> __( 'Receipt Date', 'primer' ),
			'receipt_hour'	=> __( 'Hour', 'primer' ),
			'receipt_client'	=> __( 'Client', 'primer' ),
			//'receipt_product' => __( 'Products', 'primer' ),
			'receipt_price'	=> __( 'Total Price', 'primer' ),
			'receipt_status'	=> __( 'Receipt Status', 'primer' ),
			'receipt_error_status' => __( 'Errors', 'primer' ),
            'credit_receipt'        => __('Credit Receipt', 'primer' ),
            'cancelled_receipt'    => __('Cancelled Receipt', 'primer' )
		);
	}


	function get_sortable_columns() {
		return array();
	}


    /**
     * Rendering table with receipts to admin page.
     * Plus adding search box.
     *
     * @return void
     */
    public function display() {

        //	echo '<form id="wc-orders-filter" method="get" action="' . esc_url( get_admin_url( null, 'admin.php' ) ) . '">';
        $this->search_box( esc_html__( 'Search  by order id or number of invoice', 'primer' ), 'filter_action' );
        parent::display();
        //	echo '</form> </div>';
    }
	function column_default( $item, $column_name ) {
        if($column_name == 'credit_receipt' || $column_name == 'cancelled_receipt'){
            return $item[ $column_name ];
        }
        if($column_name == 'invoice_type'){
            $invoice_type_text = '';
            $licenses = get_option('primer_licenses');
            $order_customer_country = get_locale();
            $invoice_type = get_the_terms($item['receipt_id'], 'receipt_status');
            $invoice_type_slug = '';
            if (is_array($invoice_type)) {
                $invoice_type_slug = $invoice_type[0]->slug;
            }
            if($invoice_type_slug == 'credit-invoice' || $invoice_type_slug == 'credit-receipt'){
                $find_invoice_in_slug = $invoice_type_slug;
            }else{
                $invoice_type_name = explode('_', $invoice_type_slug);
                $find_invoice_in_slug = '';
                if(array_key_exists(1, $invoice_type_name)){
                    $find_invoice_in_slug = $invoice_type_name[1];
                }
            }
            if($order_customer_country == 'GR'){
                if ($find_invoice_in_slug == 'receipt') {
                    if($licenses['productKind'] == 'goods') {
                        $invoice_type_text = __('Απόδειξη Λιανικής', 'primer');
                    }else{
                        $invoice_type_text = __('Απόδειξη Παροχής Υπηρεσιών', 'primer');
                    }
                }
                if ($find_invoice_in_slug == 'invoice') {
                    if($licenses['productKind'] == 'goods') {
                        $invoice_type_text = __('Τιμολόγιο Πώλησης', 'primer');
                    }else{
                        $invoice_type_text = __('Τιμολόγιο Παροχής Υπηρεσιών', 'primer');
                    }
                }
                if($find_invoice_in_slug == 'credit-receipt'){
                    $invoice_type_text = __('Πιστωτικό Στοιχείο Λιανικής', 'primer');
                }
                if($find_invoice_in_slug == 'credit-invoice'){
                    $invoice_type_text = __('Πιστωτικό Τιμολόγιο Συσχετιζόμενο', 'primer');
                }
            }else {
                if ($find_invoice_in_slug == 'receipt') {
                    if($licenses['productKind'] == 'goods') {
                        $invoice_type_text = __('RETAIL RECEIPT', 'primer');
                    }else{
                        $invoice_type_text = __('PROOF OF SERVICE', 'primer');
                    }
                }
                if ($find_invoice_in_slug == 'invoice') {
                    if($licenses['productKind'] == 'goods') {
                        $invoice_type_text = __('SALE INVOICE', 'primer');
                    }else{
                        $invoice_type_text = __('INVOICE', 'primer');
                    }
                }
                if($find_invoice_in_slug == 'credit-receipt'){
                    $invoice_type_text = __('Credit Receipt', 'primer');
                }
                if($find_invoice_in_slug == 'credit-invoice'){
                    $invoice_type_text = __('Credit Invoice', 'primer');
                }
            }
            $new_url = get_permalink($item['receipt_id']) . '?receipt=view';
            if(!empty($new_url) && !empty($invoice_type_text)) {
                echo '<a href="' . esc_url($new_url) . '" target="_blank" class="order-view"><strong>' . esc_attr($invoice_type_text) . '</strong></a>';
            }else{
                echo '';
            }
        }
        $receipt_series = get_post_meta($item['receipt_id'], '_primer_receipt_series', true);
		$receipt_number = get_post_meta($item['receipt_id'], '_primer_receipt_number', true);
        if($receipt_series != 'EMPTY') {
            $receipt_numbering = $receipt_series. ' '.$receipt_number;
            }else{
            $receipt_numbering = $receipt_number;
        }
		if ($column_name == 'receipt_id') {
			if (!empty($item[ $column_name ])) {
				$new_url = '';
				$find_invoice_in_slug = '';
				$invoice_type = get_the_terms($item['receipt_id'], 'receipt_status');
				if (is_array($invoice_type)) {
					$invoice_type_slug = $invoice_type[0]->slug;
					$invoice_type_name = explode('_', $invoice_type_slug);
                    if(array_key_exists(1, $invoice_type_name)){
                        $find_invoice_in_slug = $invoice_type_name[1];
                    }
				}

				if ($find_invoice_in_slug == 'receipt') {
					$new_url = get_permalink($item['receipt_id']) . '?receipt=view';
				} else {
					$new_url = get_permalink($item['receipt_id']);
				}

				echo '<a href="' . esc_url( $new_url ) . '" target="_blank" class="order-view"><strong>' . esc_attr( $receipt_numbering ? $receipt_numbering : $item[ $column_name ] ) . '</strong></a>';
			} else {
				echo '';
			}
		} else {
			if ($column_name !== 'receipt_error_status') {
				$new_url = '';
				$find_invoice_in_slug = '';
				$invoice_type = get_the_terms($item['receipt_id'], 'receipt_status');
				if (is_array($invoice_type)) {
					$invoice_type_slug = $invoice_type[0]->slug;
					$invoice_type_name = explode('_', $invoice_type_slug);
                    if(array_key_exists(1, $invoice_type_name)){
					$find_invoice_in_slug = $invoice_type_name[1];
                        }
				}

				if ($find_invoice_in_slug == 'receipt') {
					$new_url = get_permalink($item['receipt_id']) . '?receipt=view';
				} else {
					$new_url = get_permalink($item['receipt_id']);
				}
				echo '<a href="' . esc_url( $new_url ) . '" target="_blank" class="order-view"><strong>' . esc_attr( array_key_exists($column_name, $item)  ? $item[ $column_name ] : '' ) . '</strong></a>';
			} else {
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
			}
		}

	}
	/**
	 * @var array
	 *
	 * Array contains slug columns that you want hidden
	 *
	 */
    private $hidden_columns = array(
        'credit_receipt',
        'cancelled_receipt'
    );


    /**
     * Columns Check box.
     *
     * @param $item
     * @return string|void
     */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="receipts[]" id="receipt_'.$item['receipt_id'].'" value="%s" />',
			$item['receipt_id']
		);
	}


	protected function get_bulk_actions() {
		return array();
	}


	function extra_tablenav( $which ){
		if ( $which !== 'bottom' ) {
			$primer_receipts = new PrimerReceiptList();
			$primer_receipts_customers = $primer_receipts->get_users_from_receipts();
			?>
			<div class="actions">
				<h2><?php _e('Filters', 'primer'); ?></h2>
				<h3><?php _e('Date Range:', 'primer'); ?></h3>
				<div class="filter_blocks_wrapper">
					<div class="left_wrap">
						<div class="filter_block">
							<label for="primer_receipt_year" style="float: left;"><?php _e('Year: ', 'primer'); ?></label>
							<select name="primer_receipt_year" id="primer_receipt_year">
								<?php
								$current_year = date('Y');
								$primer_order_year = isset($_GET['primer_receipt_year']) ? sanitize_text_field($_GET['primer_receipt_year']) : $current_year;
								$year_to = date('Y');
                                $year_from = 2021;
								$range_years = range($year_from, $year_to);
								foreach ( $range_years as $range_year ) { ?>
									<option value="<?php echo esc_attr($range_year); ?>" <?php selected($range_year, $primer_order_year); ?>><?php echo esc_attr($range_year); ?></option>
								<?php }
								?>
							</select>
						</div>
						<div class="filter_block">
							<label for="receipt_date_from">
								<?php _e('From: ', 'primer'); ?></label>
							<input type="text" id="receipt_date_from" name="receipt_date_from" placeholder="Date From" value="" />
							<label for="receipt_date_to">
								<?php _e('To: ', 'primer'); ?></label>
							<input type="text" id="receipt_date_to" name="receipt_date_to" placeholder="Date To" value="" />
						</div>
						<div class="filter_block">
						<label for="primer_receipt_client" style="float: left;"><?php _e('Client: ', 'primer'); ?></label>
							<select name="primer_receipt_client" id="primer_receipt_client" data-placeholder="<?php _e('Select clients', 'primer'); ?>">
								<option value=""></option>
								<?php
								$primer_receipts_customers = array_unique($primer_receipts_customers, SORT_REGULAR);
								$get_customer = isset($_GET['primer_receipt_client']) ? sanitize_text_field($_GET['primer_receipt_client']) : '';
								foreach ( $primer_receipts_customers as $receipt_customer ) {
									if ( $receipt_customer['receipt_client_id'] ) { ?>
										<option value="<?php echo esc_attr($receipt_customer['receipt_client']); ?>" <?php selected($get_customer, $receipt_customer['receipt_client']); ?>><?php echo esc_attr($receipt_customer['receipt_client']); ?></option>
									<?php } else { ?>
										<option value="<?php echo esc_attr($receipt_customer['receipt_client']); ?>" <?php selected($get_customer, $receipt_customer['receipt_client']); ?>><?php _e( 'Guest client', 'primer' ); ?></option>
									<?php }
								} ?>
							</select>
					</div>
					</div>
					<div class="right_wrap">
						<div class="filter_block">
							<label for="primer_receipt_status" style="float: left;"><?php _e('Receipt Status: ', 'primer'); ?></label>
							<select name="primer_receipt_status" title="<?php _e('Select receipt status', 'primer'); ?>" id="primer_receipt_status">
								<?php
								//								$status_of_orders = wc_get_order_statuses();
								$get_status = isset($_GET['primer_receipt_status']) ? sanitize_text_field($_GET['primer_receipt_status']) : '';
								$status_of_receipts = array(
									'issued' => 'Issued',
									'not_issued' => 'Failed to issue'
								);

								foreach ( $status_of_receipts as $status_k => $status_value ) { ?>
									<option value="<?php echo esc_attr($status_k); ?>" <?php selected($status_k, $get_status); ?>><?php echo esc_attr($status_value); ?></option>
								<?php }
								?>
							</select>
						</div>
                        <div class="filter_block">
                            <label for="primer_receipt_type" style="float: left;"><?php _e('Invoice Type: ', 'primer'); ?></label>
                            <select name="primer_receipt_type" title="<?php _e('Select invoice type', 'primer'); ?>" id="primer_receipt_type">
                                <?php
                                $get_type = isset($_GET['primer_receipt_type']) ? sanitize_text_field($_GET['primer_receipt_type']) : '';
                                $type_of_receipts = array(
                                    '' => 'All',
                                    'greek_receipt' => 'Receipt',
                                    'english_receipt' => 'Receipt outside Greece',
                                    'greek_invoice' => 'Invoice',
                                    'english_invoice' => 'Invoice outside Greece',
                                    'credit-receipt' => 'Credit Receipt',
                                    'credit-invoice' => 'Credit Invoice'
                                );

                                foreach ( $type_of_receipts as $status_k => $status_value ) { ?>
                                    <option value="<?php echo esc_attr($status_k); ?>" <?php selected($status_k, $get_type); ?>><?php echo esc_attr($status_value); ?></option>
                                <?php }
                                ?>
                            </select>
                        </div>

                        <div class="apply_btn"><input type="submit" id="filter_action_receipt" class="button" name="filter_action" value="<?php _e('Apply filter', 'primer'); ?>" /></div>
					</div>

				</div>
			</div>

			<div class="loadingio-spinner-spinner-chyosfc7wi6" id="mySpinner"><div class="ldio-drsjmtezgls"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>
			<?php
			$receipts_dates = $primer_receipts->get_dates_from_receipts();
			if (!empty($receipts_dates)) {
				$min_receipt_date = min($receipts_dates);
				$max_receipt_date = max($receipts_dates);
			}
			$formatted_min_receipt_date = date('m/d/Y', $min_receipt_date);
			$formatted_max_receipt_date = date('m/d/Y', $max_receipt_date);
			?>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta2/dist/js/bootstrap-select.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/select-woo@1.0.1/dist/js/selectWoo.min.js"></script>
			<script>
                jQuery(document).ready(function ($) {

                   $.fn.selectpicker.Constructor.BootstrapVersion = '4';
                   $('.selectpicker').selectpicker();

                    $('#primer_receipt_client').selectWoo({
                        allowClear:  true,
                       placeholder: $( this ).data( 'placeholder' )
                    });

                    var select_year = $('select[name="primer_receipt_year"]').val();

                    var max_year = 2035;
                    var diff_year = 0;
                    var min_receipt_date = "<?php echo esc_attr($formatted_min_receipt_date); ?>";
                    var max_receipt_date = "<?php echo esc_attr($formatted_max_receipt_date); ?>";

                    var date_from = $('input[name="receipt_date_from"]'),
                        date_to = $('input[name="receipt_date_to"]');
                    $('input[name="receipt_date_from"], input[name="receipt_date_to"]').datepicker({
                        changeMonth: true,
                        changeYear: true,
                        dateFormat: "yy-mm-dd",
                        yearRange: "2021:2035"
                    });
                    $('input[name="receipt_date_from"]').datepicker("option", "minDate", new Date(min_receipt_date));
                  //  $('input[name="receipt_date_to"]').datepicker("option", "minDate", new Date(max_receipt_date));
                    <?php if (isset($_GET['receipt_date_from']) && !empty($_GET['receipt_date_from'])) { ?>
                    $('input[name="receipt_date_from"]').datepicker("setDate", new Date("<?php echo esc_html_e($_GET['receipt_date_from'], 'primer'); ?>"));
                    console.log(<?php echo esc_html_e($_GET['receipt_date_from'], 'primer'); ?>);
                    <?php } ?>

                    <?php if (isset($_GET['receipt_date_to']) && !empty($_GET['receipt_date_to'])) { ?>
                    $('input[name="receipt_date_to"]').datepicker("setDate", new Date("<?php echo esc_html_e($_GET['receipt_date_to'], 'primer'); ?>"));
                    <?php } ?>


                    $('select[name="primer_receipt_year"]').on('change', function () {
                        select_year = $(this).val();
                        $('input[name="receipt_date_from"], input[name="receipt_date_to"]').datepicker("destroy");
                        $('input[name="receipt_date_from"], input[name="receipt_date_to"]').datepicker({
                            changeMonth: true,
                            changeYear: true,
                            dateFormat: "yy-mm-dd",
                            yearRange: `${select_year}:${max_year}`,
                        });
                        var currentDate = new Date();
                        var currentDay = currentDate.getDate();
                        var currentMonth = currentDate.getMonth()+1;
                        var set_current_date = currentMonth + '/' + currentDay + '/' + select_year;
                        $('input[name="receipt_date_from"]').datepicker("setDate", new Date(set_current_date));
                        $('input[name="order_date_to"]').datepicker( 'option', 'minDate', date_from.val() );
                    });

                    // the rest part of the script prevents from choosing incorrect date interval
                    date_from.on( 'change', function() {
                        date_to.datepicker( 'option', 'minDate', date_from.val() );
                    });

                    date_to.on('change', function () {
                        date_to.datepicker('option', 'maxDate', date_to.val());
                    });

                    var atLeastOneIsChecked = $('input[name="receipts[]"]:checked').length > 0;
                    if (atLeastOneIsChecked) {
                        $('.convert_receipts input[type="submit"]').removeAttr('disabled');
                        $('.resend_receipt_to_customer').removeAttr('disabled');
                        $('.cancel_receipt').removeAttr('disabled');
                    }
                    function checker() {
                        var length_inputs = $('input[name="receipts[]"]').length;
                        var trues = new Array();
                        $('input[name="receipts[]"]').each(function (i, el) {

                            if ($(el).prop('checked') == true || $(el).is(':checked') == true) {
                                $('.convert_receipts input[type="submit"]').removeAttr('disabled');
                                $('.resend_receipt_to_customer').removeAttr('disabled');
                                $('.cancel_receipt').removeAttr('disabled');
                                trues.push($(el));
                            }
                        })
                        if (trues.length <= 0) {
                            $('.convert_receipts input[type="submit"]').attr('disabled', true);
                            $('.resend_receipt_to_customer').attr('disabled', true);
                            $('.cancel_receipt').removeAttr('disabled');
                        }
                    }

                    $('.wp-list-table #cb input:checkbox').on('click', function () {
                        checker();
                        if ($(this).is(':checked')) {
                            $('.convert_receipts input[type="submit"]').removeAttr('disabled');
                            $('.resend_receipt_to_customer').removeAttr('disabled');
                            $('.cancel_receipt').removeAttr('disabled');
                        } else {
                            $('.convert_receipts input[type="submit"]').attr('disabled', true);
                            $('.resend_receipt_to_customer').attr('disabled', true);
                            $('.cancel_receipt').removeAttr('disabled');
                        }
                    });
                    $('.wp-list-table input[name="receipts[]"]').on('click', function () {
                        checker();
                    });

                    function popupOpenClose(popup) {
                        if ($('.popup_wrapper').length == 0) {
                            $(popup).wrapInner("<div class='popup_wrapper'></div>")
                        }
                        $(popup).show();

                        $(popup).click(function (e) {
                            if (e.target == this) {
                                if ($(popup).is(':visible')) {
                                    $(popup).hide();
								}
							}
						})

                    }

                    $('#tables-receipt-filter .resend_receipt_to_customer').on('click', function (e) {
                        e.preventDefault();
                        $('.resend_receipt_to_customer').attr('disabled', true);
                        var checked_receipts_data = $('#tables-receipt-filter input[name="receipts[]"]').serialize();
                        console.log(checked_receipts_data);

                        $.ajax({
                            url: primer.ajax_url,
                            data: 'action=primer_resend_receipt_to_customer&'+checked_receipts_data,
                            type: 'post',
                            dataType: 'json',
                            beforeSend: function () {
                                var $table = $('table.table-view-list.receipts');
                                $table.css({ 'opacity': '0.5' });

                                // Get the z-index of the table-view-list and add 1 to make sure the spinner is on top
                                var tableZIndex = parseInt($table.css('z-index'), 10) || 0;
                                $('.loadingio-spinner-spinner-chyosfc7wi6').css({ 'z-index': tableZIndex + 1 });
                                $('.loadingio-spinner-spinner-chyosfc7wi6').show();
                            },

                            error: function(response){
                                console.log(response);
                            },
                            success: function (response) {
                                if (response.success === 'true' && response.response !== false) {
                                    console.log(response.response_wrap);
                                    setTimeout(function () {
                                        $('.loadingio-spinner-spinner-chyosfc7wi6').hide();
                                        $('table.table-view-list.receipts').css({'opacity': '1'});
                                        $('table.table-view-list.receipts').append(response.response_wrap);
                                        popupOpenClose('.primer_popup');
                                    }, 1000);
                                    setTimeout(function () {
                                        document.location.reload();
                                    }, 1700)
                                }
                            }
                        })

                    })
                    function check_exist_receipts(receipts) {
                        var receipt_arr = new Array();
                        $(receipts).each(function (i, el) {
                            var tr_parent = $(el).parents('tr');
                            var credit_receipt = tr_parent.find('td.credit_receipt');
                            var cancelled_receipt = tr_parent.find('td.cancelled_receipt');
                            var issued_receipt = tr_parent.find('td.receipt_status');
                            if (credit_receipt) {
                                var credit_status = credit_receipt.text();
                            }
                            if (cancelled_receipt) {
                                var cancelled_status = cancelled_receipt.text();
                            }
                            if (issued_receipt) {
                                var receipt_status = issued_receipt.text();
                            }

                            if (credit_status !== '' || cancelled_status !== '' || receipt_status === 'Not Issued') {
                                $(el).prop('checked', false);
                            }
                            var receipt_id = $(el).val();
                            if (receipt_id) {
                                receipt_arr.push(receipt_id);
                            }
                            console.log(issued_receipt);
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
                        //$timeout_check = $mydata_options['timeout_check'];
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
                     /*   else if(stop_conversion === 'stop'){
                            valid = false;
                            alert('Cound not connect to AADE for more than 48 hours.Please convert the failed orders first to proceed with further conversion.');
                            $('.submit_convert_orders').attr('disabled', true);
                        } */
                        else {
                            valid = true;
                        }
                        return valid;
                    }
                    $('#tables-receipt-filter .cancel_receipt').on('click', function (e) {
                        e.preventDefault();
                        $('.cancel_receipt').attr('disabled', true);
                        check_exist_receipts($('input[name="receipts[]"]:checked'));
                        console.log(validation);
                        var checked_receipts_data = $('#tables-receipt-filter input[name="receipts[]"]').serialize();
                        var count_orders = $('input[name="receipts[]"]:checked').length;
                        var receipt_word = count_orders == 1 ? 'receipt' : 'receipts';
                        var confirmation = confirm('You are about to cancel ' + count_orders + ' ' + receipt_word + '. Are you sure?');
                        var validation = check_request_receipts($('input[name="receipts[]"]:checked'));
                        console.log(checked_receipts_data);

                        if (confirmation === true && count_orders > 0 && validation === true) {
                            $.ajax({
                                url: primer.ajax_url,
                                data: 'action=primer_cancel_invoice&' + checked_receipts_data,
                                type: 'POST',
                                beforeSend: function () {
                                    var table = $('table.table-view-list.receipts')
                                    table.css({'opacity': '0.5'});
                                    var tableZIndex = parseInt(table.css('z-index'), 10) || 0;
                                    $('.loadingio-spinner-spinner-chyosfc7wi6').css({ 'z-index': tableZIndex + 1 });
                                    $('.loadingio-spinner-spinner-chyosfc7wi6').show();
                                },
                                success: function (response) {

                                    //var response_wrap = '<div class="primer_popup popup_success">' + '<h3>Orders converted!!</h3>' + '<br>' + '<br>' + '<br>' + '<br>' + '<button type="button" class="popup_ok button button-primary">OK</button>' + '</div>';

                                    var parsedResponse = JSON.parse(response);
                                    console.log(parsedResponse);
                                    var response_data = parsedResponse.data;

                                    $('#wpbody-content').prepend(response_data);
                                        $('.loadingio-spinner-spinner-chyosfc7wi6').hide();
                                        $('table.table-view-list.receipts').css({'opacity': '1'});
                                        popupOpenClose('.primer_popup');
                                        // Add event listener to the "OK" button
                                        $('.popup_ok').on('click', function () {
                                            location.reload();
                                        });
                                        popupOpenClose('.primer_popup');
                                        $(document).mouseup(function (e) {
                                            var container = $('.primer_popup > div');
                                            if (!container.is(e.target) && container.has(e.target).length === 0) {
                                                document.location.reload();
                                            }
                                        });
                                    setTimeout( function () {document.location.reload()}, 5000);
                                },
                                error: function (error) {
                                    console.log(error);
                                }
                            });
                        }
                    });


                    $('#tables-receipt-filter #zip_load').on('click', function (e) {
                        e.preventDefault();
                        $('#tables-receipt-filter #zip_load').attr('disabled', true);
                        dataObj = new Array();
                        var dat = $('#tables-receipt-filter').serializeArray();
                        $(dat).each(function (i, el) {
                            if (el.name == 'receipts[]') {
                                dataObj.push(el.value);
                            }
                        });
                        console.log('edw');
                        console.log(dataObj);
                        var datas = {
                            'action': 'primer_export_receipt_to_html',
                            'page_id': dataObj.join(', '),
                        }

                        $('.download-btn').addClass('hide');

                        $.ajax({
                            url: primer.ajax_url,
                            data: datas,
                            type: 'post',
                            dataType: 'json',
                            beforeSend: function(){
                                var table = $('table.table-view-list.receipts');
                                table.css({'opacity': '0.5'});
                                // Get the z-index of the table-view-list and add 1 to make sure the spinner is on top
                                var tableZIndex = parseInt(table.css('z-index'), 10) || 0;
                                $('.loadingio-spinner-spinner-chyosfc7wi6').css({'z-index': tableZIndex + 1});
                                $('.loadingio-spinner-spinner-chyosfc7wi6').show();
                            },
                            success: function (r) {
                                if (r.success == 'true') {
                                    if (r.response) {
                                        console.log(r.response);
                                        setTimeout(function () {
                                            $('#zip_load').hide();
                                            $('.download-btn').attr('href', r.response).removeClass('hide');
                                            $('.loadingio-spinner-spinner-chyosfc7wi6').hide();
                                            $('table.table-view-list.receipts').css({'opacity': '1'});
                                            $('.download-btn').get(0).click();
                                            $('input[name="receipts[]"]:checked').prop('checked', false);
                                            $('#cb-select-all-1').prop('checked', false);
                                            $('#cb-select-all-2').prop('checked', false);

                                            // Re-enable the button for future clicks
                                            $('#tables-receipt-filter #zip_load').attr('disabled', false);

                                            // Reset the state of the download button
                                            $('.download-btn').addClass('hide');
                                            $('#zip_load').attr('disabled', false).show();
                                        }, 1000);
                                    }
                                }
                            },
                        });
                    });


                    $('#filter_action_receipt').on('click', function() {

                        $('#current-page-selector').val('1');


                    });
                });
			</script>
		<?php } ?>
	<?php
	}


	function prepare_items() {
		$per_page = 20;
        $current_page = $this->get_pagenum();
		$get_total_receipts = new PrimerReceiptList();

		if (isset($_GET['primer_receipt_status']) || isset($_GET['primer_receipt_client']) || isset($_GET['primer_receipt_type']) || isset($_GET['receipt_date_from']) || isset($_GET['receipt_date_to']) || (isset($_GET['s']) && !empty($_GET['s']) )) {
            $get_receipts_list = $get_total_receipts->get_with_params($current_page, $_GET['receipt_date_from'], $_GET['receipt_date_to'], $_GET['primer_receipt_client'], $_GET['primer_receipt_status'], $_GET['primer_receipt_type']);
            $total_items = $get_receipts_list['totals']['totals'];
        } else {
			$get_receipts_list = $get_total_receipts->get($current_page);
            $total_items = array_sum( (array) wp_count_posts( 'primer_receipt' ) );
		}

		$columns  = $this->get_columns();
        $hidden   = $this->hidden_columns;
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $get_receipts_list;

		$data = $this->items;

		/**
		 * Get current page calling get_pagenum method
		 */
        //$total_items = array_sum( (array) wp_count_posts( 'primer_receipt' ) );
		$this->items = $data;
		/**
		 * Call to _set_pagination_args method for informations about
		 * total items, items for page, total pages and ordering
		 */
		$this->set_pagination_args(
			array(
				'total_items'	=> $total_items,
				'per_page'	    => $per_page,
				'total_pages'	=> ceil( $total_items / $per_page ),
			)
		);
	}


	function no_items() {
		_e( 'No receipts found.', 'primer' );
	}


	function process_bulk_action() {
		//Detect when a bulk action is being triggered... then perform the action.

		$receipts = isset( $_REQUEST['receipts'] ) ? $_REQUEST['receipts'] : array();
		$receipts = array_map( 'sanitize_text_field', $receipts );

		$current_action = $this->current_action();
		if ( ! empty( $current_action ) ) {
			//Bulk operation action. Lets make sure multiple records were selected before going ahead.
			if ( empty( $receipts ) ) {
				echo '<div id="message" class="error"><p>Error! You need to select multiple records to perform a bulk action!</p></div>';
				return;
			}
		} else {
			// No bulk operation.
			return;
		}

	}


	function show_all_receipts() {
		ob_start();
		$status = filter_input( INPUT_GET, 'status' );
		include_once PRIMER_PATH . 'views/admin_receipt_list.php';
		$output = ob_get_clean();
		return $output;
	}


	function handle_main_primer_receipt_admin_menu() {
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
		 <?php } elseif ($_GET['page'] === 'primer_receipts') {
		 	do_action( 'primer_menu_body_' . $action );

			//Allows an addon to completely override the body section of the members admin menu for a given action.
			$output = apply_filters( 'primer_menu_body_override', '', $action );
			if ( ! empty( $output ) ) {
				//An addon has overriden the body of this page for the given action. So no need to do anything in core.
                $allowed_html = wp_kses_allowed_html();
                echo wp_kses($output, $allowed_html);
				echo '</div>'; //<!-- end of wrap -->
				return;
			}
		 } ?>

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

