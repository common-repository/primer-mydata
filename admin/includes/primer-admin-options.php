<?php

//  called from WordPress and not another directly.
if ( ! defined('ABSPATH') ) { exit; }

require_once PRIMER_PATH . 'admin/includes/primer-admin-table.php';
require_once PRIMER_PATH . 'includes/class-primer-smtp.php';
require_once ABSPATH . '/wp-admin/includes/update.php';
require_once PRIMER_PATH. 'admin/includes/my_data_json.php';

// reference the Dompdf namespace
use PrimerDompdf\Dompdf;
use PrimerDompdf\Options;


class Primer_Options {

	/**
	 * Default Option key
	 * @var string
	 */
	private $key = 'primer_options';

	/**
	 * Array of metaboxes/fields
	 * @var array
	 */
	public $option_metabox = array();

	/**
	 * Options Tab Pages
	 * @var array
	 */
	public $options_pages = array();

	/**
	 * Options Page title
	 * @var string
	 */
	protected $menu_title = '';



	public function __construct() {
		add_action('admin_menu', array(&$this, 'menu'));
		add_action('wp_print_scripts', array(&$this, 'data_include_script'));
        add_action('wp_ajax_create_primer_the_zip_file', array(&$this, 'create_primer_the_zip_file'));
		add_action('wp_ajax_primer_export_receipt_to_html', array(&$this, 'primer_export_receipt_to_html'));
		add_action('wp_ajax_primer_resend_receipt_to_customer', array(&$this, 'primer_resend_receipt_to_customer'));
        add_action('wp_ajax_primer_cancel_invoice', array(&$this, 'primer_cancel_invoice'));
		add_action('wp_ajax_primer_smtp_settings', array(&$this, 'primer_smtp_settings'));
		add_action('wp_ajax_primer_system_settings', array(&$this, 'primer_system_settings'));
		add_action('wp_ajax_primer_insert_license', array(&$this, 'primer_insert_license'));
        add_action('wp_ajax_first_time_act', array(&$this, 'first_time_act'));
        add_action('wp_ajax_primer_get_series', array(&$this, 'primer_get_series'));
        add_action('wp_ajax_company_activation_call', array(&$this, 'company_activation_call'));
        add_action('wp_ajax_company_vat_call', array(&$this, 'company_vat_call'));
        add_action('wp_ajax_change_subsidiary', array(&$this, 'change_subsidiary'));
		add_action('wp_ajax_primer_license_remaining', array(&$this, 'primer_license_remaining'));
		add_action('wp_ajax_primer_user_picture_upload', array(&$this, 'primer_user_picture_upload'));
        add_action('cmb2_render_email_wildcards', array($this, 'cmb2_render_email_wildcards_field_callback'), 10, 5);
        add_action('wp_ajax_primer_export_receipt_to_html', 'primer_export_receipt_to_html');
        add_action('wp_ajax_nopriv_primer_export_receipt_to_html', 'primer_export_receipt_to_html');
        $this->menu_title = __( 'Primer MyData', 'primer' );
	}

    /**
     * Initializes the plugin by registering the settings for each option tab.
     *
     */
	public function init() {
		$option_tabs = self::primer_option_fields();
		foreach ($option_tabs as $index => $option_tab) {
			register_setting( $option_tab['id'], $option_tab['id'] );
		}
	}

	public function menu() {
		$menu_parent_slug = 'primer_receipts';
		$option_tabs = self::primer_option_fields();

		$primer_license_data = get_option('primer_licenses');
		if (is_array($primer_license_data) && !array_key_exists('mistake_license', $primer_license_data)) {
			add_menu_page(__('Primer Receipts', 'primer'), __('Primer Receipts', 'primer'), 'manage_options', 'wp_ajax_list_order', array(&$this, "admin_page_display"), 'dashicons-printer');
			add_submenu_page('wp_ajax_list_order', __('Settings', 'primer'), __('Settings', 'primer'), 'manage_options', 'primer_settings', array(&$this, "admin_settings_page_display"));

			$this->options_pages[] = add_submenu_page('wp_ajax_list_order', __('License and General Settings', 'primer'), __('License and General Settings', 'primer'), 'manage_options', 'primer_licenses', array(&$this, "admin_settings_page_display"));
			$this->options_pages[] = add_submenu_page(null, $this->menu_title, 'General Settings', 'manage_options', 'primer_generals', array( $this, 'admin_settings_page_display' ));
			$this->options_pages[] = add_submenu_page( null,  $this->menu_title, 'MyData Settings', 'manage_options', 'primer_mydata', array( $this, 'admin_settings_page_display' ));
			$this->options_pages[] = add_submenu_page( null,  $this->menu_title, 'Automation Settings', 'manage_options', 'primer_automation', array( $this, 'admin_settings_page_display' ));
			$this->options_pages[] = add_submenu_page( null,  $this->menu_title, 'Email Settings', 'manage_options', 'primer_email', array( $this, 'admin_settings_page_display' ));
		} else {
			if (is_array($primer_license_data) && $primer_license_data['mistake_license'] == 'fail') {
				add_menu_page(__('Primer Receipts', 'primer'), __('Primer Receipts', 'primer'), 'manage_options', 'wp_ajax_list_order', array(&$this, "admin_page_display"), 'dashicons-printer');
				$this->options_pages[] = add_submenu_page('wp_ajax_list_order', __('License and General Settings', 'primer'), __('License and General Settings', 'primer'), 'manage_options', 'primer_licenses', array(&$this, "admin_settings_page_display"));
				$this->options_pages[] = add_submenu_page(null, $this->menu_title, 'General Settings', 'manage_options', 'primer_generals', array( $this, 'admin_settings_page_display' ));
				$this->options_pages[] = add_submenu_page( null,  $this->menu_title, 'MyData Settings', 'manage_options', 'primer_mydata', array( $this, 'admin_settings_page_display' ));
				$this->options_pages[] = add_submenu_page( null,  $this->menu_title, 'Automation Settings', 'manage_options', 'primer_automation', array( $this, 'admin_settings_page_display' ));
				$this->options_pages[] = add_submenu_page( null,  $this->menu_title, 'Email Settings', 'manage_options', 'primer_email', array( $this, 'admin_settings_page_display' ));
			} else {
				add_menu_page(__('Primer Receipts', 'primer'), __('Primer Receipts', 'primer'), 'manage_options', 'wp_ajax_list_order', array(&$this, "admin_page_display"), 'dashicons-printer');
				add_submenu_page('wp_ajax_list_order', __('Orders', 'primer'), __('Orders', 'primer'), 'manage_options', 'wp_ajax_list_order', array(&$this, "admin_page_display"));
				if (!empty($primer_license_data['wpModules']) && in_array(11, $primer_license_data['wpModules'])) {
                    add_submenu_page('wp_ajax_list_order', __('Receipts', 'primer'), __('Receipts', 'primer'), 'manage_options', 'primer_receipts', array(&$this, "admin_page_receipt_display"));
                }
				add_submenu_page(null, __('Receipts Logs', 'primer'), __('Receipts Logs', 'primer'), 'manage_options', 'primer_receipts_logs', array(&$this, "admin_page_receipt_log_display"));
				add_submenu_page(null, __('Automation Logs', 'primer'), __('Automation Logs', 'primer'), 'manage_options', 'primer_receipts_logs_automation', array(&$this, "admin_page_receipt_log_automation_display"));
				add_submenu_page('wp_ajax_list_order', __('Settings', 'primer'), __('Settings', 'primer'), 'manage_options', 'primer_settings', array(&$this, "admin_settings_page_display"));
				$this->options_pages[] = add_submenu_page('wp_ajax_list_order', __('License and General Settings', 'primer'), __('License and General Settings', 'primer'), 'manage_options', 'primer_licenses', array(&$this, "admin_settings_page_display"));
				$this->options_pages[] = add_submenu_page(null, $this->menu_title, 'General Settings', 'manage_options', 'primer_generals', array( $this, 'admin_settings_page_display' ));
                if (!empty($primer_license_data['wpModules']) && in_array(7, $primer_license_data['wpModules'])) {
                    $this->options_pages[] = add_submenu_page( null,  $this->menu_title, 'MyData Settings', 'manage_options', 'primer_mydata', array( $this, 'admin_settings_page_display' ));
                }

				if (!empty($primer_license_data['wpModules']) && in_array(10, $primer_license_data['wpModules'])) {
					$this->options_pages[] = add_submenu_page('wp_ajax_list_order', __('Export', 'primer'), __('Export', 'primer'), 'manage_options', 'primer_export', array(&$this, "admin_settings_page_display"));
				}

                if (!empty($primer_license_data['wpModules']) && in_array(5, $primer_license_data['wpModules'])) {
                    $this->options_pages[] = add_submenu_page( null,  $this->menu_title, 'Automation Settings', 'manage_options', 'primer_automation', array( $this, 'admin_settings_page_display' ));
                }

                if (!empty($primer_license_data['wpModules']) && in_array(6, $primer_license_data['wpModules'])) {
                    $this->options_pages[] = add_submenu_page( null,  $this->menu_title, 'Email Settings', 'manage_options', 'primer_email', array( $this, 'admin_settings_page_display' ));
                }
			}
		}

		do_action('primer_after_main_admin_menu', $menu_parent_slug);

		// Include CMB CSS in the head to avoid FOUC
		foreach ( $this->options_pages as $page ) {
			add_action( "admin_print_styles-{$page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );
		}

	}

	/**
	 * Admin page markup. Mostly handled by CMB
	 * @since  0.1.0
	 */
	public function admin_settings_page_display() {
		global $pagenow;

		// check we are on the network settings page
		if( $pagenow != 'admin.php' ) {
			return;
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'primer_licenses' ) {
			$current_tab = 'licenses';
		} elseif ( isset( $_GET['page'] ) && $_GET['page'] === 'primer_export' ) {
			$current_tab = 'export';
		} else if ( isset( $_GET['page'] ) && $_GET['page'] === 'primer_instructions' ) {
            $current_tab = 'instructions';
        } else {
			$current_tab = empty( $_GET['tab'] ) ? 'mydata' : sanitize_title( $_GET['tab'] );
		}

		$option_tabs = self::primer_option_fields(); //get all option tabs
		$tab_forms = array();

		?>

		<div class="wrap cmb2_primer_options_page <?php echo esc_attr($this->key); ?>">

			<h2><?php esc_html_e( $this->menu_title, 'primer' ) ?></h2>

			<!-- Options Page Nav Tabs -->
			<h2 class="nav-tab-wrapper">
				<?php foreach ($option_tabs as $option_tab) :
					$tab_slug = $option_tab['id'];
					$nav_class = 'i18n-multilingual-display nav-tab';
					if ( $tab_slug === 'primer_'.$current_tab ) {
						$nav_class .= ' nav-tab-active'; //add active class to current tab
						$tab_forms[] = $option_tab; //add current tab to forms to be rendered
					}
					if ( $tab_slug === 'primer_licenses' ) {
						$admin_url = admin_url( 'admin.php?page='.$tab_slug );
					} else if ( $tab_slug === 'primer_export' ) {
						$admin_url = admin_url( 'admin.php?page='.$tab_slug );
					} else if ($tab_slug === 'primer_instructions') {
                        $admin_url = admin_url( 'admin.php?page='.$tab_slug );
                    } else {
						$admin_url = admin_url( 'admin.php?page=primer_settings&tab=' . str_replace( 'primer_', '', $tab_slug ) );
					}
					?>
					<a class="<?php echo esc_attr( $nav_class ); ?>" href="<?php echo $admin_url; ?>"><?php esc_attr_e( $option_tab['title'], 'primer' ); ?></a>
				<?php endforeach; ?>
			</h2>

			<div class="plugin_caption_version"><?php echo PRIMER_NAME . ' v'. PRIMER_VERSION; ?></div>

			<!-- End of Nav Tabs -->
			<?php foreach ($tab_forms as $tab_form) : //render all tab forms (normaly just 1 form) ?>
				<div id="<?php esc_attr_e($tab_form['id']); ?>" class="cmb-form group">
					<div class="metabox-holder">
						<div class="postbox">
							<h3 class="title"><?php esc_html_e($tab_form['title'], 'primer'); ?></h3>
							<div class="desc"><?php echo $tab_form['desc'] ?></div>
							<?php cmb2_metabox_form( $tab_form, $tab_form['id'] ); ?>
						</div>
					</div>
				</div>
			<?php endforeach;?>
		</div>
	<?php }

	/**
	 * Defines the theme option metabox and field configuration
	 * @since  0.1.0
	 * @return array
	 */
	public function primer_option_fields() {

		// Only need to initiate the array once per page-load
		if ( !empty( $this->option_metabox ) ) {
			return $this->option_metabox;
		}

		$prefix = 'primer_';
		$current_user = wp_get_current_user();

		$primer_license_data = get_option('primer_licenses');

		$general_tab = array();

		if (is_array($primer_license_data) && !array_key_exists('mistake_license', $primer_license_data)) {
			$general_tab['fields'][] = array(
				'name'		=> __( 'General Settings', 'primer' ),
				'desc'		=> '',
				'default'	=> '',
				'id'		=> 'title_generals_settings',
				'type'		=> 'title',
			);

			$general_tab['fields'][] = array(
				'name' => __('Activate this plugin with proper credentials', 'primer'),
				'desc' => __('For more information you can go to www.primer.gr.', 'primer'),
				'id' => 'general_settings_disable',
				'type' => 'title',
				'classes' => 'disable_functionality'
			);
		} else {
			if (is_array($primer_license_data) && $primer_license_data['mistake_license'] == 'fail') {
				$general_tab['fields'][] = array(
					'name'		=> __( 'General Settings', 'primer' ),
					'desc'		=> '',
					'default'	=> '',
					'id'		=> 'title_generals_settings',
					'type'		=> 'title',
				);

				$general_tab['fields'][] = array(
					'name' => __('Activate this plugin with proper credentials', 'primer'),
					'desc' => __('For more information you can go to www.primer.gr.', 'primer'),
					'id' => 'general_settings_disable',
					'type' => 'title',
					'classes' => 'disable_functionality'
				);
			} else {
				$general_tab['fields'][] = array(
					'name'		=> __( 'General Settings', 'primer' ),
					'desc'		=> '',
					'default'	=> '',
					'id'		=> 'title_generals_settings',
					'type'		=> 'title',
				);

				if(!empty($primer_license_data['wpModules']) && in_array(8, $primer_license_data['wpModules'])) {
                    $general_tab['fields'][] = array(
                        'name'		=> __( 'Enable receipt/invoice option in checkout', 'primer' ),
                        'desc'      => __( 'Enables the receipt/invoice option on checkout', 'primer' ),
                        'id'		=> 'primer_enable_invoice_in_checkout',
                        'type'		=> 'radio_inline',
                        'show_option_none' => false,
                        'options' 	=> array(
                            'off'	=> __('Hide', 'primer'),
                            'on'	=> __('Show', 'primer'),
                        ),
                        'default' => 'off'
                    );
                }

				$general_tab['fields'][] = array(
					'name'		=> __( 'Create/update new user on checkout', 'primer' ),
					'desc'      => __( 'Enables automatic creation of new user, or updating existing one in checkout', 'primer' ),
					'id'		=> 'primer_create_user_on_checkout',
					'type'		=> 'radio_inline',
					'options' 	=> array(
						'off'	=> __('Off', 'primer'),
						'on'	=> __('On', 'primer'),
					),
					'default' 	=> 'off',
				);
                $general_tab['fields'][] = array(
                    'name'		=> __( 'Enable Transmission Failure', 'primer' ),
                    'desc'      => __( 'Enables transmission failure procedure.If our servers or AADE is down the plugin prints an invoice without qr code,mark,uid,auth code and it turns on an automatic process which tries every 30 minutes to send the failed invoice. After the successful sending  the invoice is filled with the necessary information.', 'primer' ),
                    'id'		=> 'primer_cron_transmission_failure',
                    'type'		=> 'radio_inline',
                    'options' 	=> array(
                        'off'	=> __('Off', 'primer'),
                        'on'	=> __('On', 'primer'),
                    ),
                    'default' 	=> 'on'

                );
                $general_tab['fields'][] = array(
                    'name'		=> __( "Accept zero total value orders", 'primer' ),
                    'desc'		=> __( "If enabled the plugin will convert the orders that have 0 euros total value but the value send to AADE will be 0.02 euros(0.01 net value + 0.01 vat value), because AADE does not accept 0 values.", 'primer' ),
                    'id'		=> 'accept_zero_value_orders',
                    'type'		=> 'radio_inline',
                    'options' 	=> array(
                        'off'	=> __('Off', 'primer'),
                        'on'	=> __('On', 'primer'),
                    ),
                    'default' 	=> 'off'
                );

                $general_tab['fields'][] = array(
                    'name'		=> __( "Number of products on each page of the receipt", 'primer' ),
                    'desc'		=> __( "From here you can select how many products there will be on each page of the receipt-invoice", 'primer' ),
                    'id'		=> 'products_per_page_receipt',
                    'type'		=> 'select',
                    'options' 	=>
                        range(1, 20)
                    ,
                    'default' 	=> 4
                );
                $general_tab['fields'][] = array(
                    'name'      => __( "Enable display of product attributes and variations in the invoice " , 'primer'),
                    'desc'      => __( "From here you can choose if you want the attributes and variations of your products to appear on the invoice ", 'primer'),
                    'id'        => 'display_attr_var',
                    'type'      => 'radio_inline',
                    'options' 	=> array(
                        'off'	=> __('Off', 'primer'),
                        'on'	=> __('On', 'primer'),
                    ),
                    'default'   => 'off'
                );
			}
		}

		if (!empty($primer_license_data) && $primer_license_data['mistake_license'] !== 'fail') {
            $this->option_metabox[] = apply_filters( 'primer_generals_option_fields', array(
                'id'			=> $prefix . 'generals',
                'title'			=> __( 'General Settings', 'primer' ),
                'menu_title'	=> __( 'General Settings', 'primer' ),
                'desc'			=> '',
                'show_on'    	=> array( 'key' => 'options-page', 'value' => array( 'generals' ), ),
                'show_names' 	=> true,
                'fields'		=> $general_tab['fields'],
            ) );
        }


		$mydata_options = get_option('primer_mydata');
        $smtp_settings = get_option('primer_emails');
        $email_username = null;
        if(is_array($smtp_settings)) {
            if (array_key_exists('smtp_settings', $smtp_settings) && is_array($smtp_settings['smtp_settings'])) {
                $email_username = $smtp_settings['smtp_settings']['username'];
            }
        }
        if(!empty($email_username)){
            $default_smtp_type = 'other_smtp';
        }else{
            $default_smtp_type = 'wordpress_default';
        }
		$company_logo_id = '';
		$company_logo_src = '';
		$disable_upload = '';
//		$disable_upload_message = '';
		if (!empty($mydata_options)) {
			if (!empty($mydata_options['upload_img_id'])) {
				$company_logo_id = $mydata_options['upload_img_id'];
			}

			if (!empty($company_logo_id)) {
				$company_logo_src .= '<img src="'.wp_get_attachment_image_url($company_logo_id, array(350, 100)).'" alt="Company logo">';
			} else {
				$company_logo_src .= '';
			}
		}

		if (!empty($mydata_options['count_logo_change'])) {
			if ($mydata_options['count_logo_change'] >= 3) {
				$disable_upload = '';
//				$disable_upload = 'disabled hidden';
//				$disable_upload_message = '<p>'.__("Up to 3 logo changes are supported", 'primer').'</p>';
			} else {
				$disable_upload = '';
//				$disable_upload_message = '';
			}
		}

		$resend_class = 'hidden';
		if (!empty($mydata_options['last_request'])) {
			$resend_class = '';
		}
        //EDW DEN TO BAZW
//		$primer_options = new Primer_Options();
//		$generate_html_response = $primer_options->export_receipt_as_static_html_by_page_id(array(19421), '?type_logo=id');
		$primer_license_monthRemainingInvoices = '';
        $expire_mydata_date='';
        $primer_mydata_package = '';
		if(!empty($primer_license_data)) {
			if(!empty($primer_license_data['monthRemainingInvoices'])) {
				$primer_license_monthRemainingInvoices = $primer_license_data['monthRemainingInvoices'];
			}
            if(!empty($primer_license_data['monthlyInvoices'])) {
                $primer_mydata_package = $primer_license_data['monthlyInvoices'];
            }
            if(!empty($primer_license_data['endMonth'])) {
                $primer_license_endMonth = $primer_license_data['endMonth'];
            }
            if(!empty($primer_license_data['endYear'])) {
                $primer_license_endYear = $primer_license_data['endYear'];
            }
            if(!empty($primer_license_data['endDate'])) {
                $primer_license_end_date = $primer_license_data['endDate'];
            }
            if (!empty($primer_license_end_date)) {
                $expire_mydata_date = date('d F Y', strtotime("$primer_license_end_date"));
            }
		}

		$hidden_watermark_option = get_option('primer_order_delete_color65');
		if (!isset($hidden_watermark_option) || empty($hidden_watermark_option)) {
			$api_type = $mydata_options['mydata_api'];
			$put_type = '';
			if (empty($api_type) || $api_type == 'test_api') {
				$put_type = 'test_api';
			}
			update_option('primer_order_delete_color65', $put_type);
		}

		$mydata_tab = array();

		if (is_array($primer_license_data) && !array_key_exists('mistake_license', $primer_license_data)) {
			$mydata_tab['fields'][] = array(
				'name'		=> __( 'MyData Invoices', 'primer' ),
				'desc'		=> '',
				'default'	=> '',
				'id'		=> 'title_invoice_settings',
				'type'		=> 'title',
			);

			$mydata_tab['fields'][] = array(
				'name' => __('Activate this plugin with proper credentials', 'primer'),
				'desc' => __('For more information you can go to www.primer.gr.', 'primer'),
				'id' => 'mydata_settings_disable',
				'type' => 'title',
				'classes' => 'disable_functionality'
			);
		} else {
			if (is_array($primer_license_data) && ($primer_license_data['mistake_license'] == 'fail' || !in_array(7, $primer_license_data['wpModules']))) {
				$mydata_tab['fields'][] = array(
					'name'		=> __( 'MyData Invoices', 'primer' ),
					'desc'		=> '',
					'default'	=> '',
					'id'		=> 'title_invoice_settings',
					'type'		=> 'title',
				);

				$mydata_tab['fields'][] = array(
					'name' => __('Activate this plugin with proper credentials', 'primer'),
					'desc' => __('For more information you can go to www.primer.gr.', 'primer'),
					'id' => 'general_settings_disable',
					'type' => 'title',
					'classes' => 'disable_functionality'
				);
			} else {
				$mydata_tab['fields'][] = array(
					'name'		=> __( 'MyData Invoices', 'primer' ),
					'desc'		=> '',
					'default'	=> '',
					'id'		=> 'title_invoice_settings',
					'type'		=> 'title',
				);
                if(is_array($primer_license_data) && in_array(16, $primer_license_data['wpModules'])) {
                $mydata_tab['fields'][] = array(
                    'name'		=> __( 'MyData 0% VAT correlation', 'primer' ),
                    'desc'		=> 'Below are shown the tax classes that have 0% tax rate.You can choose between the exemption categories so you can convert orders that have products with 0% tax rate.',
                    'default'	=> '',
                    'id'		=> 'title_0%_correlation_settings',
                    'type'		=> 'title',
                );
                $tax_classes = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
                if ( !in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
                    array_unshift( $tax_classes, '' );
                }
                $Vat_exemption_categories = array();
                $Vat_exemption_categories[0] = 'Please select a tax exemption category';
                $Vat_exemption_categories[1] = 'Χωρίς ΦΠΑ – άρθρο 2 και 3 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[2] = 'Χωρίς ΦΠΑ - άρθρο 5 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[3] = 'Χωρίς ΦΠΑ - άρθρο 13 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[4] = 'Χωρίς ΦΠΑ - άρθρο 14 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[5] = 'Χωρίς ΦΠΑ - άρθρο 16 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[6] = 'Χωρίς ΦΠΑ - άρθρο 19 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[7] = 'Χωρίς ΦΠΑ - άρθρο 22 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[8] = 'Χωρίς ΦΠΑ - άρθρο 24 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[9] = 'Χωρίς ΦΠΑ - άρθρο 25 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[10] = 'Χωρίς ΦΠΑ - άρθρο 26 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[11] = 'Χωρίς ΦΠΑ - άρθρο 27 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[12] = 'Χωρίς ΦΠΑ - άρθρο 27 - Πλοία Ανοικτής Θαλάσσης του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[13] = 'Χωρίς ΦΠΑ - ΠΟΛ.1029/1995';
                $Vat_exemption_categories[14] = 'Χωρίς ΦΠΑ - άρθρο 27.1.γ - Πλοία Ανοικτής Θαλάσσης του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[15] = 'Χωρίς ΦΠΑ - άρθρο 28 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[16] = 'Χωρίς ΦΠΑ - άρθρο 39 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[17] = 'Χωρίς ΦΠΑ - άρθρο 39α του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[18] = 'Χωρίς ΦΠΑ - άρθρο 40 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[19] = 'Χωρίς ΦΠΑ - άρθρο 41 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[20] = 'Χωρίς ΦΠΑ - άρθρο 47 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[21] = 'ΦΠΑ εμπεριεχόμενος - άρθρο 43 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[22] = 'ΦΠΑ εμπεριεχόμενος - άρθρο 44 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[23] = 'ΦΠΑ εμπεριεχόμενος - άρθρο 45 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[24] = 'ΦΠΑ εμπεριεχόμενος - άρθρο 46 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[25] = 'Χωρίς ΦΠΑ - άρθρο 6 του Κώδικα ΦΠΑ';
                $Vat_exemption_categories[26] = 'Χωρίς ΦΠΑ - ΠΟΛ.1167/2015';
                $Vat_exemption_categories[27] = 'Λοιπές Εξαιρέσεις ΦΠΑ';
                    $counter_tax_class = 0;
                    foreach ($tax_classes as $tax_class) {
                        $inside_tax_rate = array();
                        $tax_rates = WC_Tax::get_rates_for_tax_class($tax_class);
                        $tax_arr = json_decode(json_encode($tax_rates), true);
                        foreach ($tax_arr as $tax) {
                            $inside_tax_rate[] = $tax['tax_rate'];
                        }
                        if (in_array('0.0000', $inside_tax_rate)) {
                            $counter_tax_class ++ ;
                            if ($tax_class == '') {
                                $tax_class = 'Standard rate';
                            }
                            $tax_class_id = str_replace(' ', '_', $tax_class);
                            $mydata_tab['fields'][] = array(
                                'name' => __($tax_class, 'primer'),
                                'desc' => '',
                                'id' => $tax_class_id,
                                'type' => 'select',
                                'options' => $Vat_exemption_categories
                            );
                        }
                    }
                    if($counter_tax_class == 0){
                        $mydata_tab['fields'][] = array(
                            'name'		=> __( 'No Tax classes found.', 'primer' ),
                            'desc'		=> __( 'No Tax classes found with 0% Tax rate.Please go to Woocommerce settings and configure a 0% tax rate in a tax class.', 'primer' ),
                            'default'	=> '',
                            'id'		=> 'title_no_tax_settings',
                            'type'		=> 'title',
                        );
                    }
                }

				$mydata_tab['fields'][] = array(
					'name'		=> __( "Enable/disable send", 'primer' ),
					'desc'		=> __( "Enable/disable send 'Product MyData Category Code' & 'Product Characterization Code'", 'primer' ),
					'id'		=> 'send_characterizations',
					'type'		=> 'checkbox',
					'default'	=> 'on'
				);

				$mydata_tab['fields'][] = array(
					'name'      => __( 'MyData Package:', 'primer' ),
					'desc'      => '',
					'id'        => 'mydata_package12',
					'type'      => 'text_small',
					'default'   => (int)$primer_mydata_package,
                    'attributes' => array(
                        'readonly' => 'readonly'
                    ),
                    'save_field' => false
				);

				$mydata_tab['fields'][] = array(
					'name'      => __( 'Remaining MyData Invoices for this month:', 'primer' ),
					'desc'      => '',
					'id'        => 'remaining_mydata_invoices_tab1',
					'type'      => 'text_small',
					'default' => (int)$primer_license_monthRemainingInvoices,
					'attributes' => array(
						'readonly' => 'readonly'
					),
                    'save_field' => false,
					'after_field' => '<a href="#" id="get_license_remaining" class="button-secondary">'.__('Get Remaining', 'primer').'</a>',
				);

				$mydata_tab['fields'][] = array(
					'name'      => __( 'Contract ends on:', 'primer' ),
					'desc'      => '',
					'default'   => "$expire_mydata_date",
					'id'        => 'mydata_contract_ends_on',
					'type'      => 'text_medium',
                    'attributes' => array(
                        'readonly' => 'readonly'
                    ),
                    'save_field' => false
				);

				$mydata_tab['fields'][] = array(
					'name'		=> __( 'Invoice numbering', 'primer' ),
					'desc'		=> '',
					'default'	=> '',
					'id'		=> 'invoice_numbering',
					'type'		=> 'title'
				);
				$mydata_tab['fields'][] = array(
					'name'		=> __( 'Type', 'primer' ),
					'desc'		=> '',
					'default'	=> __( 'Last number', 'primer' ),
					'id'		=> 'invoice_numbering_row',
					'type'		=> 'text',
					'attributes' => array(
						'readonly' => 'readonly'
					),
					'save_field' => false
				);
                $mydata_api_options = array();
                $series = array();
                $series['EMPTY']= __( 'EMPTY', 'primer' );
                $series['A']= 'A';
                $series['B']= 'Β';
                $series['C']= 'Γ';
                $series['D']= 'Δ';
                $series['E']= 'Ε';
                $series['Z']= 'Ζ';
                $series['H']= 'Η';
                $series['Q']= 'Θ';
                $series['I']= 'Ι';
                $series['K']= 'Κ';
                $series['L']= 'Λ';
                $series['M']= 'Μ';
                $series['N']= 'Ν';
                $series['J']= 'Ξ';
                $series['O']= 'Ο';
                $series['P']= 'Π';
                $series['R']= 'Ρ';
                $series['S']= 'Σ';
                $series['T']= 'Τ';
                $series['Y']= 'Υ';
                $series['F']= 'Φ';
                $series['X']= 'Χ';
                $series['W']= 'Ψ';
                $series['V']= 'Ω';

                if(!empty($primer_license_data['wpModules'])) {
                    if (in_array(12, $primer_license_data['wpModules'])) {
                        $mydata_api_options['test_api'] = __( 'Test API', 'primer' );
                    }
                }

                if(!empty($primer_license_data['wpModules'])) {
                    if (in_array(13, $primer_license_data['wpModules'])) {
                        $mydata_api_options['production_api'] = __( 'Production API', 'primer' );
                    }
                }
                //make different numberings according to api selection
                foreach (array_keys($series) as $serie) {
                    $mydata_tab['fields'][] = array(
                        'name' => __('Greek Receipt', 'primer'),
                        'desc' => '',
                        'default' => 1,
                        'id' => 'invoice_numbering_gr_'.$serie.'',
                        'type' => 'text_small',
                        'classes' => 'testing2'
                    );
                 }
                $mydata_tab['fields'][] = array(
                    'name' 		=> __( 'Series:', 'primer' ),
                    'desc'		=> '',
                    'id'		=> 'invoice_numbering_gr_series',
                    'type'		=> 'select',
                    'options'   => $series,
                    'classes'   => 'seriesclass'
                );
                //
                foreach (array_keys($series) as $serie) {
                    $mydata_tab['fields'][] = array(
                        'name' => __('Greek Invoice', 'primer'),
                        'desc' => '',
                        'default' => 1,
                        'id' => 'invoice_numbering_gi_'.$serie.'',
                        'type' => 'text_small',
                        'classes'   => 'testing2'

                    );
                }
                $mydata_tab['fields'][] = array(
                    'name' 		=> __( 'Series:', 'primer' ),
                    'desc'		=> '',
                    'id'		=> 'invoice_numbering_gi_series',
                    'type'		=> 'select',
                    'options'   => $series,
                    'classes'   => 'seriesclass'
                );
                foreach (array_keys($series) as $serie) {
                    $mydata_tab['fields'][] = array(
                        'name' => __('Invoice within E.U.', 'primer'),
                        'desc' => '',
                        'default' => 1,
                        'id' => 'invoice_numbering_within_'.$serie.'',
                        'type' => 'text_small',
                        'classes'   => 'testing2'
                    );
                }
                $mydata_tab['fields'][] = array(
                    'name' 		=> __( 'Series:', 'primer' ),
                    'desc'		=> '',
                    'id'		=> 'invoice_numbering_within_series',
                    'type'		=> 'select',
                    'options'   => $series,
                    'classes'   => 'seriesclass'
                );
                foreach (array_keys($series) as $serie) {
                    $mydata_tab['fields'][] = array(
                        'name' => __('Invoice outside E.U.', 'primer'),
                        'desc' => '',
                        'default' => 1,
                        'id' => 'invoice_numbering_outside_'.$serie.'',
                        'type' => 'text_small',
                        'classes' => 'testing2'
                    );
                }
                $mydata_tab['fields'][] = array(
                    'name' 		=> __( 'Series:', 'primer' ),
                    'desc'		=> '',
                    'id'		=> 'invoice_numbering_outside_series',
                    'type'		=> 'select',
                    'options'   => $series,
                    'classes'   => 'seriesclass'
                );
                foreach (array_keys($series) as $serie) {
                    $mydata_tab['fields'][] = array(
                        'name' => __('Credit Receipt', 'primer'),
                        'desc' => '',
                        'default' => 1,
                        'id' => 'credit_receipt_'.$serie.'',
                        'type' => 'text_small',
                        'classes' => 'testing2'
                    );
                }
                $mydata_tab['fields'][] = array(
                    'name' 		=> __( 'Series:', 'primer' ),
                    'desc'		=> '',
                    'id'		=> 'credit_receipt_series',
                    'type'		=> 'select',
                    'options'   => $series,
                    'classes'   => 'seriesclass',
                    'default'   => 'EMPTY'
                );
                foreach (array_keys($series) as $serie) {
                    $mydata_tab['fields'][] = array(
                        'name' => __('Credit Invoice', 'primer'),
                        'desc' => '',
                        'default' => 1,
                        'id' => 'credit_invoice_'.$serie.'',
                        'type' => 'text_small',
                        'classes' => 'testing2'
                    );
                }
                $mydata_tab['fields'][] = array(
                    'name' 		=> __( 'Series:', 'primer' ),
                    'desc'		=> '',
                    'id'		=> 'credit_invoice_series',
                    'type'		=> 'select',
                    'options'   => $series,
                    'classes'   => 'seriesclass',
                    'default'   => 'EMPTY'
                );
                foreach (array_keys($series) as $serie) {
                    $mydata_tab['fields'][] = array(
                        'name' => __('Greek Receipt(Test API)', 'primer'),
                        'desc' => '',
                        'default' => 1,
                        'id' => 'invoice_numbering_gr_'.$serie.'_test_api',
                        'type' => 'text_small',
                        'classes' => 'testing2'
                    );
                }
                $mydata_tab['fields'][] = array(
                    'name' 		=> __( 'Series:', 'primer' ),
                    'desc'		=> '',
                    'id'		=> 'invoice_numbering_gr_test_api_series',
                    'type'		=> 'select',
                    'options'   => $series,
                    'classes'   => 'seriesclass'
                );
                foreach (array_keys($series) as $serie) {
                    $mydata_tab['fields'][] = array(
                        'name' => __('Greek Invoice(Test API)', 'primer'),
                        'desc' => '',
                        'default' => 1,
                        'id' => 'invoice_numbering_gi_'.$serie.'_test_api',
                        'type' => 'text_small',
                        'classes' => 'testing2'
                    );
                }
                $mydata_tab['fields'][] = array(
                    'name' 		=> __( 'Series:', 'primer' ),
                    'desc'		=> '',
                    'id'		=> 'invoice_numbering_gi_test_api_series',
                    'type'		=> 'select',
                    'options'   => $series,
                    'classes'   => 'seriesclass'
                );
                foreach (array_keys($series) as $serie) {
                    $mydata_tab['fields'][] = array(
                        'name' => __('Invoice within E.U.(Test API)', 'primer'),
                        'desc' => '',
                        'default' => 1,
                        'id' => 'invoice_numbering_within_'.$serie.'_test_api',
                        'type' => 'text_small',
                        'classes' => 'testing2'
                    );
                }
                $mydata_tab['fields'][] = array(
                    'name' 		=> __( 'Series:', 'primer' ),
                    'desc'		=> '',
                    'id'		=> 'invoice_numbering_within_test_api_series',
                    'type'		=> 'select',
                    'options'   => $series,
                    'classes'   => 'seriesclass'
                );
                foreach (array_keys($series) as $serie) {
                    $mydata_tab['fields'][] = array(
                        'name' => __('Invoice outside E.U.(Test API)', 'primer'),
                        'desc' => '',
                        'default' => 1,
                        'id' => 'invoice_numbering_outside_'.$serie.'_test_api',
                        'type' => 'text_small',
                        'classes' => 'testing2'
                    );
                }
                $mydata_tab['fields'][] = array(
                    'name' 		=> __( 'Series:', 'primer' ),
                    'desc'		=> '',
                    'id'		=> 'invoice_numbering_outside_test_api_series',
                    'type'		=> 'select',
                    'options'   => $series,
                    'classes'   => 'seriesclass'
                );
                foreach (array_keys($series) as $serie) {
                    $mydata_tab['fields'][] = array(
                        'name' => __('Credit Receipt(Test API)', 'primer'),
                        'desc' => '',
                        'default' => 1,
                        'id' => 'credit_receipt_'.$serie.'_test_api',
                        'type' => 'text_small',
                        'classes' => 'testing2'
                    );
                }
                $mydata_tab['fields'][] = array(
                    'name' 		=> __( 'Series:', 'primer' ),
                    'desc'		=> '',
                    'id'		=> 'credit_receipt_test_api_series',
                    'type'		=> 'select',
                    'options'   => $series,
                    'classes'   => 'seriesclass',
                    'default'   => 'EMPTY'
                );
                foreach (array_keys($series) as $serie) {
                    $mydata_tab['fields'][] = array(
                        'name' => __('Credit Invoice(Test API)', 'primer'),
                        'desc' => '',
                        'default' => 1,
                        'id' => 'credit_invoice_'.$serie.'_test_api',
                        'type' => 'text_small',
                        'classes' => 'testing2'
                    );
                }
                $mydata_tab['fields'][] = array(
                    'name' 		=> __( 'Series:', 'primer' ),
                    'desc'		=> '',
                    'id'		=> 'credit_invoice_test_api_series',
                    'type'		=> 'select',
                    'options'   => $series,
                    'classes'   => 'seriesclass',
                    'default'   => 'EMPTY'
                );
				$mydata_tab['fields'][] = array(
					'name' 		=> __( 'MyData API:', 'primer' ),
					'desc'		=> '',
					'id'		=> 'mydata_api',
					'type'		=> 'select',
                    'options'   => $mydata_api_options,
					'default' => 'test_api'
				);
				$mydata_tab['fields'][] = array(
					'name'      => __( 'Resend last HTML document', 'primer' ),
					'desc'      => '',
					'default'   => '',
					'id'        => 'resend_last_html_doc',
					'type'      => 'title',
					'after_field'		=> '<a href="#" class="button '.$resend_class.'">'.__('Resend', 'primer').'</a>',
				);

				$mydata_tab['fields'][] = array(
					'name'		=> __('Add Company Logo', 'primer'),
					'desc'		=> __('Jpg,Png files only. File must be 350x100px and up to 75 kb. Up to 3 logo changes (after pressing save) are supported.', 'primer'),
					'id'		=> 'company_logo',
					'type'		=> 'title',
					'after_field' => '
						<div class="company_img">
							<div id="profile_photo">
								<div class="thumb">
									<div class="avatar-wrapper">'
					                 .$company_logo_src.
					                 '</div>
								</div>
							</div>
							<div id="upload_errors"></div>
							<div class="profile-img-controls">
								<div id="plupload-container"></div>
							</div>
							<div id="profile_upload_container" class="get__photo">
								<a id="select_user_profile_photo" class="upload-button button '.$disable_upload.'" href="javascript:;">'.__('Upload File', 'primer').'</a>
							</div>
						</div>
					',
				);

				$mydata_tab['fields'][] = array(
					'name'		=> __('Use logo', 'primer'),
					'desc'		=> __('Use logo', 'primer'),
					'id'		=> 'primer_use_logo',
					'type'		=> 'checkbox'

				);

				$mydata_tab['fields'][] = array(
					'name'		=> __('Count of upload logo', 'primer'),
					'desc'		=> '',
					'id'		=> 'count_logo_change',
					'type'		=> 'hidden',
					'default'	=> '0',
				);

				$mydata_tab['fields'][] = array(
					'name'		=> __('Image API ID', 'primer'),
					'desc'		=> '',
					'id'		=> 'image_api_id',
					'type'		=> 'hidden',
					'default'	=> '',
					'save_field' => false
				);
                $mydata_tab['fields'][] = array(
                    'name'		=> __('Use Primer Software SMTP', 'primer'),
                    'desc'		=> __('Allow Primer Software to send email with the invoice/receipt attached, using Primer’s SMTP', 'primer'),
                    'id'		=> 'primer_use_api_smtp',
                    'type'		=> 'radio_inline',
                    'options' 	=> array(
                        'off'	=> __('Off', 'primer'),
                        'on'	=> __('On', 'primer'),
                    ),
                    'default' 	=> 'on'
                );
                $mydata_tab['fields'][] = array(
                    'name'	=> __( 'Enable woocommerce rounding calculation fix', 'primer' ),
                    'desc' => __( 'Enable woocommerce rounding calculation fix. CAUTION! With this setting enabled your orders will be automatically change status to pending for a small amount of time in order to recalculate the totls of the order.', 'primer' ),
                    'type'	=> 'checkbox',
                    'id'	=> 'primer_rounding_calculation',
                );
                $mydata_tab['fields'][] = array(
                    'name'      => __( 'Add Notes in the invoice comments', 'primer' ),
                    'desc'      => 'The notes are integrated in the corresponding field in the document with the notes of the order. Maximum number of characters (total for notes): 750 characters',
                    'type'      => 'wysiwyg',
                    'default'   => '',
                    'id'        => 'mydata_invoice_notes',
                    'sanitization_cb' => false,
                    'options' => array(
                        'media_buttons' => false,
                        'textarea_rows' => get_option('default_post_edit_rows', 7),
                        'teeny' => true,
                        'tinymce' => true,
                        'quicktags' => true
                    ),
                );
                if (is_array($primer_license_data) && in_array(17, $primer_license_data['wpModules'])) {
                    $mydata_tab['fields'][] = array(
                        'name' => __('Zero value added tax for invoices outside European union', 'primer'),
                        'desc' => 'For companies outside the European Union, zero value added tax.',
                        'id' => 'zero_tax_out_EU',
                        'type' => 'radio_inline',
                        'options' => array(
                            'off' => __('Off', 'primer'),
                            'on' => __('On', 'primer'),
                        ),
                        'default' => 'off'
                    );

                    $mydata_tab['fields'][] = array(
                        'name' => __('Enable VIES check in checkout', 'primer'),
                        'desc' => 'For companies of the European Union insert VAT number for 0% value added tax.',
                        'id' => 'vies_check',
                        'type' => 'radio_inline',
                        'options' => array(
                            'off' => __('Off', 'primer'),
                            'on' => __('On', 'primer'),
                        ),
                        'default' => 'off'
                    );

                    $mydata_tab['fields'][] = array(
                        'name' => __('Enable VAT Validation in checkout', 'primer'),
                        'desc' => 'If you need help configuring your credentials for the VAT validation please see the instructions <a target="_blank" href="https://primer.gr/wp-content/uploads/2022/03/ΟΔΗΓΙΕΣ-ΔΗΜΙΟΥΡΓΙΑΣ-ΚΩΔΙΚΩΝ-ΓΙΑ-ΤΗΝ-ΑΝΑΖΗΤΗΣΗ-ΒΑΣΙΚΩΝ-ΣΤΟΙΧΕΙΩΝ-ΜΗΤΡΩΟΥ-ΕΠΙΧΕΙΡΗΣΕΩΝ.pdf">HERE</a>',
                        'id' => 'checkout_vat_validation',
                        'type' => 'radio_inline',
                        'options' => array(
                            'off' => __('Off', 'primer'),
                            'on' => __('On', 'primer'),
                        ),
                        'default' => 'off'
                    );


                    $mydata_tab['fields'][] = array(
                        'name' => __('Username', 'primer'),
                        'desc' => '',
                        'default' => '',
                        'type' => 'text_medium',
                        'id' => 'username_validation',
                        'attributes' => array(
                            'data-conditional-id' => 'checkout_vat_validation',
                            'data-conditional-value' => 'on',

                        ),
                    );
                    $mydata_tab['fields'][] = array(
                        'name' => __('Password', 'primer'),
                        'desc' => '',
                        'default' => '',
                        'type' => 'text_medium',
                        'id' => 'password_validation',
                        'attributes' => array(
                            'data-conditional-id' => 'checkout_vat_validation',
                            'data-conditional-value' => 'on',
                            'type' => 'password',
                        ),
                    );
                }


                $img_id = isset($mydata_options['upload_img_id']) ? $mydata_options['upload_img_id'] : '';
                $use_logo_url = isset($mydata_options['primer_use_logo']) ? $mydata_options['primer_use_logo'] : 'on';
                $params_url = array();
                if (!empty($img_id)) {
                    $params_url = array('primer_use_logo' => $use_logo_url, 'img_src' => wp_get_attachment_image_url($img_id,'full'));
                }

				$mydata_tab['fields'][] = array(
					'name' 		=> __( 'Select Greek invoice template', 'primer' ),
					'desc'		=> '',
					'id'		=> 'greek_template',
					'type'		=> 'select',
					'options'	=> array('greek_template1' => __( 'Greek invoice template', 'primer' )),
					'after_field' => '
						<a href="'.add_query_arg($params_url, plugins_url('../../public/partials/gr_invoicetemplate_defaultA4.php', __FILE__ )).'" target="_blank" class="button preview">'.__('Preview template', 'primer').'</a>
					',
				);

				$mydata_tab['fields'][] = array(
					'name' 		=> __( 'Select English invoice template', 'primer' ),
					'desc'		=> '',
					'id'		=> 'english_template',
					'type'		=> 'select',
					'options'	=> array('english_template1' => __( 'English invoice template', 'primer' )),
					'after_field' => '
						<a href="'.add_query_arg($params_url, plugins_url('../../public/partials/invoicetemplate_defaultA4.php', __FILE__)).'" target="_blank" class="button preview">'.__('Preview template', 'primer').'</a>
					',
				);
			}
		}

        if (!empty($primer_license_data) && $primer_license_data['mistake_license'] !== 'fail') {
            $this->option_metabox[] = apply_filters( 'primer_mydata_option_fields', array(
                'id'			=> $prefix . 'mydata',
                'title'			=> __( 'MyData Settings', 'primer' ),
                'menu_title'	=> __( 'MyData Settings', 'primer' ),
                'desc'			=> '',
                'show_on'    	=> array( 'key' => 'options-page', 'value' => array( 'mydata' ), ),
                'show_names' 	=> true,
                'fields'		=> $mydata_tab['fields']
            ) );
        }


		$email_tab = array();
		if (is_array($primer_license_data) && !array_key_exists('mistake_license', $primer_license_data)) {
			$email_tab['fields'][] = array(
				'name'		=> __( 'Email SMTP settings', 'primer' ),
				'desc'		=> '',
				'default'	=> '',
				'id'		=> 'title_email_smtp_settings',
				'type'		=> 'title',
			);

			$email_tab['fields'][] = array(
				'name' => __('Activate this plugin with proper credentials', 'primer'),
				'desc' => __('For more information you can go to www.primer.gr.', 'primer'),
				'id' => 'general_settings_disable',
				'type' => 'title',
				'classes' => 'disable_functionality'
			);
		} else {
			if (is_array($primer_license_data) && ($primer_license_data['mistake_license'] == 'fail' || !in_array(6, $primer_license_data['wpModules']))) {
				$email_tab['fields'][] = array(
					'name'		=> __( 'Email SMTP settings', 'primer' ),
					'desc'		=> '',
					'default'	=> '',
					'id'		=> 'title_email_smtp_settings',
					'type'		=> 'title',
				);

				$email_tab['fields'][] = array(
					'name' => __('Activate this plugin with proper credentials', 'primer'),
					'desc' => __('For more information you can go to www.primer.gr.', 'primer'),
					'id' => 'general_settings_disable',
					'type' => 'title',
					'classes' => 'disable_functionality'
				);
			} else {
				$email_tab['fields'][] = array(
					'name'		=> __( 'Email SMTP settings', 'primer' ),
					'desc'		=> '',
					'default'	=> '',
					'id'		=> 'title_email_smtp_settings',
					'type'		=> 'title',
				);
                $email_tab['fields'][] = array(
                    'name'		=> __( 'Smtp Type', 'primer' ),
                    'desc'      => __( 'Choose your smtp configuration', 'primer' ),
                    'id'		=> 'smtp_type',
                    'type'    	=> 'radio_inline',
                    'options'	=> array(
                        'wordpress_default' => __( 'Wordpress Default SMTP', 'primer' ),
                        'other_smtp' => __( 'Other SMTP', 'primer' )
                    ),
                    'default'	=> $default_smtp_type
                );

				$email_tab['fields'][] = array(
					'name'		=> __( 'Send email from account', 'primer' ),
					'desc'		=> '',
					'default'	=> '',
					'type'	=> 'text_email',
					'id'	=> 'primer_from_email',
                    'attributes' => array(
                        'data-conditional-id' => 'smtp_type',
                        'data-conditional-value' => 'other_smtp',
                    ),
				);

				$email_tab['fields'][] = array(
					'name'      => __( 'Email username', 'primer' ),
					'desc'      => '',
					'default'   => '',
					'type'      => 'text',
					'id'        => 'primer_smtp_username',
                    'attributes' => array(
                        'data-conditional-id' => 'smtp_type',
                        'data-conditional-value' => 'other_smtp',
                    ),
				);

				$email_tab['fields'][] = array(
					'name'      => __( 'Email password', 'primer' ),
					'desc'      => '',
					'default'   => '',
					'type'      => 'text',
					'id'        => 'primer_smtp_password',
					'attributes' => array(
						'type' => 'password',
                        'data-conditional-id' => 'smtp_type',
                        'data-conditional-value' => 'other_smtp',
					),
				);

				$email_tab['fields'][] = array(
					'name'		=> __( 'Type of Encryption', 'primer' ),
					'desc'      => __( 'For most servers SSL/TLS is the recommended option', 'primer' ),
					'id'		=> 'primer_smtp_type_encryption',
					'type'    	=> 'radio_inline',
					'options'	=> array(
						'none' => __( 'None', 'primer' ),
						'ssl' => __( ' SSL/TLS', 'primer' ),
						'tls' => __( ' STARTTLS', 'primer' ),
					),
                    'attributes' => array(
                        'data-conditional-id' => 'smtp_type',
                        'data-conditional-value' => 'other_smtp',
                    ),
					'default'	=> 'none'
				);

				$email_tab['fields'][] = array(
					'name'		=> __( 'SMTP Authentication', 'primer' ),
					'desc'		=> __("This options should always be checked 'Yes'", 'primer'),
					'id'		=> 'primer_smtp_authentication',
					'type'		=> 'radio_inline',
					'options'	=> array(
						'yes'	=> __('Yes', 'primer'),
						'no'	=> __('No', 'primer'),
					),
                    'attributes' => array(
                        'data-conditional-id' => 'smtp_type',
                        'data-conditional-value' => 'other_smtp',
                    ),
					'default'	=> 'no'
				);

				$email_tab['fields'][] = array(
					'name'      => __( 'SMTP server', 'primer' ),
					'desc'      => '',
					'default'   => 'smtp.example.com',
					'type'      => 'text',
					'id'        => 'primer_smtp_host',
                    'attributes' => array(
                        'data-conditional-id' => 'smtp_type',
                        'data-conditional-value' => 'other_smtp',
                    )
				);

				$email_tab['fields'][] = array(
					'name'      => __( 'Port', 'primer' ),
					'desc'      => '',
					'default'   => '25',
					'type'      => 'text',
					'id'        => 'primer_smtp_port',
                    'attributes' => array(
                        'data-conditional-id' => 'smtp_type',
                        'data-conditional-value' => 'other_smtp',
                    ),
					'after_row'		=> '<button type="button" name="primer_smtp_form_submit" class="button badge-danger send_tested_email">'.__('Test Email settings', 'primer').'</button>',
				);

				$email_tab['fields'][] = array(
					'name'		=> __( 'Email settings', 'primer' ),
					'desc'		=> __('Settings for the emails send to your clients', 'primer'),
					'default'	=> '',
					'id'		=> 'title_email_settings',
					'type'		=> 'title',
				);

                $email_tab['fields'][] = array(
                    'name'      => __( 'From:(name)', 'primer' ),
                    'desc'      => '',
                    'default'   => '',
                    'type'      => 'text',
                    'id'        => 'email_from_name',
                    'attributes' => array(
                        'data-conditional-id' => 'smtp_type',
                        'data-conditional-value' => 'other_smtp',
                    )
                );

				$email_tab['fields'][] = array(
					'name'      => __( 'Email subject', 'primer' ),
					'desc'      => '',
					'default'   => '',
					'type'      => 'text',
					'id'        => 'email_subject',
                    'before_row' => '<div class="cmb-full-row">',
                    'after_row' => '</div>'
				);

                $email_tab['fields'][] = array(
                    'name'      => __( 'Email body', 'primer' ),
                    'desc'      => '',
                    'type'      => 'wysiwyg',
                    'default'   => '',
                    'id'        => 'quote_available_content',
                    'sanitization_cb' => false,
                    'options' => array(
                        'media_buttons' => false,
                        'textarea_rows' => get_option('default_post_edit_rows', 7),
                        'teeny' => true,
                        'tinymce' => true,
                        'quicktags' => true
                    ),
                    'before_row' => '<div class="cmb-half-row" style="float:left;">',
                    'after_row' => '</div>',
                );

                $email_tab['fields'][] = array(
                    'desc' => __('Click on a wildcard to insert it into the email body.', 'primer'),
                    'type' => 'email_wildcards',
                    'id' => 'email_wildcards_container',
                );

                $email_tab['fields'][] = array(
					'name'		=> __('Send email automatically on order conversion', 'primer'),
					'desc'		=> '',
					'default'	=> 'yes',
					'id'		=> 'automatically_send_on_conversation',
					'type'		=> 'radio_inline',
					'options'	=> array(
						'yes' => __('Yes', 'primer'),
						'no' => __('No', 'primer')
					)
				);
			}
		}

        if (!empty($primer_license_data) && $primer_license_data['mistake_license'] !== 'fail') {
            $this->option_metabox[] = apply_filters( 'primer_email_option_fields', array(
                'id'		=> $prefix . 'emails',
                'title'		=> __( 'Email Settings', 'primer' ),
                'menu_title'		=> __( 'Email Settings', 'primer' ),
                'desc'				=> '',
                'show_on'    => array( 'key' => 'options-page', 'value' => array( 'emails' ), ),
                'show_names' => true,
                'fields'		=> $email_tab['fields'],
            ) );
        }



		$checkbox = array(
			'name'	=> __( 'Activate Automation', 'primer' ),
			'desc' => __( 'Activate Automation', 'primer' ),
			'type'	=> 'checkbox',
			'id'	=> 'activation_automation',
		);


		$plugin_edition = '';
		$primer_licenses = get_option('primer_licenses');
		$exist_modules = array();
		if (!empty($primer_licenses)) {
			if (isset($primer_licenses['wpModules'])) {
				$exist_modules = $primer_licenses['wpModules'];
			}
		}
		if (!empty($exist_modules)) {
			asort($exist_modules, SORT_NUMERIC);
			$plugin_edition = primer_plugin_allowed_functions($exist_modules);
		}

		$allowed_automation_field = array();
		if (is_array($primer_license_data) && ((array_key_exists('mistake_license', $primer_license_data) && $primer_license_data['mistake_license'] !== 'fail') && in_array(5, $primer_license_data['wpModules']))) {
			$allowed_automation_field = array(
				$checkbox,
				array(
					'id'          => $prefix . 'conditions',
					'type'        => 'group',
					'description' => '',
					'options'     => array(
						'group_title'   => __( 'Condition {#}', 'primer' ), // {#} gets replaced by row number
						'add_button'    => __( '+Add condition', 'primer' ),
						'remove_button' => __( 'Delete condition', 'primer' ),
						'sortable'      => false,
					),
					'fields' => array(
						array(
							'name'       => __( 'Issue receipt if order state is: ', 'primer' ),
							'id'         => 'receipt_order_states',
							'type'       => 'select',
							'options'	=> $this->get_status_of_orders(),
						),
						array(
							'name' 	=> __('Send email with invoice to client', 'primer'),
							'id'	=> 'client_email_send',
							'type'	=> 'checkbox',
						)
					)
				),
				array(
					'name' => __('Run Automation every ', 'primer'),
					'id'	=> 'automation_duration',
					'type' => 'select',
					'options' => array(
						'fiveminutes' => __('5 minutes', 'primer'),
						'tenminutes' => __('10 minutes', 'primer'),
						'thirtyminutes' => __('30 minutes', 'primer'),
						'hourly' => __('60 minutes', 'primer'),
						'daily' => __('Once per day', 'primer'),
					),
					'attributes' => array(
						'data-conditional-id' => 'activation_automation',
						'data-conditional-value' => 'on',
					),
					'after_field' => __('In order for automation to work, your server needs to support cron. ', 'primer') . $this->check_wp_cron_enabled(),
				),
                array(
                    'name' => __('Limit Orders for each automation ', 'primer'),
                    'id'	=> 'automation_limit',
                    'type' => 'select',
                    'options' => array(
                        '20' => __('20 orders', 'primer'),
                        '30' => __('30 orders', 'primer'),
                        '50' => __('50 orders', 'primer'),
                        '70' => __('70 orders', 'primer'),
                        '100' => __('100 orders', 'primer'),
                        '200' => __('200 orders', 'primer'),
                        'unlimited' => __('Unlimited orders', 'primer')
                    ),
                    'default'          => '50',
                    'attributes' => array(
                        'data-conditional-id' => 'activation_automation',
                        'data-conditional-value' => 'on',
                    ),
                    'after_field' => __('Warning, the bigger the limit, the heavier is the load for your server. If you get frequent timeouts, try using a lower limit.', 'primer'),
                ),
				array(
					'name' => __('Run Automation on orders issued after: ', 'primer'),
					'id' => 'calendar_date_timestamp',
					'type' => 'text_date',
					'date_format' => 'Y-m-d',
					'after_field' => __('Warning, the older the date, the heavier is the load for your server. If you get frequent timeouts, try using a more recent date.', 'primer'),
					'attributes' => array(
						'data-conditional-id' => 'activation_automation',
						'data-conditional-value' => 'on',
					),
				),

				array(
					'name'	=> __( 'Send email to admin', 'primer' ),
					'desc' => '',
					'type'	=> 'checkbox',
					'id'	=> 'send_email_to_admin',
					'attributes' => array(
						'data-conditional-id' => 'activation_automation',
						'data-conditional-value' => 'on',
					),
				),

				array(
					'name'	=> __( 'Admin email', 'primer' ),
					'desc' => __('(use comma (,) without spaces for multiple emails)', 'primer'),
					'type'	=> 'text',
					'id'	=> 'admin_email',
					'attributes' => array(
						'data-conditional-id' => 'activation_automation',
						'data-conditional-value' => 'on',
					),
				),

				array(
					'name'	=> __( 'Send successful receipts log', 'primer' ),
					'desc' => '',
					'type'	=> 'checkbox',
					'id'	=> 'send_successful_log',
					'attributes' => array(
						'data-conditional-id' => 'activation_automation',
						'data-conditional-value' => 'on',
					),
				),

				array(
					'name'	=> __( 'Send failed receipts log', 'primer' ),
					'desc' => '',
					'type'	=> 'checkbox',
					'id'	=> 'send_failed_log',
					'attributes' => array(
						'data-conditional-id' => 'activation_automation',
						'data-conditional-value' => 'on',
					),
				),

				array(
					'name'	=> __( 'Email Subject: ', 'primer' ),
					'desc' => '',
					'type'	=> 'text',
					'id'	=> 'email_subject',
					'attributes' => array(
						'data-conditional-id' => 'activation_automation',
						'data-conditional-value' => 'on',
					),
				),

				array(
					'name'	=> '',
					'desc' => '',
					'type'	=> 'button',
					'id'	=> 'log_button',
					'after' => '<a href="' . admin_url('admin.php?page=primer_receipts_logs_automation') . '" target="_blank" class="button order-view">'.__('Log', 'primer').'</a>'
				),

				array(
					'name'	=> '',
					'desc' => '',
					'type'	=> 'button',
					'id'	=> 'run_now_button',
					'after' => '<a href="#" class="button" id="cron-execute-cron-task-now">'.__('Run Now', 'primer').'</a>'
				),

			);
		} else {
			$allowed_automation_field = array(
				array(
					'name'	=> __('Automation Settings not activated', 'primer'),
					'desc' => __('Your version of the plugin does not support automation. Automation gives you the ability to issue receipts automatically according to conditions you enter. For more information and to upgrade your version you can go to www.primer.gr.', 'primer'),
					'type'	=> 'title',
					'id'	=> 'disable_title',
				),

			);
		}

        if (!empty($primer_license_data) && $primer_license_data['mistake_license'] !== 'fail') {
            $this->option_metabox[] = apply_filters( 'primer_automation_option_fields', array(
                'id'			=> $prefix . 'automation',
                'title'			=> __( 'Automation Settings', 'primer' ),
                'menu_title'	=> __( 'Automation Settings', 'primer' ),
                'desc'			=> '',
                'show_on'    	=> array( 'key' => 'options-page', 'value' => array( 'automation' ), ),
                'show_names' 	=> true,
                'fields'		=> $allowed_automation_field
            ) );
        }


		$primer_license_data = get_option('primer_licenses');
		$primer_license_key = '';
		$primer_license_monthRemainingInvoices = '';
		$expire_date = '';
        $primer_license_end_date = '';
		$companyName = '';
        $translatedCompanyName = '';
		$companySmallName = '';
        $translatedCompanySmallName = '';
		$vatNumber = '';
		$companyAddress = '';
        $translatedCompanyAddress = '';
		$companyPhoneNumber = '';
		$companyActivity = '';
        $translatedCompanyActivity = '';
		$gemh = '';
		$username = '';
		$password = '';
        $webpage = '';
        $companyCity = '';
        $translatedCompanyCity = '';
        $plugin_edition_string = '';
        $companyTk = '';
        $companyEmail = '';
        $companyDoy = '';
        $translatedCompanyDoy = '';
        $connector_password = '';
        $currentBranchIdType = 'hidden';
        $subsidiaryId = '';
        $subsidiaryCity = '';
        $subsidiaryAddress = '';
        $subsidiaryAddressNumber = '';
        $subsidiaryTk = '';
        $subsidiaryDoy = '';
        $subsidiaryPhone = '';
		if(!empty($primer_license_data)) {
            if (!empty($primer_license_data['domain'])) {
                $webpage = $primer_license_data['domain'];
            }
            if (!empty($primer_license_data['connectorPassword'])) {
                $connector_password = $primer_license_data['connectorPassword'];
            }
            if (!empty($primer_license_data['companyDoy'])) {
                $companyDoy = $primer_license_data['companyDoy'];
            }
            if (!empty($primer_license_data['companyEmail'])) {
                $companyEmail = $primer_license_data['companyEmail'];
            }
            if (!empty($primer_license_data['companyCity'])) {
                $companyCity = $primer_license_data['companyCity'];
            }
            if (!empty($primer_license_data['companyTk'])) {
                $companyTk = $primer_license_data['companyTk'];
            }
			if (!empty($primer_license_data['serialNumber'])) {
				$primer_license_key = $primer_license_data['serialNumber'];
			}
			if(!empty($primer_license_data['monthRemainingInvoices'])) {
				$primer_license_monthRemainingInvoices = $primer_license_data['monthRemainingInvoices'];
			}
            if(!empty($primer_license_data['endDate'])) {
                $primer_license_end_date = $primer_license_data['endDate'];
            }
			if (!empty($primer_license_end_date)) {
				$expire_date = date('d F Y', strtotime("$primer_license_end_date"));
			}
			if (!empty($primer_license_data['companyName'])) {
				$companyName = $primer_license_data['companyName'];
			}
            if (!empty($primer_license_data['translated_company_name'])) {
                $translatedCompanyName = $primer_license_data['translated_company_name'];
            }
            if (!empty($primer_license_data['translated_company_small_name'])) {
                $translatedCompanySmallName = $primer_license_data['translated_company_small_name'];
            }
            if (!empty($primer_license_data['translated_company_address'])) {
                $translatedCompanyAddress = $primer_license_data['translated_company_address'];
            }
            if (!empty($primer_license_data['translated_company_city'])) {
                $translatedCompanyCity = $primer_license_data['translated_company_city'];
            }
            if (!empty($primer_license_data['translated_company_doy'])) {
                $translatedCompanyDoy = $primer_license_data['translated_company_doy'];
            }
            if (!empty($primer_license_data['translated_company_activity'])) {
                $translatedCompanyActivity = $primer_license_data['translated_company_activity'];
            }
			if (!empty($primer_license_data['companySmallName'])) {
				$companySmallName = $primer_license_data['companySmallName'];
			}
			if (!empty($primer_license_data['companyVatNumber'])) {
				$vatNumber = $primer_license_data['companyVatNumber'];
			}
			if (!empty($primer_license_data['companyAddress'])) {
				$companyAddress = $primer_license_data['companyAddress'];
			}
			if (!empty($primer_license_data['companyPhoneNumber'])) {
				$companyPhoneNumber = $primer_license_data['companyPhoneNumber'];
			}
			if (!empty($primer_license_data['companyActivity'])) {
				$companyActivity = $primer_license_data['companyActivity'];
			}
			if (!empty($primer_license_data['gemh'])) {
				$gemh = $primer_license_data['gemh'];
			}
			if (!empty($primer_license_data['username'])) {
				$username = $primer_license_data['username'];
			}
			if (!empty($primer_license_data['password'])) {
				$password = $primer_license_data['password'];
			}
            if (!isset($primer_license_data['currentBranchID']) || $primer_license_data['currentBranchID'] == 0  || $primer_license_data['currentBranchID'] == null ) {
                $currentBranchIdType = 'hidden';
                $subsidiaryId = '';
                $subsidiaryCity = '';
                $subsidiaryAddress = '';
                $subsidiaryAddressNumber = '';
                $subsidiaryTk = '';
                $subsidiaryDoy = '';
                $subsidiaryPhone = '';
            } else {
                $currentBranchIdType = 'text_medium';

                // Get the subsidiaries array
                $subsidiaries = $primer_license_data['subsidiaries'];

                // Find the subsidiary based on currentBranchID
                $currentBranchId = $primer_license_data['currentBranchID'];
                $foundSubsidiary = null;

                foreach ($subsidiaries as $subsidiary) {
                    if ($subsidiary['branchId'] == $currentBranchId) {
                        $foundSubsidiary = $subsidiary;
                        break;
                    }
                }

                // Check if a matching subsidiary is found
                if ($foundSubsidiary) {
                    // Populate the variables with subsidiary data
                    $subsidiaryId = $foundSubsidiary['branchId'];
                    $subsidiaryCity = $foundSubsidiary['city'];
                    $subsidiaryAddress = $foundSubsidiary['street'];
                    $subsidiaryAddressNumber = $foundSubsidiary['number'];
                    $subsidiaryTk = $foundSubsidiary['tk'];
                    $subsidiaryDoy = $foundSubsidiary['doy'];
                    $subsidiaryPhone = $foundSubsidiary['phoneNumber'];
                } else {
                    // Handle the case where no matching subsidiary is found
                    // You can set default values or handle it as needed
                    $subsidiaryId = '';
                    $subsidiaryCity = '';
                    $subsidiaryAddress = '';
                    $subsidiaryAddressNumber = '';
                    $subsidiaryTk = '';
                    $subsidiaryDoy = '';
                    $subsidiaryPhone = '';
                }
            }
            if (!empty($primer_license_data['wpEdition'])) {
                $plugin_edition = $primer_license_data['wpEdition'];
                if($plugin_edition == 'PRwpluginGold'){
                    $plugin_edition_string = 'Primer Plugin Gold Edition';
                }elseif($plugin_edition == 'PRwpluginBS'){
                    $plugin_edition_string = 'Primer Plugin Business Edition';
                }elseif($plugin_edition == 'PRwpluginDM'){
                    $plugin_edition_string = 'Primer Plugin Demo Edition';
                }
            }
		}

		$upload_dir = wp_upload_dir()['basedir'];
		if (!file_exists($upload_dir . '/primer-export-invoices')) {
			mkdir($upload_dir . '/primer-export-invoices');
		}
		$export_dir_file = $upload_dir . '/primer-export-invoices';
        if (!empty($primer_license_data) && $primer_license_data['mistake_license'] !== 'fail' && !empty($primer_license_data['wpModules']) && in_array(10, $primer_license_data['wpModules'])) {
            $this->option_metabox[] = apply_filters('primer_export_option_fields', array(
                'id' => $prefix . 'export',
                'title' => __('Export', 'primer'),
                'menu_title' => __('Export', 'primer'),
                'desc' => '',
                'show_on' => array('key' => 'options-page', 'value' => array('export'),),
                'show_names' => true,
                'fields' => array(
                    array(
                        'name' => __('Export Fields', 'primer'),
                        'id' => 'export_title',
                        'type' => 'title',
                        'desc' => '',
                    ),
                    array(
                        'name' => __('Field Name', 'primer'),
                        'id' => 'field_column_name',
                        'type' => 'text_small',
                        'default' => 'Column',
                        'attributes' => array(
                            'readonly' => 'readonly',
                        ),
                        'classes' => 'title_field',
                        'save_field' => false,
                        'before_row' => '<div class="export_columns"><div class="export_column">'
                    ),
                    array(
                        'name' => __('Client name', 'primer'),
                        'id' => 'export_select_client_name',
                        'type' => 'select',
                        'options' => $this->get_alpha_char(),
                    ),
                    array(
                        'name' => __('Client Company', 'primer'),
                        'id' => 'export_select_client_company',
                        'type' => 'select',
                        'options' => $this->get_alpha_char(),
                    ),
                    array(
                        'name' => __('Client VAT', 'primer'),
                        'id' => 'export_select_client_vat',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Client Activity', 'primer'),
                        'id' => 'export_select_client_activity',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Client Address', 'primer'),
                        'id' => 'export_select_client_address',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Field Name', 'primer'),
                        'id' => 'field_column_name_2',
                        'type' => 'text_small',
                        'default' => 'Column',
                        'attributes' => array(
                            'readonly' => 'readonly',
                        ),
                        'classes' => 'title_field',
                        'save_field' => false,
                        'before_row' => '</div><div class="export_column">'
                    ),
                    array(
                        'name' => __('Client Phone number', 'primer'),
                        'id' => 'export_select_client_phone',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Client Email', 'primer'),
                        'id' => 'export_select_client_email',
                        'type' => 'select',
                        'options' => $this->get_alpha_char(),
                    ),
                    array(
                        'name' => __('Client Webpage', 'primer'),
                        'id' => 'export_select_client_webpage',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Product name', 'primer'),
                        'id' => 'export_select_product_name',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Product Quantity', 'primer'),
                        'id' => 'export_select_product_quantity',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Field Name', 'primer'),
                        'id' => 'field_column_name_3',
                        'type' => 'text_small',
                        'default' => 'Column',
                        'attributes' => array(
                            'readonly' => 'readonly',
                        ),
                        'classes' => 'title_field',
                        'save_field' => false,
                        'before_row' => '</div><div class="export_column">'
                    ),
                    array(
                        'name' => __('VAT Amount per product', 'primer'),
                        'id' => 'export_select_vat_amount',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Total Amount per product', 'primer'),
                        'id' => 'export_select_total_amount_per_product',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Net amount (without VAT) per product', 'primer'),
                        'id' => 'export_select_net_amount_per_product',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Total Amount', 'primer'),
                        'id' => 'export_select_total_amount',
                        'type' => 'select',
                        'options' => $this->get_alpha_char(),
                    ),
                    array(
                        'name' => __('Field Name', 'primer'),
                        'id' => 'field_column_name_4',
                        'type' => 'text_small',
                        'default' => 'Column',
                        'attributes' => array(
                            'readonly' => 'readonly',
                        ),
                        'classes' => 'title_field',
                        'save_field' => false,
                        'before_row' => '</div><div class="export_column">',
                    ),
                    array(
                        'name' => __('Total (Sum) VAT Amount', 'primer'),
                        'id' => 'export_select_total_vat_amount',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Total (Sum) VAT Amount per tax rate', 'primer'),
                        'id' => 'export_select_total_vat_rate_amount',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Total (Sum) Net Amount', 'primer'),
                        'id' => 'export_select_total_net_amount',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Invoice Number', 'primer'),
                        'id' => 'export_select_invoice_number',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Series of the invoice', 'primer'),
                        'id' => 'export_select_invoice_series_number',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Invoice Date', 'primer'),
                        'id' => 'export_select_invoice_date',
                        'type' => 'select',
                        'options' => $this->get_alpha_char()
                    ),
                    array(
                        'name' => __('Invoice Type', 'primer'),
                        'id' => 'export_select_invoice_type',
                        'type' => 'select',
                        'options' => $this->get_alpha_char(),
                        'after_row' => '</div></div>'
                    ),

                    array(
                        'name' => __('Export settings', 'primer'),
                        'id' => 'export_settings_title',
                        'type' => 'title',
                        'desc' => '',
                    ),
                    array(
                        'name' => __('Export Type', 'primer'),
                        'id' => 'export_type',
                        'type' => 'select',
                        'options' => array(
                            'csv' => 'CSV',
                            'excel' => 'Excel',
                        ),
                        'before_row' => '<div class="type_wrap">'
                    ),
                    array(
                        'name' => __(" ", 'primer'),
                        'desc' => __("Leave blank row on new invoice/receipt", 'primer'),
                        'id' => 'export_leave_blank_row',
                        'type' => 'checkbox',
                    ),
                    array(
                        'name' => __(" ", 'primer'),
                        'desc' => __("First excel row is Column Name", 'primer'),
                        'id' => 'export_first_excel_column_name',
                        'type' => 'checkbox'
                    ),
                    array(
                        'name' => __(" ", 'primer'),
                        'desc' => __("Export Totals at the last row", 'primer'),
                        'id' => 'export_row_totals',
                        'type' => 'checkbox',
                    ),
                    array(
                        'name' => __(" ", 'primer'),
                        'desc' => __("Export Only Invoice Details per line", 'primer'),
                        'id' => 'export_only_invoice_details',
                        'type' => 'checkbox',
                        'after_row' => '</div>'
                    ),
                    array(
                        'name' => __('Choose Invoice Date for the exported invoices', 'primer'),
                        'id' => 'export_settings_date',
                        'type' => 'title',
                        'desc' => '',
                    ),
                    array(
                    'name' => 'From:',
                    'id'   => 'mydata_export_from',
                    'type' => 'text_date',
                    // 'timezone_meta_key' => 'wiki_test_timezone',
                     'date_format' => 'm/d/Y',
                        'before_row' => '<div class="type_wrap">'
                ),
                    array(
                        'name' => 'To:',
                        'id'   => 'mydata_export_to',
                        'type' => 'text_date',
                        // 'timezone_meta_key' => 'wiki_test_timezone',
                         'date_format' => 'm/d/Y',
                        'after_row' => '</div>'
                    ),
                    array(
                        'name' => __(" ", 'primer'),
                        'desc' => __("Enable Scheduler", 'primer'),
                        'id' => 'export_enable_schedule',
                        'type' => 'checkbox',
                    ),
                    array(
                        'name' => __("Export every", 'primer'),
                        'desc' => '',
                        'id' => 'export_time',
                        'type' => 'select',
                        'options' => array(
                            '' => __('Select', 'primer'),
                            'fifteenminutes' => __('15 minutes-Warning, server heavy load', 'primer'),
                            'hourly' => __('1 hour', 'primer'),
                            'twicedaily' => __('12 hours', 'primer'),
                            'daily' => __('1 Day', 'primer'),
                            '3days' => __('3 Days', 'primer'),
                            'weekly' => __('7 Days', 'primer'),
                            'first' => __('Every 1st of the month', 'primer'),
                            'fifteenth' => __('Every 15th of the month', 'primer'),
                        ),
                        'attributes' => array(
                            'data-conditional-id' => 'export_enable_schedule',
                            'data-conditional-value' => 'on',
                        ),
                    ),
                    array(
                        'name' => __(" ", 'primer'),
                        'desc' => __("Export to Path", 'primer'),
                        'id' => 'export_path',
                        'type' => 'checkbox',
                    ),
                    array(
                        'name' => __("Export path", 'primer'),
                        'desc' => __("Default export path is $export_dir_file. Check that the folder you enter has the correct write permissions.", 'primer'),
                        'id' => 'export_path_files',
                        'type' => 'text',
                        'attributes' => array(
                            'data-conditional-id' => 'export_path',
                            'data-conditional-value' => 'on',
                        ),
                    ),
                    array(
                        'name' => __(" ", 'primer'),
                        'desc' => __("Send Email", 'primer'),
                        'id' => 'export_email_check',
                        'type' => 'checkbox',
                    ),
                    array(
                        'name' => __("Email to", 'primer'),
                        'desc' => __("Check that Email settings are correct. If Email settings are missing or are incorrect, email will not be send.", 'primer'),
                        'id' => 'export_send_email',
                        'type' => 'text',
                    ),
                    array(
                        'name' => '',
                        'desc' => '',
                        'type' => 'button',
                        'id' => 'run_now_export_button',
                        'after' => '<a href="#" class="button" id="cron_export_run">' . __('Export Now', 'primer') . '</a>'
                    ),
                )
            ));
        }

		$this->option_metabox[] = apply_filters( 'primer_licenses_option_fields', array(
			'id'		 => $prefix . 'licenses',
			'title'		 => __( 'License and General Settings', 'primer' ),
			'menu_title' => __( 'License credentials', 'primer' ),
			'desc'			=> '',
			'show_on'    => array( 'key' => 'options-page', 'value' => array( 'licenses' ), ),
			'show_names' => true,
			'fields' => array(
				array(
					'name' => __('License credentials', 'primer'),
					'id' => 'license_credentials',
					'type' => 'title',
					'desc' => '',
				),
                array(
                    'name'       => 'Branch ID',
                    'desc'       => '',
                    'id'         => 'currentBranchID',
                    'type'       => 'hidden',
                    'default'    => 0,
                ),
				array(
					'name' 	=> __('License Key', 'primer'),
					'desc'	=> '',
					'id'	=> 'license_key',
					'type'	=> 'text_medium',
					'default' => "$primer_license_key",
					'after_field' => '<p>
<a href="#" id="first_time" class="button-secondary">'.__('Activate','primer').'</a></p>',
				),
				array(
					'name' 	=> __('Username', 'primer'),
					'desc'	=> '',
					'id'	=> 'license_user',
					'type'	=> 'text_medium',
					'default' => $username,
				),
				array(
					'name' 	=> __('Password', 'primer'),
					'desc'	=> '',
					'id'	=> 'license_password',
					'type'	=> 'text_medium',
					'attributes' => array(
						'type' => 'password',
					),
					'default' => $password,
				),

				array(
					'name' => __('License Details', 'primer'),
					'id' => 'license_details',
					'type' => 'title',
					'desc' => '',
				),
				array(
					'name' => __('Plugin Package', 'primer'),
					'desc' => '',
					'id' => 'plugin_package',
					'type' => 'text_medium',
					'default' => $plugin_edition_string,
					'attributes' => array(
						'readonly' => 'readonly'
					),
					'save_field' => false
				),
				array(
					'name' => __('Expires on', 'primer'),
					'desc' => '',
					'id' => 'expires_on',
					'type' => 'text_medium',
					'default' => "$expire_date",
					'attributes' => array(
						'readonly' => 'readonly'
					),
					'save_field' => false
				),
				array(
					'name' => __('MyData Package', 'primer'),
					'desc' => '',
					'id' => 'mydata_package',
					'type' => 'text_medium',
					'default' => (int)$primer_license_monthRemainingInvoices,
					'attributes' => array(
						'readonly' => 'readonly'
					),
					'save_field' => false
				),
				array(
					'name' => __('Remaining MyData invoices for this month', 'primer'),
					'desc' => '',
					'id' => 'remaining_invoices',
					'type' => 'text_medium',
					'default' => (int)$primer_license_monthRemainingInvoices,
					'attributes' => array(
						'readonly' => 'readonly'
					),
					'save_field' => false,
					'after_field' => '<a href="#" id="get_license_remaining" class="button-secondary">'.__('Get Remaining', 'primer').'</a>',
				),

				array(
					'name' => __('Owner Details', 'primer'),
					'id' => 'owner_details',
					'type' => 'title',
					'desc' => __( 'These are the details used as issuer on your invoices and receipts. To change the following details, please send an email to support-plugin@primer.gr with your username, your new company details and a copy of the IRS registration form. Below you have the choice to input ONLY FOR ONE TIME a translated alternative for your company details for foreign template invoices', 'primer' ),
				),
				array(
					'name'      => __( 'Company name', 'primer' ),
					'desc'      => '',
					'default'   => $companyName,
					'id'        => 'license_company_name',
					'type'      => 'text_medium',
					'attributes' => array(
						'readonly' => 'readonly'
					),
                    'save_field' => false,
				),
                array(
                    'name'      => __( 'Company name for English invoices', 'primer' ),
                    'desc'      => '',
                    'default'   => $translatedCompanyName,
                    'id'        => 'translated_company_name',
                    'type'      => 'text_medium',
                    'attributes' => array(
                        'readonly' => $translatedCompanyName != '' ? 'readonly' : false
                    ),
                ),
				array(
					'name'      => __( 'Company small name', 'primer' ),
					'desc'      => '',
					'default'   => $companySmallName,
					'id'        => 'license_company_small_name',
					'type'      => 'text_medium',
					'attributes' => array(
						'readonly' => 'readonly'
					),
                    'save_field' => false
				),
                array(
                    'name'      => __( 'Company small name for English invoices', 'primer' ),
                    'desc'      => '',
                    'default'   => $translatedCompanySmallName,
                    'id'        => 'translated_company_small_name',
                    'type'      => 'text_medium',
                    'attributes' => array(
                        'readonly' => $translatedCompanySmallName != '' ? 'readonly' : false
                    ),
                ),
				array(
					'name'      => __( 'VAT number', 'primer' ),
					'desc'      => '',
					'default'   => $vatNumber,
					'id'        => 'license_vat_number',
					'type'      => 'text_medium',
					'attributes' => array(
						'readonly' => 'readonly'
					),
                    'save_field' => false
				),
				array(
					'name'      => __( 'Headquarters Address', 'primer' ),
					'desc'      => '',
					'default'   => $companyAddress,
					'id'        => 'license_head_address',
					'type'      => 'text_medium',
					'attributes' => array(
						'readonly' => 'readonly'
					),
                    'save_field' => false
				),
                array(
                    'name'      => __( 'Headquarters Address for English invoices', 'primer' ),
                    'desc'      => '',
                    'default'   => $translatedCompanyAddress,
                    'id'        => 'translated_company_address',
                    'type'      => 'text_medium',
                    'attributes' => array(
                        'readonly' => $translatedCompanyAddress != '' ? 'readonly' : false
                    ),
                ),
				array(
					'name'      => __( 'Activity', 'primer' ),
					'desc'      => '',
					'default'   => $companyActivity,
					'id'        => 'license_activity',
					'type'      => 'text_medium',
					'attributes' => array(
						'readonly' => 'readonly'
					),
                    'save_field' => false
				),
                array(
                    'name'      => __( 'Activity for English invoices', 'primer' ),
                    'desc'      => '',
                    'default'   => $translatedCompanyActivity,
                    'id'        => 'translated_company_activity',
                    'type'      => 'text_medium',
                    'attributes' => array(
                        'readonly' => $translatedCompanyActivity != '' ? 'readonly' : false
                    ),
                ),
				array(
					'name'      => __( 'GEMH number', 'primer' ),
					'desc'      => '',
					'default'   => $gemh,
					'id'        => 'license_gemh_number',
					'type'      => 'text_medium',
					'attributes' => array(
						'readonly' => 'readonly'
					),
                    'save_field' => false
				),
				array(
					'name'      => __( 'Webpage', 'primer' ),
					'desc'      => '',
					'default'   => $webpage,
					'id'        => 'license_webpage',
					'type'      => 'text_medium',
					'attributes' => array(
						'readonly' => 'readonly'
					),
                    'save_field' => false
				),
				array(
					'name'      => __( 'Phone number', 'primer' ),
					'desc'      => '',
					'default'   => $companyPhoneNumber,
					'id'        => 'license_phone_number',
					'type'      => 'text_medium',
					'attributes' => array(
						'readonly' => 'readonly'
					),
                    'save_field' => false
				),
				array(
					'name'      => __( 'Email', 'primer' ),
					'desc'      => '',
					'default'   => $companyEmail,
					'id'        => 'license_email',
					'type'      => 'text_email',
					'attributes' => array(
						'readonly' => 'readonly'
					),
                    'save_field' => false
				),
                array(
                    'name'      => __( 'City', 'primer' ),
                    'desc'      => '',
                    'default'   => $companyCity,
                    'id'        => 'CompanyCity',
                    'type'      => 'text_medium',
                    'attributes' => array(
                        'readonly' => 'readonly'
                    ),
                    'save_field' => false
                ),
                array(
                    'name'      => __( 'City for English invoices', 'primer' ),
                    'desc'      => '',
                    'default'   => $translatedCompanyCity,
                    'id'        => 'translated_company_city',
                    'type'      => 'text_medium',
                    'attributes' => array(
                        'readonly' => $translatedCompanyCity != '' ? 'readonly' : false
                    ),
                ),
                array(
                    'name'      => __( 'Postal Code', 'primer' ),
                    'desc'      => '',
                    'default'   => $companyTk,
                    'id'        => 'CompanyTk',
                    'type'      => 'text_medium',
                    'attributes' => array(
                        'readonly' => 'readonly'
                    ),
                    'save_field' => false
                ),
                array(
                    'name'      => __( 'ΔΟΥ', 'primer' ),
                    'desc'      => '',
                    'default'   => $companyDoy,
                    'id'        => 'CompanyDoy',
                    'type'      => 'text_medium',
                    'attributes' => array(
                        'readonly' => 'readonly'
                    ),
                    'save_field' => false
                ),
                array(
                    'name'      => __( 'DOY for English invoices', 'primer' ),
                    'desc'      => '',
                    'default'   => $translatedCompanyDoy,
                    'id'        => 'translated_company_doy',
                    'type'      => 'text_medium',
                    'attributes' => array(
                        'readonly' => $translatedCompanyDoy != '' ? 'readonly' : false
                    ),
                ),
                array(
                    'name'      => __( 'Installation Number in the Taxis Registry:', 'primer' ),
                    'desc'      => '',
                    'default'   => $subsidiaryId,
                    'id'        => 'subsidiaryBranchId',
                    'type'      => $currentBranchIdType,
                    'attributes' => array(
                        'readonly' => 'readonly'
                    ),
                ),
                array(
                    'name'      => __( 'City :', 'primer' ),
                    'desc'      => '',
                    'default'   => $subsidiaryCity,
                    'id'        => 'subsidiaryCity',
                    'type'      => $currentBranchIdType,
                    'attributes' => array(
                        'readonly' => 'readonly'
                    ),
                ),
                array(
                    'name'      => __( 'Address :', 'primer' ),
                    'desc'      => '',
                    'default'   => $subsidiaryAddress,
                    'id'        => 'subsidiaryAddressId',
                    'type'      => $currentBranchIdType,
                    'attributes' => array(
                        'readonly' => 'readonly'
                    ),
                ),
                array(
                    'name'      => __( 'Address Number :', 'primer' ),
                    'desc'      => '',
                    'default'   => $subsidiaryAddressNumber,
                    'id'        => 'subsidiaryNumberId',
                    'type'      => $currentBranchIdType,
                    'attributes' => array(
                        'readonly' => 'readonly'
                    ),
                ),
                array(
                    'name'      => __( 'Postal Code :', 'primer' ),
                    'desc'      => '',
                    'default'   => $subsidiaryTk,
                    'id'        => 'subsidiaryPostcodeId',
                    'type'      => $currentBranchIdType,
                    'attributes' => array(
                        'readonly' => 'readonly'
                    ),
                ),
                array(
                    'name'      => __( 'DOY :', 'primer' ),
                    'desc'      => '',
                    'default'   => $subsidiaryDoy,
                    'id'        => 'subsidiaryDoyId',
                    'type'      => $currentBranchIdType,
                    'attributes' => array(
                        'readonly' => 'readonly'
                    ),
                ),
                array(
                    'name'      => __( 'Phone Number :', 'primer' ),
                    'desc'      => '',
                    'default'   => $subsidiaryPhone,
                    'id'        => 'subsidiaryPhoneId',
                    'type'      => $currentBranchIdType,
                    'attributes' => array(
                        'readonly' => 'readonly'
                    ),
                ),
                array(
                    'name'      => __( 'Search Invoices Password', 'primer' ),
                    'desc'      => '',
                    'default'   => $connector_password,
                    'id'        => 'connector_password',
                    'type'      => 'text_medium',
                    'attributes' => array(
                        'readonly' => 'readonly'
                    ),
                    'save_field' => false,
                    'after_field' => '<a style="margin-left:20px;" target="_blank" href="https://primer.gr/search-invoice/?url-vat='.$vatNumber.'&url-password='.$connector_password.'" class="button">Search your Invoices</a>'
                ),
				array(
					'name' => __('General settings', 'primer'),
					'id' => 'general_license_settings',
					'type' => 'title',
					'desc' => ''
				),
				array(
					'name' => __('Check server compatibility', 'primer'),
					'id' => 'system_check',
					'type' => 'title',
					'after_field' => '<button class="button" id="primer_get_system_info" type="button">Check</button>',
				)
			)
		) );

		/*if (empty($primer_license_data) || $primer_license_data['mistake_license'] == 'fail') {
            $this->option_metabox[] = apply_filters('primer_instructions_option_fields', array(
                'id'		 => $prefix . 'instructions',
                'title'		 => __( 'Instructions', 'primer' ),
                'menu_title' => __( 'Instructions', 'primer' ),
                'desc'			=> __( '', 'primer' ),
                'show_on'    => array( 'key' => 'options-page', 'value' => array( 'instructions' ), ),
                'show_names' => true,
                'fields' => array(
                    array(
                        'name' => __('Instruction', 'primer'),
                        'id' => 'instruction',
                        'type' => 'title',
                        'desc' => __( '', 'primer' ),
                        'classes' => 'disable_functionality'
                    ),
                ),
            ));
        }*/

		return $this->option_metabox;

	}

    /**
     * Checks if WordPress cron is enabled.
     *
     * This function checks if the WordPress cron is enabled by checking the value of the constant
     * `DISABLE_WP_CRON`.
     * If `DISABLE_WP_CRON` is defined and its value is `true`, the function
     * returns the string 'DISABLED'. Otherwise, it returns the string 'ENABLED - OK'.
     *
     * @return string The status of the WordPress cron.
     */
	public function check_wp_cron_enabled() {
		$cron_status = '';
		if (defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON === true ) {
			$cron_status = __('DISABLED', 'primer');
		} else {
			$cron_status = __( 'ENABLED - OK', 'primer' );
		}
		return $cron_status;
	}

	/**
	 * Get the list of Woocommerce Order Statuses
	 *
	 * @since   1.0.0
	 */
	public function get_status_of_orders() {
		$order_all_statuses = array( '' => __('Select order status', 'primer') );
		$status_of_orders = wc_get_order_statuses();
		foreach ( $status_of_orders as $status_k => $status_value ) {
			$order_all_statuses[$status_k] = $status_value;
		}
		return $order_all_statuses;
	}

	/**
	 * Get the list of Woocommerce Standard rates to add to dropdowns in the settings.
	 *
	 * @since   1.0.0
	 */
	public function get_standard_rates() {
		$all_standard_tax_rates = array( '0' => __('Select Standard VAT rates', 'primer') );
		$tax_classes   = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
		if ( ! in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
			array_unshift( $tax_classes, '' );
		}
		foreach ( $tax_classes as $tax_class ) { // For each tax class, get all rates.
			$taxes         = WC_Tax::get_rates_for_tax_class( $tax_class );
			foreach ( $taxes as $tax ) {
				$tax_rate_class = $tax->tax_rate_class;
				if (empty($tax_rate_class)) {
					$tax_rate_id = $tax->tax_rate_id;
					$tax_rate_name = $tax->tax_rate_name;
					$all_standard_tax_rates[$tax_rate_id] = $tax_rate_name;
				}
			}
		}

		return $all_standard_tax_rates;
	}

    /**
     * Get the list of Woocommerce Reduced rates to add to dropdowns in the settings.
     *
     * @since   1.0.0
     */
	public function get_reduced_rates() {
		$all_reduced_tax_rates = array( '0' => __('Select Reduced VAT rates', 'primer') );

		$tax_classes   = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
		if ( ! in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
			array_unshift( $tax_classes, '' );
		}
		foreach ( $tax_classes as $tax_class ) { // For each tax class, get all rates.
			$taxes         = WC_Tax::get_rates_for_tax_class( $tax_class );
			foreach ( $taxes as $tax ) {
				$tax_rate_class = $tax->tax_rate_class;
				if (!empty($tax_rate_class) && $tax_rate_class == 'reduced-rate') {
					$tax_rate_id = $tax->tax_rate_id;
					$tax_rate_name = $tax->tax_rate_name;
					$all_reduced_tax_rates[$tax_rate_id] = $tax_rate_name;
				}
			}
		}

		return $all_reduced_tax_rates;
	}

	/**
	 * Get the list of Woocommerce Zero rates to add to dropdowns in the settings.
	 *
	 * @since   1.0.0
	 */
	public function get_zero_rates() {
		$all_zero_tax_rates = array( '0' => __('Select Zero VAT rates', 'primer') );

		$tax_classes   = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
		if ( ! in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
			array_unshift( $tax_classes, '' );
		}
		foreach ( $tax_classes as $tax_class ) { // For each tax class, get all rates.
			$taxes         = WC_Tax::get_rates_for_tax_class( $tax_class );
			foreach ( $taxes as $tax ) {
				$tax_rate_class = $tax->tax_rate_class;
				if (!empty($tax_rate_class) && $tax_rate_class == 'zero-rate') {
					$tax_rate_id = $tax->tax_rate_id;
					$tax_rate_name = $tax->tax_rate_name;
					$all_zero_tax_rates[$tax_rate_id] = $tax_rate_name;
				}
			}
		}

		return $all_zero_tax_rates;
	}

	public $receipt_client_data = array();

	public function get_clients_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
			'post_status' => 'publish',
			'order'		=> 'ASC',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();

				$user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);

				$order_id = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
				if (!empty($order_id)) {
					$order = wc_get_order( $order_id );
				}

				if (!empty($order)) {
					$order_user_first_name = $order->get_billing_first_name();
					$order_user_last_name = $order->get_billing_last_name();
				}


				$customer_full_name = get_post_meta(get_the_ID(), 'receipt_client', true);
				if (empty($customer_full_name)) {
					$customer_full_name = $order_user_first_name . ' ' . $order_user_last_name;
				}

				$user_data = get_user_by('ID', $user_display_name);

				$user_id = get_post_meta(get_the_ID(), 'receipt_client_id', true);
				if (empty($user_id)) {
					$user_id = 0;
				}

				$this->receipt_client_data[$user_id] = $customer_full_name;
//				$this->receipt_client_data[$receipt_count]['receipt_client_id'] = $user_id;
				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_client_data;
	}

	public $receipt_client_vat = array();

	public function get_client_vat_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
			'post_status' => 'publish',
			'order'		=> 'ASC',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();

				$user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);

				$order_id = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
				if (!empty($order_id)) {
					$order = wc_get_order( $order_id );
				}

				if (!empty($order)) {
					$order_user_first_name = $order->get_billing_first_name();
					$order_user_last_name = $order->get_billing_last_name();
				}


				$customer_full_name = get_post_meta(get_the_ID(), 'receipt_client', true);
				if (empty($customer_full_name)) {
					$customer_full_name = $order_user_first_name . ' ' . $order_user_last_name;
				}

				$user_data = get_user_by('ID', $user_display_name);

				$user_vat = get_user_meta($user_display_name, 'billing_vat', true);

				$user_id = get_post_meta(get_the_ID(), 'receipt_client_id', true);
				if (empty($user_id)) {
					$user_id = 0;
				}

				if (!empty($user_vat)) {
					$this->receipt_client_vat[$user_id] = $user_vat;
				}
				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_client_vat;
	}

	public $receipt_client_address = array();

	public function get_client_address_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
			'post_status' => 'publish',
			'order'		=> 'ASC',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();

				$user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);

				$order_id = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
				if (!empty($order_id)) {
					$order = wc_get_order( $order_id );
				}

				if (!empty($order)) {
					$order_user_first_name = $order->get_billing_first_name();
					$order_user_last_name = $order->get_billing_last_name();
				}


				$customer_full_name = get_post_meta(get_the_ID(), 'receipt_client', true);
				if (empty($customer_full_name)) {
					$customer_full_name = $order_user_first_name . ' ' . $order_user_last_name;
				}

				$user_data = get_user_by('ID', $user_display_name);

				$user_address = get_user_meta($user_display_name, 'billing_address_1', true);

				$user_id = get_post_meta(get_the_ID(), 'receipt_client_id', true);
				if (empty($user_id)) {
					$user_id = 0;
				}

				$this->receipt_client_address[$user_id] = $user_address;
				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_client_address;
	}

	public $receipt_client_phone = array();

	public function get_client_phone_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
			'post_status' => 'publish',
			'order'		=> 'ASC',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();

				$user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);

				$order_id = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
				if (!empty($order_id)) {
					$order = wc_get_order( $order_id );
				}

				if (!empty($order)) {
					$order_user_first_name = $order->get_billing_first_name();
					$order_user_last_name = $order->get_billing_last_name();
				}


				$customer_full_name = get_post_meta(get_the_ID(), 'receipt_client', true);
				if (empty($customer_full_name)) {
					$customer_full_name = $order_user_first_name . ' ' . $order_user_last_name;
				}

				$user_data = get_user_by('ID', $user_display_name);

				$user_phone = get_user_meta($user_display_name, 'billing_phone', true);

				$user_id = get_post_meta(get_the_ID(), 'receipt_client_id', true);
				if (empty($user_id)) {
					$user_id = 0;
				}

				$this->receipt_client_phone[$user_id] = $user_phone;
				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_client_phone;
	}

	public $receipt_client_email = array();

	public function get_client_email_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
			'post_status' => 'publish',
			'order'		=> 'ASC',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();

				$user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);

				$order_id = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
				if (!empty($order_id)) {
					$order = wc_get_order( $order_id );
				}

				if (!empty($order)) {
					$order_user_first_name = $order->get_billing_first_name();
					$order_user_last_name = $order->get_billing_last_name();
				}


				$customer_full_name = get_post_meta(get_the_ID(), 'receipt_client', true);
				if (empty($customer_full_name)) {
					$customer_full_name = $order_user_first_name . ' ' . $order_user_last_name;
				}

				$user_data = get_user_by('ID', $user_display_name);

				$user_email = get_user_meta($user_display_name, 'billing_email', true);

				$user_id = get_post_meta(get_the_ID(), 'receipt_client_id', true);
				if (empty($user_id)) {
					$user_id = 0;
				}

				$this->receipt_client_email[$user_id] = $user_email;
				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_client_email;
	}

	public $receipt_client_url = array();

	public function get_client_url_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
			'post_status' => 'publish',
			'order'		=> 'ASC',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();

				$user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);

				$order_id = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
				if (!empty($order_id)) {
					$order = wc_get_order( $order_id );
				}

				if (!empty($order)) {
					$order_user_first_name = $order->get_billing_first_name();
					$order_user_last_name = $order->get_billing_last_name();
				}


				$customer_full_name = get_post_meta(get_the_ID(), 'receipt_client', true);
				if (empty($customer_full_name)) {
					$customer_full_name = $order_user_first_name . ' ' . $order_user_last_name;
				}

				$user_data = get_user_by('ID', $user_display_name);

				if(!empty($user_data->user_url)) {
					$user_url = $user_data->user_url;
				}

				$user_id = get_post_meta(get_the_ID(), 'receipt_client_id', true);
				if (empty($user_id)) {
					$user_id = 0;
				}

				if(!empty($user_data->user_url)) {
					$this->receipt_client_url[$user_id] = $user_url;
				}

				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_client_url;
	}

	public $receipt_product_name = array();

	public function get_product_name_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
			'post_status' => 'publish',
			'order'		=> 'ASC',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();

				$user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);

				$order_id = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
				if (!empty($order_id)) {
					$order = wc_get_order( $order_id );
				}

				$product_name = '';
				$product_id = '';
				if (!empty($order)) {
					foreach ( $order->get_items() as $item_id => $item_data ) {
						$product_id = $item_data->get_product_id();
						//$product_instance = wc_get_product($product_id);
						$product_name = $item_data->get_name();
					}
				}
				if (!empty($product_id)) {
					$this->receipt_product_name[$product_name] = $product_name;
				}

				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_product_name;
	}

	public $receipt_product_vat_amount = array();

	public function get_product_vat_amount_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
			'post_status' => 'publish',
			'order'		=> 'ASC',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();

				$user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);

				$order_id = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
				if (!empty($order_id)) {
					$order = wc_get_order( $order_id );
				}

				$product_name = '';
				$product_id = '';
				$inside_tax_rate = '';

				if (!empty($order)) {
					$sum = 0;
					$item_count = 0;

					$tax_classes   = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
					if ( ! in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
						array_unshift( $tax_classes, '' );
					}

					foreach ( $order->get_items() as $item_id => $item_data ) {
						$quantity = $item_data->get_quantity();
						$sum += $quantity;

						//$product_id = $item_data->get_product_id();
						//$product_instance = wc_get_product($product_id);
						//$product_name = $product_instance->get_name();

						$product_tax_class = $item_data->get_tax_class();

						$taxes = WC_Tax::get_rates_for_tax_class( $product_tax_class );

						$tax_arr = json_decode(json_encode($taxes), true);
						foreach ( $tax_arr as $tax ) {
							if ($product_tax_class == $tax['tax_rate_class']) {
								$inside_tax_rate = $tax['tax_rate'];
							}
						}
                        if(is_int($inside_tax_rate) || is_float($inside_tax_rate)){
                            $inside_tax_rate = round($inside_tax_rate);
                        }
					}
				}

				if (empty($inside_tax_rate)) {
					$inside_tax_rate = '0';
				}

				if (!empty($item_data)) {
					$this->receipt_product_vat_amount[$inside_tax_rate] = $inside_tax_rate;
				}

				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_product_vat_amount;
	}

	public $receipt_product_total_amount = array();

	public function get_product_total_amount_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
			'post_status' => 'publish',
			'order'		=> 'ASC',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();

				$user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);

				$order_id = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
				if (!empty($order_id)) {
					$order = wc_get_order( $order_id );
				}

				$product_name = '';
				$product_id = '';
				$regular_price = '';
				if (!empty($order)) {
					foreach ( $order->get_items() as $item_id => $item_data ) {
						$product_id = $item_data->get_product_id();
					//	$product_instance = wc_get_product($product_id);
						$product_name = $item_data->get_name();
						$regular_price = $item_data->get_regular_price();
					}
				}
				if (!empty($product_id)) {
					$this->receipt_product_total_amount[$product_id] = $regular_price;
				}

				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_product_total_amount;
	}

	public $receipt_product_net_amount = array();

	public function get_product_net_amount_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
			'post_status' => 'publish',
			'order'		=> 'ASC',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();

				$user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);

				$order_id = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
				if (!empty($order_id)) {
					$order = wc_get_order( $order_id );
				}

				$product_name = '';
				$product_id = '';
				$regular_price = '';
				$subtotal_order_payment = '';
				if (!empty($order)) {
					foreach ( $order->get_items() as $item_id => $item_data ) {
						$product_id = $item_data->get_product_id();
						//$product_instance = wc_get_product($product_id);
						$product_name = $item_data->get_name();
						$regular_price = $item_data->get_regular_price();
						$subtotal_order_payment = $item_data->get_subtotal();
					}
				}
				if (!empty($product_id)) {
					$this->receipt_product_net_amount[$product_id] = $subtotal_order_payment;
				}

				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_product_net_amount;
	}

	public $receipt_total_amounts = array();

	public function get_total_amounts_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
			'post_status' => 'publish',
			'order'		=> 'ASC',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();

				$user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);

				$order_id = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
				if (!empty($order_id)) {
					$order = wc_get_order( $order_id );
				}

				$total = '';
				if (!empty($order)) {
					$total = $order->get_total();
				}

				$this->receipt_total_amounts[get_the_ID()] = $total;

				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_total_amounts;
	}

	public $receipt_total_sum_vat = array();

	public function get_total_sum_vat_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
			'post_status' => 'publish',
			'order'		=> 'ASC',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();

				$user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);

				$order_id = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
				if (!empty($order_id)) {
					$order = wc_get_order( $order_id );
				}

				$total_tax = '';
				if (!empty($order)) {
					$total_tax = $order->get_total_tax();
				}

				$this->receipt_total_sum_vat[get_the_ID()] = $total_tax;

				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_total_sum_vat;
	}

	public $receipt_invoice_date = array();

	public function get_invoice_date_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
			'post_status' => 'publish',
			'order'		=> 'ASC',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();

				$user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);

				$receipt_date = get_post_meta(get_the_ID(), 'success_mydata_date', true);
				$receipt_order_date = get_the_date('d/m/Y', get_the_ID());

				if (empty($receipt_date)) {
					$receipt_date = $receipt_order_date;
				}

				$this->receipt_invoice_date[get_the_ID()] = $receipt_date;

				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_invoice_date;
	}

	public $receipt_invoice_type = array();

	public function get_invoice_type_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
			'post_status' => 'publish',
			'order'		=> 'ASC',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();

				$user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);

				$invoice_type_text = '';
				$find_invoice_in_slug = '';

				$invoice_type = get_the_terms(get_the_ID(), 'receipt_status');
				if(!empty($invoice_type)) {
					$invoice_type_slug = $invoice_type[0]->slug;

					$this->receipt_invoice_type[$invoice_type_slug] = $invoice_type[0]->name;
				}



				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_invoice_type;
	}


	/**
	 * Returns the option key for a given field id
	 * @since  0.1.0
	 * @return array
	 */
	public function primer_get_option_key($field_id) {
		$option_tabs = $this->primer_option_fields();
		foreach ( $option_tabs as $option_tab ) { //search all tabs
			foreach ( $option_tab['fields'] as $field ) { //search all fields
				if ($field['id'] == $field_id) {
					return $option_tab['id'];
				}
			}
		}
		return $this->key; //return default key if field id not found
	}

	/**
	 * Public getter method for retrieving protected/private variables
	 * @since  0.1.0
	 * @param  string  $field Field to retrieve
	 * @return mixed          Field value or exception is thrown
	 */
	public function __get( $field ) {

		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'fields', 'menu_title', 'options_pages' ), true ) ) {
			return $this->{$field};
		}
		if ( 'option_metabox' === $field ) {
			return $this->primer_option_fields();
		}

		throw new Exception( 'Invalid property: ' . $field );
	}

	/* Render the Primer menu in admin dashboard */
	public function admin_page_display() {
		$primer = new PrimerReceipts();
		$primer->handle_main_primer_admin_menu();
	}

	public function admin_page_receipt_display() {
		include_once(PRIMER_PATH . 'admin/includes/primer-admin-receipt-table.php');
		$primer_receipt = new PrimerReceipt();
		$primer_receipt->handle_main_primer_receipt_admin_menu();
	}

	public function admin_page_receipt_log_display() {
		include_once(PRIMER_PATH . 'admin/includes/primer-admin-receipt-log-table.php');
		$primer_receipt = new PrimerReceiptLog();
		$primer_receipt->handle_main_primer_receipt_admin_menu();
	}

	public function admin_page_receipt_log_automation_display() {
		include_once(PRIMER_PATH . 'admin/includes/primer-admin-receipt-log-automation-table.php');
		$primer_receipt = new PrimerReceiptLogAutomation();
		$primer_receipt->handle_main_primer_receipt_admin_menu();
	}

    /**
     *
     * Resends a receipt to the customer.
     *
     *
     * This function retrieves the receipt IDs from the $_POST data, sanitizes them,
     * and then iterates over each receipt ID to resend the receipt to the customer.
     * The function performs the following steps:
     * 1. Retrieves the receipt IDs from the $_POST data.
     * 2. Sanitizes the receipt IDs.
     * 3. Checks if the receipt IDs are not empty and are an array.
     * 4. Iterates over each receipt ID.
     * 5. Retrieves the order ID, client ID, and order log ID associated with the receipt.
     * 6. Retrieves the billing email from the order.
     * 7. Retrieves the user data based on the client ID.
     * 8. Determines the user email based on the billing email.
     * 9. Sanitizes the user email.
     * 10. Creates the upload directory for email invoices if it doesn't exist.
     * 11. Generates the post name for the receipt.
     * 12. Checks if the attachment file exists. If not, generates the PDF file using Dompdf.
     * 13. Retrieves the SMTP options and sets the email headers and subject.
     * 14. Retrieves the email message content and replaces the placeholders with the client information.
     * 15. Sends the email using either the WordPress default SMTP or a custom SMTP.
     * 16. Handles the email sending result and generates the response message.
     * 17. Encodes the response as JSON and returns it.
     *
     */
	public function primer_resend_receipt_to_customer() {
		$receipt_ids = isset($_POST["receipts"]) ? $_POST["receipts"] : "";
        array_map( 'sanitize_text_field', $receipt_ids );

		$response = '';

		if (!empty($receipt_ids) && is_array($receipt_ids)) {
			foreach ( $receipt_ids as $receipt_id ) {
                $receipt_id = (int)$receipt_id;

                $order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
                $user_id = get_post_meta($receipt_id, 'receipt_client_id', true);
                $order_log_id = get_post_meta($receipt_id, 'log_id_for_order', true);

                $order = wc_get_order($order_id);

                $order_email = $order->get_billing_email();

                $user_data = get_user_by('ID', $user_id);

                if (empty($order_email)) {
	                $user_email = $user_data->user_email;
                } else {
	                $user_email = $order_email;
                }

                $user_email = sanitize_email($user_email);

				$upload_dir = wp_upload_dir()['basedir'];

				if (!file_exists($upload_dir . '/email-invoices')) {
					mkdir($upload_dir . '/email-invoices');
				}

				$post_name = get_the_title($receipt_id);
				$post_name = str_replace(' ', '_', $post_name);
				$post_name = str_replace('#', '', $post_name);
				$post_name = strtolower($post_name);
				$post_name = sanitize_text_field($post_name);

				$attachments = $upload_dir . '/email-invoices/'.$post_name.'.pdf';

				if (!file_exists($attachments)) {
					$post_url = get_the_permalink($receipt_id);

					$homepage = file_get_contents($post_url);

					$dompdf = new Dompdf();
					$options= $dompdf->getOptions();
					$options->setIsHtml5ParserEnabled(true);
					$dompdf->setOptions($options);

					$dompdf->loadHtml($homepage);

					$dompdf->render();

					$output = $dompdf->output();
					file_put_contents($upload_dir . '/email-invoices/'.$post_name.'.pdf', $output);

					$attachments = $upload_dir . '/email-invoices/'.$post_name.'.pdf';
				}

				$primer_smtp_options = get_option('primer_emails');
                $primer_smtp_type = $primer_smtp_options['smtp_type'];
				$headers = 'From: ' . $primer_smtp_options['from_email_field'] ? $primer_smtp_options['from_email_field'] : 'Primer '. get_bloginfo('admin_email');
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

				$mailResult = false;
                $from_name_email = $primer_smtp_options['email_from_name'];
				$primer_smtp = PrimerSMTP::get_instance();

				//$mailResult = wp_mail( $user_email, $primer_smtp_subject, $primer_smtp_message, $headers, $attachments );
                if($primer_smtp_type == 'wordpress_default'){
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    $mailResult = wp_mail($user_email,$primer_smtp_subject,$primer_smtp_message,$headers,$attachments);
                }else{
                    $mailResult = $primer_smtp->primer_mail_sender($user_email,$from_name_email, $primer_smtp_subject, $primer_smtp_message, $attachments);
                }

				if (!$mailResult && $primer_smtp_type == 'wordpress_default') {
//					$response =  '<div class="notice notice-error"><p>'.__('Email settings are not correct.', 'primer').'</p></div>';
					$response = false;
					$response_wrap = '<div class="primer_popup popup_error"><h3>'.__('Message not sent!', 'primer').'</h3></div>';

				} else {
					$response = 'success';
					$response_wrap = '<div class="primer_popup popup_success"><h3>'.__('Message sent successfully!', 'primer').'</h3></div>';
				}
                if (!empty($mailResultSMTP['error']) && $primer_smtp_type != 'wordpress_default') {
                    $response_wrap = '<div class="notice notice-error"><p>'.$GLOBALS['phpmailer']->ErrorInfo.'</p></div>';
                    update_post_meta($order_log_id, 'receipt_log_email', 'not_sent');
                    $email_logs = $GLOBALS['phpmailer']->ErrorInfo ."\n";
                    update_post_meta($order_log_id, 'receipt_log_email_error', $email_logs);
                    update_post_meta($order_log_id, 'receipt_log_total_status', 'only_errors');
                } else {
                    update_post_meta($order_log_id, 'receipt_log_email', 'sent');
                    update_post_meta($order_log_id, 'receipt_log_total_status', 'only_issued');
                }

				$json_r = json_encode(array('success' => 'true', 'status' => 'success', 'response' => $response, 'response_wrap' => $response_wrap));

			}
		}


		wp_die($json_r);
    }

    public function primer_cancel_invoice() {

        $mydata_options = get_option('primer_mydata');

        $send_characterizations = $mydata_options['send_characterizations'];
        $classificationType = '';
        $classificationCategory = '';
        $classificationCategory_en = 'category1_95';
        $api_url = $mydata_options['mydata_api'];
        $api_urls = array();
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
        $callingFunction = "primer_cancel_invoice";
        $send_api_invoice = true;
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
        $invoiceType = '';
        $post_ids = array();
        $order_ids = array();
        $log_ids = array();
        $receipt_ids = isset($_POST["receipts"]) ? $_POST["receipts"] : "";
        array_map( 'sanitize_text_field', $receipt_ids );
        $orders = array();
          if (!isset($_GET['orders']) && !empty($mydata_options['last_request']) && isset($_GET['resend_html'])) {
              $orders = $mydata_options['last_request'];
          }
        $response_data = '';
        $receipt_log_value = '';
        $receipt_log_value_array = array();
        $order_item_tax = array();
        $check_gr_tax = true;
        if (!empty($receipt_ids) && is_array($receipt_ids)) {
            foreach ( $receipt_ids as $receipt_id ) {
                $receipt_id = (int)$receipt_id;
                $order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
                $order = new WC_Order($order_id);
                $id_of_order = $order->get_id();
                $issue_date = new DateTime("now", new DateTimeZone("Europe/Athens"));
                $gr_time = $issue_date->format('Y-m-d');
                $receipt_log_id = wp_insert_post(array(
                    'post_type' => 'primer_receipt_log',
                    'post_title' => 'Credit Receipt report for #' . $id_of_order,
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                    'post_status' => 'publish',
                ));
                if (!ini_get('allow_url_fopen')) {
                    $response_data .= '<div class="primer_popup popup_error"><div><h3>'.__('Php option allow_url_fopen is disabled! Please communicate with your hosting provider in order to activate it.', 'primer') .'</h3><br><br><br><br><br></div>';
                    $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
                    $receipt_log_value .= __('Php option allow_url_fopen is disabled! Please communicate with your hosting provider in order to activate it.', 'primer');
                    break ;
                }
                $order_paid_date = null;
                $order_paid_hour = null;
                if (!empty($order->get_date_paid())) {
                    $order_paid_date = date( 'F j, Y', $order->get_date_paid()->getTimestamp());
                    $order_paid_hour = date( 'H:i:s', $order->get_date_paid()->getTimestamp());
                } else {
                    $order_paid_date = date( 'F j, Y', $order->get_date_created()->getTimestamp());
                    $order_paid_hour = date( 'H:i:s', $order->get_date_created()->getTimestamp());
                }
                update_post_meta($receipt_log_id, 'receipt_log_order_id', $id_of_order);
                if (!empty($receipt_log_id)) {
                    update_post_meta($id_of_order, 'credit_log_id_for_order', $receipt_log_id);
                    update_post_meta($receipt_log_id, 'receipt_log_order_date', $order_paid_date);
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
                    $insert_taxonomy = 'receipt_status';
                    $serie = '';
                    $series = '';
                    $number = '';
                    $invoice_time = '';
                    $invoice_term = '';
                    $response_data = '';
                    $invoiceType = '';
                    $receipt_log_value = '';
                    $total = '';
                    $get_mark_from_receipt = '';
                    $create_json_instance = new Create_json();
                    $Vat_exemption_categories = $create_json_instance->getVatExemptionCategories();
                    $Vat_exemption_categories_en = $create_json_instance->getVatExemptionCategoriesEn();
                    $mydata_options = get_option('primer_mydata');
                    $invoice_data = $create_json_instance->create_invoice($user_id, $order_id, $total_vat_number, $mydata_options, $primer_license_data,
                        $total, $series, $serie, $number, $currency, $currency_symbol, $user_data, $insert_taxonomy,
                        $classificationCategory, $classificationCategory_en, $response_data, $receipt_log_value, $receipt_log_value_array,
                        $receipt_log_id, $invoice_term, $gr_time, $invoice_time, $order_total_price, $order_invoice_type,
                        $order_vatNumber, $user_order_email, $order_country, $user_full_name, $primer_smtp, $log_ids, $callingFunction, $invoiceType,
                        $send_api_invoice, $classificationType, true, $receipt_id);
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
                    $is_timeout = false ;
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
                            $is_timeout = true ;
                            $first_time = get_post_meta($id_of_order, 'order_date_failed_timeout',true);
                            if($first_time == null){
                                update_post_meta($id_of_order, 'order_date_failed_timeout',$gr_time);
                                $first_time = get_post_meta($id_of_order, 'order_date_failed_timeout',true);
                            }
                        }
                    } else {
                        $response_to_array = wp_remote_retrieve_body($response);
                    }
                    $time_for_call_timeout_48 = get_post_meta($id_of_order, 'order_date_failed',true);
                    $time_for_call_timeout_1 = '';
                    if($time_for_call_timeout_48 && ($response_code > 500 || $is_timeout)){
                        $time_for_call_timeout_1 = date('Y-m-d H:i:s', strtotime($time_for_call_timeout_48. ' + 2 days'));

                        if($time_for_call_timeout_1 > $gr_time ){
                            $mydata_options['timeout_check_48'] = 0;
                        }else{
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
                                                                                $response_data, $receipt_log_id, $url_slug, $callingFunction, $generated_uid, null,null);

                        if ( $status == "break" ) {
                            continue;
                        } else {
                            //echo "<br>";
                            update_option('primer_mydata', $mydata_options);
                            $response = array(
                                'status' => 'success',
                                'data' => $response_data
                            );

                            echo json_encode($response);
                            delete_transient('convert_order_to_invoice_lock');
                            wp_die();
                        }
                        //end checking
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
                                        'post_title' => 'Credit Receipt for order #' . $id_of_order,
                                        'comment_status' => 'closed',
                                        'ping_status' => 'closed',
                                        'post_status' => 'publish',
                                    ));
                                    wp_set_object_terms($post_id, $invoice_term, $insert_taxonomy, false);
                                    if ($post_id) {
                                        $issue_date = new DateTime("now", new DateTimeZone("Europe/Athens"));
                                        $invoice_date = $issue_date->format('d/m/Y');
                                        $invoice_time = $issue_date->format('H:i');
                                        update_post_meta($post_id, 'credit_success_mydata_date', $invoice_date);
                                        update_post_meta($post_id, 'credit_success_mydata_time', $invoice_time);
                                        update_post_meta($post_id, 'receipt_type', $invoice_term);
                                        update_post_meta($post_id, 'send_to_api_type', $api_type);
                                        update_post_meta($post_id, 'order_id_to_receipt', $id_of_order);
                                        update_post_meta($id_of_order, 'order_id_from_credit_receipt', $post_id);
                                        add_post_meta($post_id, 'receipt_client', $user_data);
                                        add_post_meta($post_id, 'receipt_client_id', $user_id);
                                        add_post_meta($post_id, 'receipt_price', $order_total_price . ' ' .$currency_symbol);
                                        update_post_meta( $post_id, '_primer_receipt_number', $number );
                                        update_post_meta( $post_id, '_primer_receipt_series', $serie );
                                        update_post_meta( $post_id, 'credit_receipt', 'yes' );
                                        // update the invoice number
                                        //increment directly from json( was not working with above function for multiple conversions)
                                        $create_json_instance -> numbering($order_invoice_type, $order_country,$mydata_options,$series, true);
                                        update_option( 'primer_mydata', $mydata_options );
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
                                            $currentBranchID = "0";
                                        } else {
                                            $currentBranchID = get_post_meta($receipt_id, 'branchID', true);
                                        }
                                        update_post_meta($post_id, 'branchID', $currentBranchID);
                                        if ( $serie == "EMPTY" ) {
                                            $identifier = "0" . "_" . $number . "_" . $invoice_term . "_" . $currentBranchID;
                                        } else {
                                            $identifier = $serie . "_" . $number . "_" . $invoice_term . "_" . $currentBranchID;
                                        }
                                        update_post_meta($post_id, 'numbering_identifier', $identifier);
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
                                    $zip_response = '';
                                    $upload_dir = wp_upload_dir()['basedir'];
                                    $upload_url = wp_upload_dir()['baseurl'] . '/exported_html_files';
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
                                        $zip_response = ($create_zip == 'created') ? $upload_url . '/'.$post_ids_str.'_html.zip' : false;
                                    }
                                    if ($zip_response !== false) {
                                        $html_body_args['invoiceFile'] = curl_file_create(WP_CONTENT_DIR . '/uploads/exported_html_files/'.$post_ids_str.'_html.zip', 'application/zip', $post_ids_str.'_html.zip');
                                    }
                                            $post_url = get_the_permalink($post_id);
                                            $post_url = $post_url . '?receipt=view&username='.$primer_license_data['username'];
                                            $arrContextOptions=array(
                                                "ssl"=>array(
                                                    "verify_peer"=>false,
                                                    "verify_peer_name"=>false,
                                                ),
                                            );
                                            $homepage = file_get_contents($post_url, false, stream_context_create($arrContextOptions));
                                            // instantiate and use the dompdf class
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
                                            $headers = 'From: ' . $primer_smtp_options['from_email_field'] ? $primer_smtp_options['from_email_field'] : 'Primer '. get_bloginfo('admin_email');
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
                                            $order_log_id = get_post_meta($id_of_order, 'credit_log_id_for_order', true);

                                            if ($order_log_id) {
                                                update_post_meta($post_id, 'credit_log_id_for_order', $order_log_id);
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
                                            if (!empty($primer_automatically_send_file) && $primer_automatically_send_file === 'yes' && $user_order_email != '' && $user_order_email != null) {
                                                $mailResult = false;
                                                $primer_smtp = PrimerSMTP::get_instance();
                                                if (!empty($primer_smtp_options['email_from_name'])) {
                                                    $from_name_email = $primer_smtp_options['email_from_name'];
                                                } else {
                                                    $from_name_email = '';
                                                }
                                                if($primer_smtp_type == 'wordpress_default'){
                                                    $headers = array('Content-Type: text/html; charset=UTF-8');
                                                    $mailResultSMTP = wp_mail($user_order_email,$primer_smtp_subject,$primer_smtp_message,$headers,$attachments);
                                                }else{
                                                    $mailResultSMTP = $primer_smtp->primer_mail_sender($user_order_email,$from_name_email, $primer_smtp_subject, $primer_smtp_message, $attachments);
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
                                                update_post_meta($id_of_order, 'cancelled','yes');
                                                update_post_meta($receipt_id, 'cancelled','yes');
									if (($response_code > 500 || $is_timeout) && $general_settings['primer_cron_transmission_failure'] == 'on') {
                                        if ($response_code == 512 ) {
                                            $receipt_log_value .= __('Unable to connect Provider and AADE.', 'primer');
                                            $receipt_log_value_array[] = __('Unable to connect Provider and AADE.', 'primer');
                                            update_post_meta($id_of_order, 'transmission_failure_check', 3);
                                            update_post_meta($post_id, 'transmission_failure_check', 3);
                                            update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                                        } else {
                                            $receipt_log_value .= __('Unable to connect Entity and provider..', 'primer');
                                            $receipt_log_value_array[] = __('Unable to connect Entity and provider.', 'primer');
                                            update_post_meta($id_of_order, 'transmission_failure_check', 1);
                                            update_post_meta($post_id, 'transmission_failure_check', 1);
                                            update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                                        }
                                        $gr_timezone = new DateTimeZone("Europe/Athens");
                                        $gr_time = new DateTime("now", $gr_timezone);
										update_post_meta($id_of_order, 'order_date_failed', $gr_time->format('Y-m-d'));
                                        update_post_meta($id_of_order, 'order_datetime_failed', $gr_time->format('Y-m-d H:i:s'));
										update_post_meta($id_of_order, 'credit_receipt_failed_to_issue', 'yes');
									}
                                    //$response_data = '<div class="primer_popup popup_success">' . '<h3>Orders converted!!!</h3>' . '<br>' . '<br>' . '<button type="button" class="popup_ok button button-primary">OK</button>' . '</div>';
                                    $response_data = '<div class="primer_popup popup_success"><div>';
                                    $response_data .= '<h3>'.__("Orders converted", "primer").'</h3><br><br><br><br><br>';
                                    $response_data .= '<button class="popup_ok button button-primary">OK</button>';
                                    $response_data .= '</div></div>';
                                } else {
                                    update_option('primer_mydata', $mydata_options);
                                    $response_data .= is_object($response_from_array[0]->errors) ? json_encode($response_from_array[0]->errors) : $response_from_array[0]->errors;
                                    $receipt_log_value_array[] = $response_data;
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

        update_option('primer_mydata', $mydata_options);
        wp_die();
    }

    /***
     * Get the file permissions for all files in the plugin folder.
     *
     * @return array An associative array where the keys are the file names and the values are the file permissions.
     */
    public function primer_get_file_permissions() {
        // Empty array to store return values
		$file_permissions = array();
        // Get PRIMER_SERVER_PATH from primer-options.php file
		$plugin_folder = PRIMER_SERVER_PATH;
        // Clear cache before scanning
		clearstatcache();
        //
		$scanned_directory = array_diff(scandir($plugin_folder), array('..', '.', '.git'));
        // Iterate over each file in the scanned directory
		foreach ( $scanned_directory as $plugin_file ) {
            // Check if the file is a regular file
			if (is_file($plugin_folder.'/'.$plugin_file)) {
                // Get the file permissions
				$perm = fileperms($plugin_folder.'/'.$plugin_file);
                // Add the file name and permissions to the array
				$file_permissions[$plugin_file] = substr(sprintf('%o', $perm), -4);
			}
		}
        // Return the array of file permissions
        return $file_permissions;
	}

	public function primer_get_folder_permissions() {
    $folder_permissions = array();
    $plugin_folder = PRIMER_SERVER_PATH;
    clearstatcache();
    $scanned_directory = array_diff(scandir($plugin_folder), array('..', '.', '.git'));

    foreach ( $scanned_directory as $plugin_file ) {
        if (is_dir($plugin_folder.'/'.$plugin_file)) {
            $perm = fileperms($plugin_folder.'/'.$plugin_file);
            $folder_permissions[$plugin_file] = substr(sprintf('%o', $perm), -4);
        }
    }
    return $folder_permissions;
}

    public function primer_get_series(){
        $mydata_options = get_option('primer_mydata');
        $series = $mydata_options['invoice_numbering_gr_series'];
        $response_data['message']  = $series;
        wp_die(json_encode($response_data));
    }

	public function primer_get_plugin_version() {
		$licenses = get_option('primer_licenses');
		$username = $licenses['username'] ? $licenses['username'] : '';
		$password = $licenses['password'] ? $licenses['password'] : '';
		$auth = base64_encode( "$username" . ':' . "$password" );
        $url = 'https://wp-mydataapi.primer.gr/v2/config/pluginVersion';
		$mydata_options = get_option('primer_mydata');
		$api_type = isset($mydata_options['mydata_api']) ? sanitize_text_field($mydata_options['mydata_api']) : '';
		$request_args = array(
			'timeout' 		=> 0,
			'method'		=> 'GET',
			'httpversion' 	=> '1.1',
			'headers'		=> array(
				'Authorization' => 'Basic ' . $auth,
				'Content-Type'	=> 'application/json'
			),
			'sslverify'		=> false
		);
		$response = wp_remote_get($url, $request_args);
		$body = wp_remote_retrieve_body( $response );
		return $body;

	}

	public function primer_system_settings() {
		// verify the nonce
		//$nonce = isset( $_REQUEST['_ajax_nonce'] ) ? $_REQUEST['_ajax_nonce'] : false;

		//check_ajax_referer( 'primer_ajax_nonce', '_ajax_nonce' );

		//if( ! wp_verify_nonce( $nonce, 'primer_ajax_nonce' ) ) die( 'Stop!');

		$response = '';
		$cron_status_text = '';
		$version_status_text = '';
		$file_permission_text = '';
		$folder_permission_text = '';
		$permission_of_files = $this->primer_get_file_permissions();
		$permission_of_folders = $this->primer_get_folder_permissions();

		$folder_permission_values = array_values($permission_of_folders);
		$folder_permission_value = array_unique($folder_permission_values);

		if (!empty($folder_permission_value) && is_array($folder_permission_value)) {
			$folder_permission_text = $folder_permission_value[0];
			if ($folder_permission_text == '0777' || $folder_permission_text == '0755') {
				$folder_permission_text = '<p><span class="info_success">'.__('Pass', 'primer').'</span></p>';
			} else {
				$folder_permission_text = '<p><span class="info_error">'.__('FAIL - correct folder permissions are: 755 for directories', 'primer').'</span></p>';
			}
		}

		$file_permission_values = array_values($permission_of_files);
		$file_permission_value = array_unique($file_permission_values);

		if (!empty($file_permission_value) && is_array($file_permission_value)) {
			$file_permission_text = $file_permission_value[0];
			if ($file_permission_text == '0666' || $file_permission_text == '0644') {
				$file_permission_text = '<p><span class="info_success">'.__('Pass', 'primer').'</span></p>';
			} else {
				$file_permission_text = '<p><span class="info_error">'.__('FAIL - correct files permissions are: 644 for files', 'primer').'</span></p>';
			}
		}

		$cron_status = $this->check_wp_cron_enabled();
		$pos = stripos($cron_status, 'ENABLED');
		if ($pos !== false) {
			$cron_status_text = '<p><span class="info_success">'.__('Pass', 'primer').'</span></p>';
		} else {
			$cron_status_text = '<p><span class="info_error">'.__('Fail', 'primer').'</span></p>';
		}

		$api_plugin_version = $this->primer_get_plugin_version();
		$system_plugin_version = PRIMER_VERSION;

		$case_versions = strcasecmp($system_plugin_version, $api_plugin_version);
		if ($case_versions == 0) {
			$version_status_text = '<p><span class="info_success">'.__('Pass. The versions match', 'primer').'</span></p>';
		} else {
			$version_status_text = '<p><span class="info_error">'.__('Fail. The versions do not match', 'primer').'</span></p>';
			$version_status_text .= '<input type="hidden" class="sys_version" value="'.$system_plugin_version.'">';
			$version_status_text .= '<input type="hidden" class="api_version" value="'.$api_plugin_version.'">';
		}

        $wordpress_version_text = '';
        $wordpress_curennt_version = get_bloginfo( 'version' );
        $url = 'https://api.wordpress.org/core/version-check/1.7/';
        $response = wp_remote_get($url);
        $json = $response['body'];
        $obj = json_decode($json);
        $upgrade = $obj->offers[0];
        $wordpress_latest_version = $upgrade->version;
        if(version_compare($wordpress_curennt_version,'5.0.0') == 1 && $wordpress_curennt_version != $wordpress_latest_version){
            $wordpress_version_text = '<p><span class="info_wordpress_version_not_latest">Current Version: '.$wordpress_curennt_version.' Please update to the newest wordpress version '.$wordpress_latest_version.'</span></p>';
        }elseif(version_compare($wordpress_curennt_version,'5.0.0') == 1 && $wordpress_curennt_version == $wordpress_latest_version){
            $wordpress_version_text = '<p><span class="info_success">Current Version: '.$wordpress_curennt_version.' Latest Version</span></p>';
        }elseif(version_compare($wordpress_curennt_version,'5.0.0') == -1){
            $wordpress_version_text = '<p><span class="info_error">Current Version: '.$wordpress_curennt_version.' Caution! Please update to the latest version in order the plugin to be fully functionable</span></p>';
        }
        $woocommerce_latest_version = '1';
        $woocommerce_version_text = '';
        $woocommerce_current_version = '';
        if (defined('WC_VERSION')){
            $woocommerce_current_version = WC_VERSION;
        }
        $update_plugins = get_site_transient('update_plugins');
        if ($update_plugins && !empty($update_plugins->response)) {
            foreach ($update_plugins->response as $plugin => $vals) {
                if($vals->slug == 'woocommerce') {
                    $woocommerce_latest_version = $vals->new_version;
                }
            }
        }
        if($woocommerce_current_version == $woocommerce_latest_version || $woocommerce_latest_version == 1){
            $woocommerce_version_text = '<p><span class="info_success">Current Version: '.$woocommerce_current_version.' Latest Version</span></p>';
        }elseif($woocommerce_current_version != $woocommerce_latest_version && version_compare($woocommerce_current_version,'3.5.0') == 1){
            $woocommerce_version_text = '<p><span class="info_wordpress_version_not_latest">Current Version: '.$woocommerce_current_version.' Please update to the newest woocommerce version '.$woocommerce_latest_version.'</span></p>';
        }elseif(version_compare($woocommerce_current_version,'3.5.0') == -1){
            $woocommerce_version_text = '<p><span class="info_error">Current Version: '.$woocommerce_current_version.' Caution! Please update to the latest version in order the plugin to be fully functionable</span></p>';
        }


		$response = 'success';
		$response_wrap = '<div class="primer_popup popup_check_info"><h3>'.__("Server compatibility check", "primer").'</h3>';
		$response_wrap .= '<p>'.__("Results: ", "primer").'</p>';
		$response_wrap .= '<div class="info_wrap">';

		$response_wrap .= '<div class="info-row">';
		$response_wrap .= '<div class="info-label">'.__('Cron capability: ', 'primer').'</div>';
		$response_wrap .= '<div class="info-value">'.$cron_status_text.'</div>';
		$response_wrap .= '</div>';

		$response_wrap .= '<div class="info-row">';
		$response_wrap .= '<div class="info-label">'.__('Folders permissions: ', 'primer').'</div>';
		$response_wrap .= '<div class="info-value">'.$folder_permission_text.'</div>';
		$response_wrap .= '</div>';

		$response_wrap .= '<div class="info-row">';
		$response_wrap .= '<div class="info-label">'.__('File permissions: ', 'primer').'</div>';
		$response_wrap .= '<div class="info-value">'.$file_permission_text.'</div>';
		$response_wrap .= '</div>';

		$response_wrap .= '<div class="info-row">';
		$response_wrap .= '<div class="info-label">'.__('Time zone: ', 'primer').'</div>';
		$response_wrap .= '<div class="info-value">'.primer_check_time_difference().'</div>';
		$response_wrap .= '</div>';

		$response_wrap .= '<div class="info-row">';
		$response_wrap .= '<div class="info-label">'.__('Latest plugin version: ', 'primer').'</div>';
		$response_wrap .= '<div class="info-value">'.$version_status_text.'</div>';
		$response_wrap .= '</div>';

        $response_wrap .= '<div class="info-row">';
        $response_wrap .= '<div class="info-label">'.__('Wordpress version: ', 'primer').'</div>';
        $response_wrap .= '<div class="info-value">'.$wordpress_version_text.'</div>';
        $response_wrap .= '</div>';

        $response_wrap .= '<div class="info-row">';
        $response_wrap .= '<div class="info-label">'.__('Woocommerce version: ', 'primer').'</div>';
        $response_wrap .= '<div class="info-value">'.$woocommerce_version_text.'</div>';
        $response_wrap .= '</div>';

		$response_wrap .= '</div>';
		$response_wrap .= '</div>';

		echo json_encode(array('success' => 'true', 'status' => 'success', 'response' => $response, 'response_wrap' => $response_wrap));

		wp_die();
	}

    /**
     * Performs the first time activation of the invoice.
     *
     * This function retrieves the username and password from the $_POST superglobal,
     * encodes them in base64, and sends a GET request to the API endpoint
     * 'https://wp-mydataapi.primer.gr/v2/invoice/firstTimeActivation'.
     * The response body is retrieved and decoded into an associative array.
     * If the response code is not 200, the response data is set to indicate failure.
     * Otherwise, the response data is set to indicate success.
     * The response data also includes the server name, URL for the terms of use text file,
     * and the Greek translation of the terms of use.
     *
     */
    public function first_time_act(){
		$username = isset( $_POST['username'] ) ? sanitize_text_field( $_POST['username'] ) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        $api_url = 'https://wp-mydataapi.primer.gr/v2/invoice/firstTimeActivation';
        $auth = base64_encode( "$username" . ':' . "$password" );
		$curl_args = array(
			'timeout'     => 10,
			'method'      => 'GET',
			'httpversion' => '1.1',
			'headers'     => array(
				'Authorization' => 'Basic ' . $auth,
				'Content-Type'  => 'application/json'
			),
			'sslverify'   => false
		);

		$response = wp_remote_request( $api_url, $curl_args );

		$response_body    = wp_remote_retrieve_body( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$response_to_array = json_decode( $response_body );

		if ( ! empty( $response_body ) ) {
            if ($response['response']['code'] !== 200) {
                $response_data['response'] = false;
                $response_data['message'] = $response_body;
            } else {
                $response_data['response'] = true;
                $response_data['message'] = $response_body;
            }
        }
            $response_data['response'] = false;
			$response_data['message'] = $response_body;
            $response_data['username'] = $username;

        $second_level_domains_regex = '/\.asn\.au$|\.com\.au$|\.net\.au$|\.id\.au$|\.org\.au$|\.edu\.au$|\.gov\.au$|\.csiro\.au$|\.act\.au$|\.nsw\.au$|\.nt\.au$|\.qld\.au$|\.sa\.au$|\.tas\.au$|\.vic\.au$|\.wa\.au$|\.co\.at$|\.or\.at$|\.priv\.at$|\.ac\.at$|\.avocat\.fr$|\.aeroport\.fr$|\.veterinaire\.fr$|\.co\.hu$|\.film\.hu$|\.lakas\.hu$|\.ingatlan\.hu$|\.sport\.hu$|\.hotel\.hu$|\.ac\.nz$|\.co\.nz$|\.geek\.nz$|\.gen\.nz$|\.kiwi\.nz$|\.maori\.nz$|\.net\.nz$|\.org\.nz$|\.school\.nz$|\.cri\.nz$|\.govt\.nz$|\.health\.nz$|\.iwi\.nz$|\.mil\.nz$|\.parliament\.nz$|\.ac\.za$|\.gov\.za$|\.law\.za$|\.mil\.za$|\.nom\.za$|\.school\.za$|\.net\.za$|\.co\.uk$|\.org\.uk$|\.me\.uk$|\.ltd\.uk$|\.plc\.uk$|\.net\.uk$|\.sch\.uk$|\.ac\.uk$|\.gov\.uk$|\.mod\.uk$|\.mil\.uk$|\.nhs\.uk$|\.police\.uk$/';
        $domain = $_SERVER['SERVER_NAME'];
        $domain = explode('.', $domain);
        $domain = array_reverse($domain);
        if (preg_match($second_level_domains_regex, $_SERVER['SERVER_NAME'])) {
            $domain = "$domain[2].$domain[1].$domain[0]";
    }else{
            $domain = "$domain[1].$domain[0]";
        }
        $response_data['server_name'] = $domain;
            $response_data['url_for_txt'] = plugins_url('/primer-mydata/admin/includes/termsofuse.txt');
            $response_data['terms']= __( 'ΟΡΟΙ ΚΑΙ ΠΡΟΫΠΟΘΕΣΕΙΣ');

       wp_die(json_encode($response_data));


        }

    /**
     * Sends a company activation request to the API.
     *
     * This function sends a POST request to the API endpoint for company activation.
     * It retrieves the necessary data from the $_POST global and sanitizes it.
     * The sanitized data is then used to construct the request body.
     * If the subsidiaries are present, they are also sanitized and included in the request body.
     * The response from the API is then processed and returned as a JSON-encoded string.
     *
     * @throws WP_Error If there is an error in the API request.
     */
    public function company_activation_call(){

    $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $company_name = isset($_POST['company_name']) ? sanitize_text_field($_POST['company_name']) : '';
    $small_name = isset($_POST['small_name']) ? sanitize_text_field($_POST['small_name']) : '';
    $activity = isset($_POST['activity']) ? sanitize_text_field($_POST['activity']) : '';
    $c_phone = isset($_POST['c_phone']) ? sanitize_text_field($_POST['c_phone']) : '';
    $c_vat = isset($_POST['c_vat']) ? sanitize_text_field($_POST['c_vat']) : '';
    $gemh = isset($_POST['gemh']) ? sanitize_text_field($_POST['gemh']) : '';
    $cp_adress = isset($_POST['cp_adress']) ? sanitize_text_field($_POST['cp_adress']) : '';
    $r_name = isset($_POST['r_name']) ? sanitize_text_field($_POST['r_name']) : '';
    $r_sname = isset($_POST['r_sname']) ? sanitize_text_field($_POST['r_sname']) : '';
    $r_vat = isset($_POST['r_vat']) ? sanitize_text_field($_POST['r_vat']) : '';
    $cp_email = isset($_POST['cp_email']) ? sanitize_email($_POST['cp_email']) : '';
    $cp_city= isset($_POST['cp_city']) ? sanitize_text_field($_POST['cp_city']) : '';
    $cp_tk= isset($_POST['cp_tk']) ? sanitize_text_field($_POST['cp_tk']) : '';
    $cp_doy= isset($_POST['doy']) ? sanitize_text_field($_POST['doy']) : '';

    $subsidiaries_json = isset($_POST['subsidiaries']) ? wp_unslash($_POST['subsidiaries']) : '';
    $subsidiaries = $subsidiaries_json;

    $api_url = 'https://wp-mydataapi.primer.gr/v2/invoice/companyActivation';
    $auth = base64_encode( "$username" . ':' . "$password" );
	$curl_args = array(
		'timeout' 		=> 10,
		'method' => 'POST',
		'httpversion' 	=> '1.1',
		'headers'		=> array(
			'Authorization' => 'Basic ' . $auth,
			'Content-Type'	=> 'application/json'
		),
		'sslverify'		=> false
	);
    $second_level_domains_regex = '/\.asn\.au$|\.com\.au$|\.net\.au$|\.id\.au$|\.org\.au$|\.edu\.au$|\.gov\.au$|\.csiro\.au$|\.act\.au$|\.nsw\.au$|\.nt\.au$|\.qld\.au$|\.sa\.au$|\.tas\.au$|\.vic\.au$|\.wa\.au$|\.co\.at$|\.or\.at$|\.priv\.at$|\.ac\.at$|\.avocat\.fr$|\.aeroport\.fr$|\.veterinaire\.fr$|\.co\.hu$|\.film\.hu$|\.lakas\.hu$|\.ingatlan\.hu$|\.sport\.hu$|\.hotel\.hu$|\.ac\.nz$|\.co\.nz$|\.geek\.nz$|\.gen\.nz$|\.kiwi\.nz$|\.maori\.nz$|\.net\.nz$|\.org\.nz$|\.school\.nz$|\.cri\.nz$|\.govt\.nz$|\.health\.nz$|\.iwi\.nz$|\.mil\.nz$|\.parliament\.nz$|\.ac\.za$|\.gov\.za$|\.law\.za$|\.mil\.za$|\.nom\.za$|\.school\.za$|\.net\.za$|\.co\.uk$|\.org\.uk$|\.me\.uk$|\.ltd\.uk$|\.plc\.uk$|\.net\.uk$|\.sch\.uk$|\.ac\.uk$|\.gov\.uk$|\.mod\.uk$|\.mil\.uk$|\.nhs\.uk$|\.police\.uk$/';
    $domain = $_SERVER['SERVER_NAME'];
    $domain = explode('.', $domain);
    $domain = array_reverse($domain);
    if (preg_match($second_level_domains_regex, $_SERVER['SERVER_NAME'])) {
        $domain_name = "$domain[2].$domain[1].$domain[0]";
    }else{
        $domain_name = "$domain[1].$domain[0]";
    }

    if (!empty($subsidiaries)) {
        // Handle the case when subsidiaries are present
        // You may need to adjust this part based on the structure of your data
        $subsidiaries = sanitize_text_field($subsidiaries);
        $curl_args['body'] = '{
        "companyName": "'.$company_name.'",
        "smallName": "'.$small_name.'",
        "address": "'.$cp_adress.'",
        "activity": "'.$activity.'",
        "vatNumber": "'.$c_vat.'",
        "phoneNumber": "'.$c_phone.'",
        "gemhNumber": "'.$gemh.'",
        "representativeName": "'.$r_name.'",
        "representativeSurname": "'.$r_sname.'",
        "representativeVatNumber": "'.$r_vat.'",
        "city": "'.$cp_city.'",
        "tk": "'.$cp_tk.'",
        "email": "'.$cp_email.'",
        "doy": "'.$cp_doy.'",
        "domain": "'.$domain_name.'",
        "subsidiaries": '.$subsidiaries.'
    }';
    } else {
        // Handle the case when subsidiaries are not present
        $curl_args['body'] = '{
        "companyName": "'.$company_name.'",
        "smallName": "'.$small_name.'",
        "address": "'.$cp_adress.'",
        "activity": "'.$activity.'",
        "vatNumber": "'.$c_vat.'",
        "phoneNumber": "'.$c_phone.'",
        "gemhNumber": "'.$gemh.'",
        "representativeName": "'.$r_name.'",
        "representativeSurname": "'.$r_sname.'",
        "representativeVatNumber": "'.$r_vat.'",
        "city": "'.$cp_city.'",
        "tk": "'.$cp_tk.'",
        "email": "'.$cp_email.'",
        "doy": "'.$cp_doy.'",
        "domain": "'.$domain_name.'"
    }';
    }

	$response = wp_remote_request($api_url, $curl_args);
	$response_body = wp_remote_retrieve_body($response);
	$response_message = wp_remote_retrieve_response_message($response);

    $response_to_array = json_decode($response_body);

	if (!is_wp_error($response)) {
                $response_data['response'] = true;
                $response_data['message']  = 'Success';
            }else{
                $response_data['response'] = false;
		$response_data['message']  = json_decode($response_message);
        $response_data['username'] = $username;
    }
    $response_data['message']  = $response_body;
    wp_die(json_encode($response_data));
}

    /**
     * Sends a company VAT number request to the API and returns the company data as a JSON-encoded string.
     *
     */
    public function company_vat_call() {
        $vat_number = isset($_POST['vatNumber']) ? sanitize_text_field($_POST['vatNumber']) : '';
        $username = 'admin';
        $password = 'kpW1bucbQcUW';
        $api_url = 'https://test-mydataapi.primer.gr/v2/invoice/getCompanyInfo';

        $data = [
            'vatNumber' => $vat_number,
            'onlyAADE' => 'true',
        ];

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("$username:$password"),
            'Content-Type' => 'application/json',
        ];

        // cURL arguments
        $curl_args = array(
            'timeout' => 10,
            'method' => 'POST',
            'headers' => $headers,
            'body' => json_encode($data),
            'sslverify' => false,
        );


        $response  = wp_remote_post($api_url, $curl_args);

        if (is_wp_error ($response)) {
            wp_send_json(['error' => $response->get_error_message()]);
        }

        $body = wp_remote_retrieve_body ($response);
        $company_data = json_decode($body,true);
        wp_send_json($company_data);
    }

    /**
     * Changes the active subsidiary and resets the invoice and credit series for the new subsidiary.
     *
     */
    public function change_subsidiary() {
        $branchID = isset($_POST['branchID']) ? $_POST['branchID'] : 0;
        $licenses = get_option('primer_licenses');
        $mydata_options = get_option('primer_mydata');
        $mydata_options_sub = $mydata_options ;

        $response_data = array();
        if ($branchID == $licenses['currentBranchID']) {
            $response_data['message'] = 'Η ενεργοποίηση ολοκληρώθηκε.';
            wp_die(json_encode($response_data));
        } else {
            $licenses['currentBranchID'] = $branchID;
            $reset_series_sub  = array('EMPTY',
                    'A',
                    'B',
                    'C',
                    'D',
                    'E',
                    'Z',
                    'H',
                    'Q',
                    'I',
                    'K',
                    'L',
                    'M',
                    'N',
                    'J',
                    'O',
                    'P',
                    'R',
                    'S',
                    'T',
                    'Y',
                    'F',
                    'X',
                    'W',
                    'V');
            $mydata_options_sub['invoice_numbering_gr_series'] = 'EMPTY';
            $mydata_options_sub['invoice_numbering_gi_series'] = 'EMPTY';
            $mydata_options_sub['invoice_numbering_within_series'] = 'EMPTY';
            $mydata_options_sub['invoice_numbering_outside_series'] = 'EMPTY';
            $mydata_options_sub['invoice_numbering_gr_test_api_series'] = 'EMPTY';
            $mydata_options_sub['invoice_numbering_gi_test_api_series'] = 'EMPTY';
            $mydata_options_sub['invoice_numbering_within_test_api_series'] = 'EMPTY';
            $mydata_options_sub['invoice_numbering_outside_test_api_series'] = 'EMPTY';
            $mydata_options_sub['credit_receipt_series'] = 'EMPTY';
            $mydata_options_sub['credit_invoice_series'] = 'EMPTY';
            $mydata_options_sub['credit_receipt_test_api_series'] = 'EMPTY';
            $mydata_options_sub['credit_invoice_test_api_series'] = 'EMPTY';
            foreach($reset_series_sub as $reset_serie_sub) {
                $mydata_options_sub['invoice_numbering_gr_'.$reset_serie_sub.''] = 1;
                $mydata_options_sub['invoice_numbering_gi_'.$reset_serie_sub.''] = 1;
                $mydata_options_sub['invoice_numbering_within_'.$reset_serie_sub.''] = 1;
                $mydata_options_sub['invoice_numbering_outside_'.$reset_serie_sub.''] = 1;
                $mydata_options_sub['invoice_numbering_gr_'.$reset_serie_sub.'_test_api'] = 1;
                $mydata_options_sub['invoice_numbering_gi_'.$reset_serie_sub.'_test_api'] = 1;
                $mydata_options_sub['invoice_numbering_within_'.$reset_serie_sub.'_test_api'] = 1;
                $mydata_options_sub['invoice_numbering_outside_'.$reset_serie_sub.'_test_api'] = 1;
                $mydata_options_sub['credit_receipt_'.$reset_serie_sub.''] = 1;
                $mydata_options_sub['credit_invoice_'.$reset_serie_sub.''] = 1;
                $mydata_options_sub['credit_receipt_'.$reset_serie_sub.'_test_api'] = 1;
                $mydata_options_sub['credit_invoice_'.$reset_serie_sub.'_test_api'] = 1;
            }
            sleep(2);
            update_option('primer_mydata',$mydata_options_sub);
            update_option('primer_licenses', $licenses);
            //sleep(2);//edw simainei exei thema me to js
            $response_data['success'] = true;
            if ($branchID == 0) {
                $response_data['message'] = 'Η επιλογή κεντρικού καταστήματος ολοκληρώθηκε με επιτυχία.Παρακαλώ ελέγξτε την αρίθμηση των παραστατικών στη καρτέλα MyData Settings';
            } else {
                $response_data['message'] = 'Η επιλογή υποκαταστήματος ολοκληρώθηκε με επιτυχία.Παρακαλώ ελέγξτε την αρίθμηση των παραστατικών στη καρτέλα MyData Settings ';
            }
            wp_die(json_encode($response_data));
        }
    }

    /**
     * Inserts a license into the database and updates the options accordingly.
     *
     * 1. Get existing licenses and mydata options
     * 2. Get license key, username, password, and reset flag from POST data
     * 3. Set API URL and authentication headers
     * 4. Set cURL arguments
     * 5. Send API request and handle response
     * 6. Set successful response data
     * 7. Reset numbering series
     *
     */
    public function primer_insert_license() {
        /** Get existing licenses and mydata options */
		$licenses = get_option( 'primer_licenses' );
		$mydata_options = get_option('primer_mydata');
		$response_data = array();

        /** Get license key, username, password, and reset flag from POST data */
		$licenseKey = isset($_POST['licenseKey']) ? $_POST['licenseKey'] : '';
		$username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
		$password = isset($_POST['password']) ? $_POST['password'] : '';
        $check_reset = isset($_POST['check_reset']) ? sanitize_text_field($_POST['check_reset']) : '';

        /** Set API URL and authentication headers, $api_url */
		$api_url = 'https://wp-mydataapi.primer.gr/v2/invoice/productActivation';
		$auth = base64_encode( "$username" . ':' . "$password" );

        /** Set cURL arguments  */
		$curl_args = array(
			'timeout' 		=> 10,
			'method' => 'POST',
			'httpversion' 	=> '1.1',
			'headers'		=> array(
				'Authorization' => 'Basic ' . $auth,
				'Content-Type'	=> 'application/json'
			),
			'sslverify'		=> false
		);
		$curl_args['body'] = '{
				"serialNumber": "'.$licenseKey.'"
			}';

        /** Send API request and handle response */
		$response = wp_remote_request($api_url, $curl_args);
		$response_body = wp_remote_retrieve_body($response);
		$response_message = wp_remote_retrieve_response_message($response);
        $response_code = wp_remote_retrieve_response_code($response);
		$response_to_array = json_decode($response_body);
        $response_data['check_message']=false;
		if (!empty($response_to_array)) {
			if ( ! is_string( $response_to_array ) && is_object( $response_to_array ) ) {
			    if ($response_code == 200) {
					$response_args = json_decode(json_encode($response_to_array), true);
					if (!empty($response_args)) {
						foreach ($licenses as $key => $license_val) {
							if (!array_key_exists($key, $response_args)  && $key != 'currentBranchID' )  {
								unset($licenses[$key]);
							}
						}
						foreach ( $response_args as $k => $response_arg ) {
							$licenses[$k] = $response_arg;
						}
					}
                    $response_data['response'] = true;
                    $response_data['message']  = $check_reset;
                    $response_data['check_message'] = true;
                    $response_data['server_name'] = $licenses['domain'];
                    $licenses['mistake_license'] = 'success';
                    $licenses['username'] = $username;
                    $licenses['password'] = $password;

                    if($check_reset == 'true') {
                        $reset_series  = array('EMPTY',
                            'A',
                            'B',
                            'C',
                            'D',
                            'E',
                            'Z',
                            'H',
                            'Q',
                            'I',
                            'K',
                            'L',
                            'M',
                            'N',
                            'J',
                            'O',
                            'P',
                            'R',
                            'S',
                            'T',
                            'Y',
                            'F',
                            'X',
                            'W',
                            'V');
                        $mydata_options['invoice_numbering_gr_series'] = 'EMPTY';
                        $mydata_options['invoice_numbering_gi_series'] = 'EMPTY';
                        $mydata_options['invoice_numbering_within_series'] = 'EMPTY';
                        $mydata_options['invoice_numbering_outside_series'] = 'EMPTY';
                        $mydata_options['invoice_numbering_gr_test_api_series'] = 'EMPTY';
                        $mydata_options['invoice_numbering_gi_test_api_series'] = 'EMPTY';
                        $mydata_options['invoice_numbering_within_test_api_series'] = 'EMPTY';
                        $mydata_options['invoice_numbering_outside_test_api_series'] = 'EMPTY';
                        $mydata_options['credit_receipt_series'] = 'EMPTY';
                        $mydata_options['credit_invoice_series'] = 'EMPTY';
                        $mydata_options['credit_receipt_test_api_series'] = 'EMPTY';
                        $mydata_options['credit_invoice_test_api_series'] = 'EMPTY';
                        foreach($reset_series as $reset_serie) {
                            $mydata_options['invoice_numbering_gr_'.$reset_serie.''] = 1;
                            $mydata_options['invoice_numbering_gi_'.$reset_serie.''] = 1;
                            $mydata_options['invoice_numbering_within_'.$reset_serie.''] = 1;
                            $mydata_options['invoice_numbering_outside_'.$reset_serie.''] = 1;
                            $mydata_options['invoice_numbering_gr_'.$reset_serie.'_test_api'] = 1;
                            $mydata_options['invoice_numbering_gi_'.$reset_serie.'_test_api'] = 1;
                            $mydata_options['invoice_numbering_within_'.$reset_serie.'_test_api'] = 1;
                            $mydata_options['invoice_numbering_outside_'.$reset_serie.'_test_api'] = 1;
                            $mydata_options['credit_receipt_'.$reset_serie.''] = 1;
                            $mydata_options['credit_invoice_'.$reset_serie.''] = 1;
                            $mydata_options['credit_receipt_'.$reset_serie.'_test_api'] = 1;
                            $mydata_options['credit_invoice_'.$reset_serie.'_test_api'] = 1;
                        }
                    }
                    $mydata_options['activation_update'] = 5;
                    $automation_duration_failed = "thirtyminutes";
                    $next_timestamp_failed = wp_next_scheduled('primer_cron_process_failed');
                    wp_clear_scheduled_hook('primer_cron_process_failed');
                    wp_schedule_event(time(), $automation_duration_failed, 'primer_cron_process_failed');
                    $next_timestamp_failed = wp_next_scheduled('primer_cron_process_credit_failed');
                    wp_clear_scheduled_hook('primer_cron_process_credit_failed');
                    wp_schedule_event(time(), $automation_duration_failed, 'primer_cron_process_credit_failed');
                    $next_timestamp_remaining = wp_next_scheduled('primer_cron_primer_license_remaining');
                    wp_clear_scheduled_hook('primer_cron_primer_license_remaining');
                    wp_schedule_event(time(), $automation_duration_failed, 'primer_cron_primer_license_remaining');
					$response_data['numbering'] = __('The Numbering of the invoices has been reset.', 'primer');
					update_option('primer_mydata', $mydata_options);

                } else {
                    $response_data['response'] = false;
                    $response_data['message']  = $response_to_array->Error;
                    $licenses['mistake_license'] = 'fail';

                    $licenses['primer_license_key']     = '';
                    $licenses['wpModules']              = array();
                    $licenses['monthRemainingInvoices'] = '';
                    $licenses['startMonth']             = '';
                    $licenses['endMonth']               = '';
                    $licenses['startYear']              = '';
                    $licenses['endYear']                = '';
                    $licenses['monthlyInvoices']        = '';
                    $licenses['companyName']            = '';
                    $licenses['companySmallName']       = '';
                    $licenses['companyVatNumber']       = '';
                    $licenses['companyAddress']         = '';
                    $licenses['companyPhoneNumber']     = '';
                    $licenses['companyActivity']        = '';
                    $licenses['companyDoy']             = '';
                    $licenses['companyCity']            = '';
                    $licenses['companyTk']              = '';
                    $licenses['companyEmail']           = '';
                    $licenses['gemh']                   = '';
                    $licenses['connectorPassword']      = '';

                    $licenses['username'] = '';
                    $licenses['password'] = '';
                }

			} else {
				$response_data['response'] = false;
				$response_data['message']  = $response_body;
				$licenses['mistake_license'] = 'fail';

                $licenses['primer_license_key']     = '';
				$licenses['wpModules']              = array();
				$licenses['monthRemainingInvoices'] = '';
				$licenses['startMonth']             = '';
				$licenses['endMonth']               = '';
				$licenses['startYear']              = '';
				$licenses['endYear']                = '';
				$licenses['monthlyInvoices']        = '';
				$licenses['companyName']            = '';
				$licenses['companySmallName']       = '';
				$licenses['companyVatNumber']       = '';
				$licenses['companyAddress']         = '';
				$licenses['companyPhoneNumber']     = '';
				$licenses['companyActivity']        = '';
				$licenses['gemh']                   = '';
                $licenses['companyDoy']             = '';
                $licenses['connectorPassword']      = '';
                $licenses['companyCity']            = '';
                $licenses['companyTk']              = '';
                $licenses['companyEmail']           = '';

                $licenses['username'] = '';
                $licenses['password'] = '';
			}
		} else {
			$response_data['response'] = false;
			$response_data['message']  = $response_body;
			$licenses['mistake_license'] = 'fail';
            $licenses['primer_license_key']     = '';
            $licenses['username'] = '';
            $licenses['password'] = '';
		}
        $licenses['webpage']= $_SERVER['SERVER_NAME'];
        if($mydata_options['upload_img_id'] != null){
            $response_data['messagephoto'] = 'Παρακαλούμε πολύ ανεβάστε ξανά λογότυπο στην περίπτωση που το χρειάζεστε.';
        }
        $response_data['kind'] = $licenses['productKind'];
        $second_level_domains_regex = '/\.asn\.au$|\.com\.au$|\.net\.au$|\.id\.au$|\.org\.au$|\.edu\.au$|\.gov\.au$|\.csiro\.au$|\.act\.au$|\.nsw\.au$|\.nt\.au$|\.qld\.au$|\.sa\.au$|\.tas\.au$|\.vic\.au$|\.wa\.au$|\.co\.at$|\.or\.at$|\.priv\.at$|\.ac\.at$|\.avocat\.fr$|\.aeroport\.fr$|\.veterinaire\.fr$|\.co\.hu$|\.film\.hu$|\.lakas\.hu$|\.ingatlan\.hu$|\.sport\.hu$|\.hotel\.hu$|\.ac\.nz$|\.co\.nz$|\.geek\.nz$|\.gen\.nz$|\.kiwi\.nz$|\.maori\.nz$|\.net\.nz$|\.org\.nz$|\.school\.nz$|\.cri\.nz$|\.govt\.nz$|\.health\.nz$|\.iwi\.nz$|\.mil\.nz$|\.parliament\.nz$|\.ac\.za$|\.gov\.za$|\.law\.za$|\.mil\.za$|\.nom\.za$|\.school\.za$|\.net\.za$|\.co\.uk$|\.org\.uk$|\.me\.uk$|\.ltd\.uk$|\.plc\.uk$|\.net\.uk$|\.sch\.uk$|\.ac\.uk$|\.gov\.uk$|\.mod\.uk$|\.mil\.uk$|\.nhs\.uk$|\.police\.uk$/';
        $domain = $_SERVER['SERVER_NAME'];
        $domain = explode('.', $domain);
        $domain = array_reverse($domain);
        if (preg_match($second_level_domains_regex, $_SERVER['SERVER_NAME'])) {
            $domain_name = "$domain[2].$domain[1].$domain[0]";
        }else{
            $domain_name = "$domain[1].$domain[0]";
        }
        if($licenses['domain'] != $domain_name){
            $licenses['mistake_license'] = 'fail';
            $response_data['mistake_domain'] = true;
            $licenses['primer_license_key']     = '';
            $licenses['wpModules']              = array();
            $licenses['monthRemainingInvoices'] = '';
            $licenses['startMonth']             = '';
            $licenses['endMonth']               = '';
            $licenses['startYear']              = '';
            $licenses['endYear']                = '';
            $licenses['monthlyInvoices']        = '';
            $licenses['companyName']            = '';
            $licenses['companySmallName']       = '';
            $licenses['companyVatNumber']       = '';
            $licenses['companyAddress']         = '';
            $licenses['companyPhoneNumber']     = '';
            $licenses['companyActivity']        = '';
            $licenses['gemh']                   = '';
            $licenses['companyDoy']             = '';
            $licenses['connectorPassword']      = '';
            $licenses['companyCity']            = '';
            $licenses['companyTk']              = '';
            $licenses['companyEmail']           = '';

            $licenses['username'] = '';
            $licenses['password'] = '';
        }
        $response_data['message']  = $response_body;
        $mydata_options['image_api_id'] = '';
        $mydata_options['timeout_check'] = '';
        $mydata_options['upload_img_id'] = null;
        $mydata_options['primer_use_logo'] = null;
        $mydata_options['running_conversion'] = 'no';
        delete_option('primer_do_not_show_notice');
		update_option('primer_licenses', $licenses);
        update_option('primer_mydata', $mydata_options);
        flush_rewrite_rules();
		wp_die(json_encode($response_data));
	}

    /**
     * Retrieves the remaining license count for a given license key.
     *
     * This function sends a POST request to the API endpoint to retrieve the remaining license count
     * for a given license key. It verifies the nonce and checks if the response is successful.
     * If the response is successful, it updates the license count in the options table and returns
     * a JSON response containing the remaining license count. If the response is not successful,
     * it returns an empty JSON response.
     *
     * @throws Exception If the nonce verification fails.
     */
	public function primer_license_remaining() {
		$licenses = get_option( 'primer_licenses' );

		// verify the nonce
		$nonce = isset( $_REQUEST['_ajax_nonce'] ) ? $_REQUEST['_ajax_nonce'] : false;

		check_ajax_referer( 'primer_ajax_nonce', '_ajax_nonce' );

		if( ! wp_verify_nonce( $nonce, 'primer_ajax_nonce' ) ) die( 'Stop!');

		$username = $licenses['username'] ? $licenses['username'] : '';
		$password = $licenses['password'] ? $licenses['password'] : '';

		$licenseKey = isset($licenses['serialNumber']) ? $licenses['serialNumber'] : '';
		$api_url = 'https://wp-mydataapi.primer.gr/v2/invoice/productActivation';
		$auth = base64_encode( "$username" . ':' . "$password" );
		$serial_number = array("serialNumber" => "$licenseKey");

		$request_args = array(
			'timeout' 		=> 0,
			'method'		=> 'POST',
			'httpversion' 	=> '1.1',
			'headers'		=> array(
				'Authorization' => 'Basic ' . $auth,
				'Content-Type'	=> 'application/json'
			),
			'sslverify'		=> false,
			'body' => json_encode($serial_number),
		);

		$response = wp_remote_post($api_url, $request_args);
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_answer = wp_remote_retrieve_body($response);

		$response_to_array = json_decode($response_answer);
		$response_body = array();

		if (!empty($response_to_array)) {
			if (!is_string($response_to_array) && is_object($response_to_array)) {
				$licenses['monthRemainingInvoices'] = $response_to_array->monthRemainingInvoices;
                $licenses['endDate'] = $response_to_array->endDate;
				$response_body['success'] = true;
				$response_body['remaining'] = $response_to_array->monthRemainingInvoices;
                if($response_to_array->monthRemainingInvoices > 0){
                    $mydata_options = get_option('primer_mydata');
                    $mydata_options['check_0_remaining'] = 2;
                    update_option( 'primer_mydata', $mydata_options );
                }
			} else {
				$licenses['monthRemainingInvoices'] = '';
				$response_body['success'] = false;
				$response_body['remaining'] = '';
			}
		}

		update_option( 'primer_licenses', $licenses );

		wp_die(json_encode($response_body));
	}

    /**
     * Export receipts to HTML and create a zip file containing the PDFs.
     *
     * This function retrieves the license data from the 'primer_licenses' option.
     * It then processes the receipt IDs provided in the $_POST['page_id'] parameter.
     * For each receipt ID, it generates a PDF file using the 'generate_pdf_content' method.
     * The PDF files are saved in the 'exported_invoices_files' directory within the WordPress uploads directory.
     * The function creates a zip file containing all the PDF files and saves it in the 'exported_invoices_files' directory.
     * The function returns the full URL to the zip file as a JSON response.
     *
     */
    public function primer_export_receipt_to_html() {
        $primer_license_data = get_option('primer_licenses');
        $receipt_ids = isset($_POST['page_id']) ? sanitize_text_field($_POST['page_id']) : "";

        $receipt_ids = explode(', ', $receipt_ids);

        $use_url_params = '';
        $files = array(); // Array to store PDF files
        $upload_dir = wp_upload_dir()['basedir'];

        $exported_invoices_dir = $upload_dir . '/exported_invoices_files';

        if (!file_exists($exported_invoices_dir)) {
            mkdir($exported_invoices_dir);
        }

        if (file_exists($exported_invoices_dir)) {
            $files = scandir($exported_invoices_dir);
            if (count($files) > 2) { // Count is greater than 2 for "." and ".."
                foreach ($files as $file) {
                    unlink($exported_invoices_dir . '/' . $file);
                }
            }
        }

        foreach ($receipt_ids as $receipt_id) {
            $find_invoice_in_slug = '';
            $invoice_type = get_the_terms($receipt_id, 'receipt_status');
            if (is_array($invoice_type)) {
                $invoice_type_slug = $invoice_type[0]->slug;
                $invoice_type_name = explode('_', $invoice_type_slug);
                $find_invoice_in_slug = $invoice_type_name[1];
            }

            if ($find_invoice_in_slug == 'receipt') {
                $use_url_params = '?receipt=view&username=' . $primer_license_data['username'];
            } else {
                $use_url_params =  '?username=' . $primer_license_data['username'];
            }

            // Generate PDFs
            $pdf_content = $this->generate_pdf_content($receipt_id, $use_url_params);
            $order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);

            $pdf_file_name = $upload_dir . '/exported_invoices_files/receipt-' . $receipt_id . ' for order '.$order_id.'.pdf';
            file_put_contents($pdf_file_name, $pdf_content);

            // Add PDF file to the list of files to be zipped
            $files[] = $pdf_file_name;
        }

        // Create a zip file
        $zip_file_name = $upload_dir . '/exported_invoices_files/receipts.zip';
        $this->create_zip($files, $zip_file_name, $upload_dir . '/exported_invoices_files/');

        // Get the full URL to the zip file
        $zip_file_url = home_url('/wp-content/uploads/exported_invoices_files/receipts.zip');

        echo json_encode(array('success' => 'true', 'status' => 'success', 'response' => $zip_file_url));
        die();
    }

    /**
     * Generates a PDF content for a given receipt ID and URL parameters.
     *
     * @param int $receipt_id The ID of the receipt.
     * @param bool $use_url_params Whether to use URL parameters.
     * @return string The generated PDF content.
     */
    private function generate_pdf_content($receipt_id, $use_url_params) {
        // Create Dompdf instance
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);

        // Set paper size
        $dompdf->setPaper('A4', 'portrait');

        // Retrieve the HTML content with the specified headers
        $post_url = get_the_permalink($receipt_id) . $use_url_params;
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
            "http" => array(
                "header" => "Content-type: text/html; charset=utf-8\r\n",
            ),
        );
        $create_json_instance = new Create_json();
        $Vat_exemption_categories = $create_json_instance->getVatExemptionCategories();
        $Vat_exemption_categories_en = $create_json_instance->getVatExemptionCategoriesEn();
        $id_of_order = get_post_meta($receipt_id,'order_id_to_receipt', true);

        $context = stream_context_create($arrContextOptions);
        $homepage = file_get_contents($post_url, false, $context);
        $modified_homepage = $homepage;


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

        // Load HTML into Dompdf
        $dompdf->loadHtml($modified_homepage);

        // Render PDF (echo to browser or save to file)
        $dompdf->render();

        // Output PDF
        return $dompdf->output();
    }

    /**
     * Recursively removes a directory and its contents.
     *
     * @param string $dir The directory to be removed.
     *
     */
	public function rmdir_recursive($dir) {
		foreach(scandir($dir) as $file) {
			if ('.' === $file || '..' === $file) continue;
			if (is_dir("$dir/$file")) $this->rmdir_recursive("$dir/$file");
			else unlink("$dir/$file");
		}
		rmdir($dir);
	}

    /**
     * Export receipts as static HTML files by page ID.
     *
     * @param array $page_ids The array of page IDs.
     * @param bool $use_url_params Whether to use URL parameters.
     * @return bool Returns true if the export is successful, false otherwise.
     */
	public function export_receipt_as_static_html_by_page_id($page_ids, $use_url_params) {
		if (!empty($page_ids)) {

			$upload_dir = wp_upload_dir()['basedir'];

			if (!file_exists($upload_dir . '/exported_html_files')) {
				mkdir($upload_dir . '/exported_html_files');
			}

			if (!file_exists($upload_dir . '/exported_html_files/tmp_files')) {
				mkdir($upload_dir . '/exported_html_files/tmp_files');
			} else {
				$this->rmdir_recursive($upload_dir . '/exported_html_files/tmp_files');
				mkdir($upload_dir . '/exported_html_files/tmp_files');
			}

			foreach ( $page_ids as $page_id ) {

				$main_url = '';
				$find_invoice_in_slug = '';
				$invoice_type = get_the_terms($page_id, 'receipt_status');
				if (is_array($invoice_type)) {
					$invoice_type_slug = $invoice_type[0]->slug;
					$invoice_type_name = explode('_', $invoice_type_slug);
				//	$find_invoice_in_slug = $invoice_type_name[1];
				}

				/*if ($find_invoice_in_slug == 'receipt') {
					$main_url = get_permalink($page_id) . $use_url_params;
				} else {
					$main_url = get_permalink($page_id) . $use_url_params;
				}*/

				$main_url = get_permalink($page_id) . $use_url_params;

//				$main_url = get_permalink($page_id);

				$parse_url = parse_url($main_url);
				$scheme = $parse_url['scheme'];
				$host = $scheme . '://' . $parse_url['host'];

				$post_name = get_the_title($page_id);
				$post_name = str_replace(' ', '_', $post_name);
				$post_name = str_replace('#', '', $post_name);
				$post_name = strtolower($post_name);
				$post_name = sanitize_text_field($post_name);

				$src = $this->get_site_data_by_url($main_url);

				if (!empty(realpath($upload_dir . '/exported_html_files/tmp_files/'))) {
				file_put_contents($upload_dir . '/exported_html_files/tmp_files/'.$post_name.'.html', $src);
			}
			}

		}

		return true;
	}
    /**
     * Include a JavaScript data object with the AJAX URL in the admin area.
     *
     * This function generates a JavaScript data object with the AJAX URL for the admin area.
     * The AJAX URL is obtained using the `admin_url('admin-ajax.php')` function.
     * The generated JavaScript code is enclosed within `<script>` tags.
     *
     */
	public function data_include_script() {
		?>
		<script>
            /* <![CDATA[ */
            var primer = {
                "ajax_url":"<?php echo admin_url('admin-ajax.php'); ?>",
            }
            /* ]]\> */
		</script>
	<?php
	}

    /**
     * Creates a zip file from an array of files.
     *
     * @param array $files The array of files to be included in the zip file.
     * @param string $destination The destination of the zip file.
     * @param string $replace_path The path to be replaced in the zip file.
     * @param bool $overwrite Whether to overwrite the zip file if it already exists.
     * @return bool Returns true if the zip file is created successfully, false otherwise.
     */
	public function create_zip($files = array(), $destination = '', $replace_path = "", $overwrite = true) {
		//if the zip file already exists and overwrite is false, return false
		if(file_exists($destination) && !$overwrite) { return false; }
		//vars
		$valid_files = array();
		//if files were passed in...
		if(is_array($files)) {
			//cycle through each file
			foreach($files as $file) {
				//make sure the file exists
				if(file_exists($file)) {
					if (is_file($file)) {
						$valid_files[] = $file;
					}

				}
			}
		}
		//if we have good files...
		if(count($valid_files)) {

			//create the archive
			$overwrite = file_exists($destination) ? true : false ;
			$zip = new ZipArchive();
			if($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
				return false;
			}

			//add the files
			foreach($valid_files as $file) {
				$filename = str_replace( $replace_path, '', $file);
				$zip->addFile($file, $filename);
			}
			//debug
			//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

			//close the zip -- done!
			$zip->close();

			//check to make sure the file exists
			return file_exists($destination) ? 'created' : 'not' ;
		}
		else
		{
			return false;
		}
	}

    /**
     * Creates a zip file of exported HTML files and returns the URL of the zip file.
     *
     */
	public function create_primer_the_zip_file(){
		$receipt_id = isset($_POST['page_id']) ? sanitize_text_field($_POST['page_id']) : "";

		global $wpdb;
        //
		$post_name = get_the_title($receipt_id);
		$post_name = str_replace(' ', '_', $post_name);
		$post_name = str_replace('#', '', $post_name);
		$post_name = strtolower($post_name);

		$upload_dir = wp_upload_dir()['basedir'];

		$upload_url = wp_upload_dir()['baseurl'] . '/exported_html_files';

		$all_files = $upload_dir . '/exported_html_files/tmp_files';
		$files = $this->get_all_files_as_array($all_files);

		$zip_file_name = $upload_dir . '/exported_html_files/'.$post_name.'-html.zip';

		ob_start();
		echo $this->create_zip($files, $zip_file_name, $all_files . '/');
		$create_zip = ob_get_clean();

		if ($create_zip == 'created') {
			$this->rmdir_recursive($upload_dir . '/exported_html_files/tmp_files');
		}

		$response = ($create_zip == 'created') ? $upload_url . '/'.$post_name.'-html.zip' : false;


		echo json_encode(array('success' => 'true', 'status' => 'success', 'response' => $response));

		die();
	}

    /**
     * Retrieves all files in a directory and its subdirectories as an array.
     *
     * @param string $all_files The path to the directory.
     * @return array An array containing the paths to all the files.
     */
	public function get_all_files_as_array($all_files){

		$files = [];
		if (!function_exists('rc_get_sub_dir')) {
			function rc_get_sub_dir($dir) {
				foreach(scandir($dir) as $file) {
					if ('.' === $file || '..' === $file) continue;
					if (is_dir("$dir/$file")) rc_get_sub_dir("$dir/$file");
					echo "$dir/$file" . ',';
				}
			}
		}
		if (function_exists('rc_get_sub_dir')) {
			ob_start();
			rc_get_sub_dir($all_files);
			$files = ob_get_clean();
			$files = rtrim($files, ',');
			$files = explode(',', $files);
		}

		return $files;
	}

    /**
     * Retrieves site data by URL.
     *
     * This function retrieves site data from a given URL using file_get_contents.
     * It sets the SSL verification options to false to bypass certificate verification.
     *
     * 1. Set SSL verification options.
     * 2. Retrieve site data using file_get_contents.
     * 3. If no data is retrieved, set a default value of 'OK'.
     * 4. Return the retrieved data.
     *
     * @return string The retrieved site data or 'OK' if no data is retrieved.
     */
	public function get_site_data_by_url($url='')
	{
		$arrContextOptions=array(
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			),
		);
        //
		$html = file_get_contents($url, false, stream_context_create($arrContextOptions));

		if (!$html) {
			$html = 'OK';
		}

		return $html;
	}

    /**
     * Updates the SMTP settings and sends a test email.
     *
     * This function is responsible for updating the SMTP settings and sending a test email.
     *
     * 1. Checks if the OpenSSL PHP extension is loaded and displays a warning if it's not.
     * 2. Checks if the server meets the encryption requirements.
     * 3. Retrieves the SMTP options and the SMTP test mail details from the database.
     * 4. Updates the SMTP options based on the values submitted through the form.
     *    - Sanitizes and validates the email address in the 'Send email from account' field.
     *    - Sets the reply-to email address to the admin email.
     *    - Updates the SMTP server, encryption type, authentication, username, password, and port.
     * 5. If the password encryption option is enabled and the password is not already encrypted,
     *    the function encrypts the password and updates the SMTP options in the database.
     * 6. Checks the validity of the SMTP port and updates the port value accordingly.
     * 7. If there are no errors, the function saves the updated SMTP options in the database
     *    and displays a success message.
     * 8. If there are errors, the function displays the error messages.
     * 9. If the 'smtp_type' field is not set to 'other_smtp', it calls the 'test_mail' method of the 'PrimerSMTP' class
     *    to send the test email.
     * 10. If there are errors and the 'smtp_type' is set to 'other_smtp', it displays the error messages.
     */
	public function primer_smtp_settings() {
		$primer_smtp = PrimerSMTP::get_instance();
		$enc_req_met  = true;
		$enc_req_err  = '';
		//check if OpenSSL PHP extension is loaded and display warning if it's not
		if ( ! extension_loaded( 'openssl' ) ) {
			$class   = 'notice notice-warning';
			$message = __( "PHP OpenSSL extension is not installed on the server. It's required by Primer SMTP to operate properly. Please contact your server administrator or hosting provider and ask them to install it.", 'primer' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			//also show encryption error message
			$enc_req_err .= __( 'PHP OpenSSL extension is not installed on the server. It is required for encryption to work properly. Please contact your server administrator or hosting provider and ask them to install it.', 'primer' ) . '<br />';
			$enc_req_met  = false;
		}

		//check if server meets encryption requirements
		if ( version_compare( PHP_VERSION, '5.6.0' ) < 0 ) {
			$enc_req_err = ! empty( $enc_req_err ) ? $enc_req_err   .= '<br />' : '';
			// translators: %s is PHP version
			$enc_req_err .= sprintf( __( 'Your PHP version is %s, encryption function requires PHP version 5.6.0 or higher.', 'primer' ), PHP_VERSION );
			$enc_req_met  = false;
		}

		$message = '';
		$error   = '';

		$primer_smtp_options = get_option('primer_emails');
		$smtp_test_mail  = get_option( 'primer_smtp_test_mail' );
		$gag_password    = '#primersmtpgagpass#';
		if ( empty( $smtp_test_mail ) ) {
			$smtp_test_mail = array(
				'primer_smtp_to'      => '',
				'primer_smtp_subject' => '',
				'primer_smtp_message' => '',
			);
		}

//		if ( isset( $_POST['primer_smtp_form_submit'] ) ) {
			/* Update settings */

			if( isset( $_POST['primer_from_email'] ) ) {
				if ( is_email($_POST['primer_from_email'] ) ) {
					$primer_smtp_options['from_email_field'] = sanitize_email( $_POST['primer_from_email'] );
				} else {
					$error .= ' ' . __( "Please enter a valid email address in the 'Send email from account' field.", 'primer' );
				}
			}

			$primer_smtp_options['reply_to_email'] = sanitize_email( get_bloginfo('admin_email') );

			$primer_smtp_options['smtp_settings']['smtp_server']            = stripslashes( sanitize_text_field($_POST['primer_smtp_host']) );
			$primer_smtp_options['smtp_settings']['type_encryption'] = ( isset( $_POST['primer_smtp_type_encryption'] ) ) ? sanitize_text_field( $_POST['primer_smtp_type_encryption'] ) : 'none';
			$primer_smtp_options['smtp_settings']['authentication'] = ( isset( $_POST['primer_smtp_authentication'] ) ) ? sanitize_text_field( $_POST['primer_smtp_authentication'] ) : 'yes';
			$primer_smtp_options['smtp_settings']['username'] = stripslashes( $_POST['primer_smtp_username'] );

			$primer_smtp_options['smtp_settings']['encrypt_pass'] = isset( $_POST['primer_encrypt_pass'] ) ? 1 : false;

			$primer_smtp_password = $_POST['primer_smtp_password'];
			if ($primer_smtp_password !== $gag_password) {
				$primer_smtp_options['smtp_settings']['password'] = $primer_smtp->encrypt_password( $primer_smtp_password );
			}

			if ( $primer_smtp_options['smtp_settings']['encrypt_pass'] && ! get_option( 'primer_pass_encrypted', false ) ) {
				update_option( 'primer_emails', $primer_smtp_options );
				$pass = $primer_smtp->get_password();
				$primer_smtp_options['smtp_settings']['password'] = $primer_smtp->encrypt_password( $pass );
				update_option('primer_emails', $primer_smtp_options);
			}


			/* Check value from "SMTP port" option */
			if ( isset( $_POST['primer_smtp_port'] ) ) {
				if ( empty( $_POST['primer_smtp_port'] ) || 1 > intval( $_POST['primer_smtp_port'] ) || ( ! preg_match( '/^\d+$/', $_POST['primer_smtp_port'] ) ) ) {
					$primer_smtp_options['smtp_settings']['port'] = '25';
					$error .= ' ' . __( "Please enter a valid port in the 'SMTP Port' field.", 'primer' );
				} else {
					$primer_smtp_options['smtp_settings']['port'] = sanitize_text_field( $_POST['primer_smtp_port'] );
				}
			}

            //
			if ( empty( $error ) ) {
				update_option( 'primer_emails', $primer_smtp_options );
				$message .= __( 'Settings saved.', 'primer' );
			} else {
				$error .= ' ' . __( 'Settings are not saved.', 'primer' );
			}

			/* Send test letter */
			$primer_smtp_to = '';
//			if ( isset( $_POST['primer_smtp_form_submit'] ) ) {
				if ( isset($_POST['primer_from_email']) ) {
					$to_email = sanitize_text_field( $_POST['primer_from_email'] );
					if (is_email( $to_email )) {
						$primer_smtp_to = $to_email;
					} else {
						$error .= __( 'Please enter a valid email address in the recipient email field.', 'primer' );
					}
				}
				if (!empty($primer_smtp_options['email_subject'])) {
					$primer_smtp_subject = $primer_smtp_options['email_subject'];
				} else {
					$primer_smtp_subject = __('Test email subject', 'primer');
				}

				if (!empty($primer_smtp_options['quote_available_content'])) {
					$primer_smtp_message = $primer_smtp_options['quote_available_content'];
				} else {
					$primer_smtp_message = __('Test email message', 'primer');
				}

				//Save the test mail details so it doesn't need to be filled in everytime.
				$smtp_test_mail['primer_smtp_to']      = $primer_smtp_to;
				$smtp_test_mail['primer_smtp_subject'] = $primer_smtp_subject;
				$smtp_test_mail['primer_smtp_message'] = $primer_smtp_message;
				update_option( 'primer_smtp_test_mail', $smtp_test_mail );

                if($_POST['smtp_type'] != 'other_smtp'){
                    $test_res = $primer_smtp->test_mail($primer_smtp_to, $primer_smtp_subject, $primer_smtp_message);
                }
				if(!empty($error) && $_POST['smtp_type'] == 'other_smtp') {
					$error_arr = explode('.', $error);
					foreach ($error_arr as $e) {
						if ($e) {
							$response_wrap = '<div class="primer_popup popup_error"><h3>'.$e.'</h3></div>';
							echo $response_wrap;
						}
					}
				}

				if ( !empty( $primer_smtp_to ) ) {
					$test_res = $primer_smtp->test_mail($primer_smtp_to, $primer_smtp_subject, $primer_smtp_message);
				}
//			}

//		}
		wp_die();
	}

    /**
     * Uploads a user picture and retrieves its URL.
     *
     * This function handles the upload of a user picture and retrieves its URL using the Primer MyData API.
     *
     * 1. Retrieves the Primer licenses and extracts the username and password.
     * 2. Constructs the API URL based on the selected API type.
     * 3. Sets up the cURL arguments, including the authorization header and the content type.
     * 4. Verifies the nonce to ensure the request is valid.
     * 5. Handles the user image upload using the WordPress `wp_handle_upload` function.
     * 6. If the upload is successful, extracts the image details and converts the image to a base64-encoded string.
     * 7. Sends the base64-encoded image to the Primer MyData API for processing.
     * 8. Checks the response for errors and returns the image URL if successful.
     * 9. If any errors occur during the process, an appropriate error message is returned.
     *
     * @throws WP_Error If the request is invalid or if the upload fails.
     */
	public function primer_user_picture_upload() {

		$mydata_options = get_option('primer_mydata');

		$licenses = get_option('primer_licenses');
		$username = $licenses['username'] ? $licenses['username'] : '';
		$password = $licenses['password'] ? $licenses['password'] : '';

		$store_url = '';
		$mydata_options = get_option('primer_mydata');
		$api_type = $mydata_options['mydata_api'];

		$store_url = 'https://wp-mydataapi.primer.gr/v2/invoice/photo';

		$auth = base64_encode( "$username" . ':' . "$password" );

		$curl_args = array(
			'timeout' 		=> 0,
			'method' => 'POST',
			'httpversion' 	=> '1.1',
			'headers'		=> array(
				'Authorization' => 'Basic ' . $auth,
				'Content-Type'	=> 'application/json'
			),
			'sslverify'		=> false
		);


		// Verify if Nonce is valid
		$verify_nonce = $_REQUEST['verify_nonce'];
		if ( !wp_verify_nonce($verify_nonce, 'upload_nonce') ) {
			echo json_encode(array('success' => false, 'reason' => 'Invalid request'));
			die();
		}

		$user_image = $_FILES['file_data_name'];
		$wp_handle_upload = wp_handle_upload($user_image, array('test_form' => false));

		if (isset($wp_handle_upload['file'])) {
			$file_name = basename($user_image['name']);
			$file_type = wp_check_filetype($wp_handle_upload['file']);

			$uploaded_image_details = array(
				'guid'           => $wp_handle_upload['url'],
				'post_mime_type' => $file_type['type'],
				'post_title'     => preg_replace('/\.[^.]+$/', '', basename($file_name)),
				'post_content'   => '',
				'post_status'    => 'inherit'
			);

			$attach_url = $wp_handle_upload['url'];

			$type = pathinfo($attach_url, PATHINFO_EXTENSION);
			$data = file_get_contents($attach_url);
			$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
			$curl_args['body'] = $base64;

			$img_has_error = '';

			$response = wp_remote_request($store_url, $curl_args);
			$response_body = wp_remote_retrieve_body($response);
			if ( is_wp_error( $response ) ) {
				$img_has_error = '<div class="notice notice-error"><p>'.$response->get_error_message().'</p></div>';
			}

			$info_img = wp_remote_retrieve_response_code( $response );

			$response_image = array();

			$exist_msg = "Logo exists with photoId";

			$use_exist_logo = false;
			$response_outside_args = explode(':', $response['body']);
			if (isset($response_outside_args[0])) {
				if ($response_outside_args[0] == $exist_msg || $response_outside_args[0] == 'photoId') {
					$use_exist_logo = true;
				} else {
					$use_exist_logo = false;
					$img_has_error = $response_body;
				}
			}

			if ($use_exist_logo !== false) {
				unset($mydata_options['image_api_id']);
				update_option('primer_mydata', $mydata_options);
			}

			if (!empty($response['body']) && $use_exist_logo !== false && $info_img === 200) {
				$response_args = explode(':', $response['body']);
				if (count($response_args) > 1) {
					$response_key = $response_args[0];
					$response_value = $response_args[1];
					$response_key = str_replace('"', '', $response_key);
					$response_value = str_replace('"', '', $response_value);
					if ($response_key == 'Logo exists with photoId') {
						$response_key = 'photoId';
					}
					$mydata_options['image_api_id'] = $response_key.':'.$response_value;
					$response_image[$response_key] = $response_value;
				}

				if (array_key_exists('photoId', $response_image)) {
					if (!empty($response_image['photoId'])) {
//					$mydata_options['count_logo_change'] = (int)$count_logo_change + 1;
						$profile_attach_id = wp_insert_attachment($uploaded_image_details, $wp_handle_upload['file']);

						$profile_attach_data = wp_generate_attachment_metadata($profile_attach_id, $wp_handle_upload['file']);
						wp_update_attachment_metadata($profile_attach_id, $profile_attach_data);

						$mydata_options['upload_img_id'] = $profile_attach_id;

						// Get uploaded image url
//			$photo_url = ogedge_uploaded_image_url( $profile_attach_data );
						$thumbnail_url = wp_get_attachment_image_src($profile_attach_id, array(350,100));

						update_option('primer_mydata', $mydata_options);

						echo json_encode(array(
							'success' => true,
							'url' => $thumbnail_url[0],
							'attachment_id' => $profile_attach_id
						));
						die;
					}
				}

			} else {
				echo json_encode(array('success' => false, 'reason' => $img_has_error));
				die;
			}

		} else {
			echo json_encode(array('success' => false, 'reason' => 'Profile Avatar upload failed'));
			die;
		}
	}

    public function get_alpha_char(): array
    {
		$alpha_chars = array();
		$alpha_chars[''] = __('Select', 'primer');
		foreach ( range('A', 'U') as $item ) {
			$alpha_chars[$item] = $item;
		}
		return $alpha_chars;
	}

    function cmb2_render_email_wildcards_field_callback($field, $escaped_value, $object_id, $object_type, $field_type_object) {
        echo '
        <div class="cmb-td">
            <ul>
                <li><a href="#" class="email-wildcard" data-wildcard=" {ClientFirstName} ">{ClientFirstName}</a></li>
                <li><a href="#" class="email-wildcard" data-wildcard=" {ClientLastName} ">{ClientLastName}</a></li>
                <li><a href="#" class="email-wildcard" data-wildcard=" {ClientEmail} ">{ClientEmail}</a></li>
                <li><a href="#" class="email-wildcard" data-wildcard=" {StreetAddress} ">{StreetAddress}</a></li>
                <li><a href="#" class="email-wildcard" data-wildcard=" {TownCity} ">{TownCity}</a></li>
                <li><a href="#" class="email-wildcard" data-wildcard=" {Phone} ">{Phone}</a></li>
                <!--<li><a href="#" class="email-wildcard" data-wildcard=" {CompanyName} ">{CompanyName}</a></li>
                <li><a href="#" class="email-wildcard" data-wildcard=" {Profession} ">{Profession}</a></li>
                <li><a href="#" class="email-wildcard" data-wildcard=" {VAT} ">{VAT}</a></li>-->
  
            </ul>';
        $field_args = $field_type_object->field->args;
        if ( ! empty( $field_args['desc'] ) ) {
            echo '<p class="cmb2-metabox-description">' . $field_args['desc'] . '</p>';
        }
        echo '</div>';

        ?>
        <script>
            jQuery(document).ready(function($) {
                $('.email-wildcard').click(function(e) {
                    e.preventDefault();
                    var wildcard = $(this).data('wildcard');
                    var editor_id = 'quote_available_content';
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get(editor_id)) {
                        tinyMCE.get(editor_id).execCommand('mceInsertContent', false, wildcard);
                    } else {
                        // Insert the wildcard at the end of the editor
                        editor.value += wildcard;
                    }
                });
            });
        </script>
        <?php
    }

}

// Get it started
$Primer_Options = new Primer_Options();

/**
 * Wrapper function around cmb_get_option
 * @since  0.1.0
 * @param  string  $key Options array key
 * @return mixed        Option value
 */
function primer_admin_option( $key = '' ) {
	global $Primer_Options;
	return cmb2_get_option( $Primer_Options->primer_get_option_key($key), $key );
}
