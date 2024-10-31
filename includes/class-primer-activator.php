<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }


/**
 * Fired during plugin activation
 *
 * @link       test.example.com
 * @since      1.0.0
 *
 * @package    Primer
 * @subpackage Primer/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Primer
 * @subpackage Primer/includes
 * @author     test_user <testwe@gmail.com>
 */
class Primer_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		global $wpdb, $wp_version;

		$plugin_name = 'primer';
		$require = array(
			'wordpress' => '4.0',
			'php'       => '5.3',
			'curl'      => true,
		);
        $php_version = phpversion();
		$extensions = get_loaded_extensions();
		$curl = in_array('curl', $extensions);
		$error = array();
		if ($wp_version < $require['wordpress']) {
			$error['wp_error'] = 'yes';
		}
		if ($php_version < $require['php']) {
			$error['php_error'] = 'yes';
		}
		if ($curl != true) {
			$error['curl_error'] = 'yes';
		}

		set_transient( 'primer_activation_warning', $error, 5 );
        $orders       = wc_get_orders(array(
            'date_created' => '>' . '01-01-2022',
            'limit' => 1000
        ));
        foreach ( $orders as $order ) {
            if (is_a($order, 'WC_Order_Refund')) {
                $order = wc_get_order($order->get_parent_id());
            }
            $order_id = $order->get_id();
            $receipt_order_status = get_post_meta($order_id, 'receipt_status', true);
            if (empty($receipt_order_status)) {
                update_post_meta($order_id, 'receipt_status', 'not_issued');
            }
        }


        $enable_comp_mode = get_option('woocommerce_custom_orders_table_data_sync_enabled');

        if ($enable_comp_mode == 'no') {
            update_option('woocommerce_custom_orders_table_data_sync_enabled', 'yes', true);
        }


        //$performance = get_option('woocommerce_custom_orders_table_enabled');
        //$enable_comp_mode = get_option('woocommerce_custom_orders_table_data_sync_enabled');

        //if ($performance == 'yes' && $enable_comp_mode == 'no') {
        //   update_option('woocommerce_custom_orders_table_data_sync_enabled', 'yes', true);
        //}


        /**
		 * if new install, add default options
		 */
		$orders_exists = get_option('primer_orders');
		$receipts_exists = get_option('primer_receipts');
		$automation_exists = get_option('primer_automation_settings');
		$mydata_exists = get_option('primer_mydata_settings');
		$email_exists = get_option('primer_email_settings');
		$export_exists = get_option('primer_export');
		$licenses_exists = get_option('primer_licenses');


		//flush_rewrite_rules();

		// Done
	//	do_action( 'primer_activated' );
	}

}
