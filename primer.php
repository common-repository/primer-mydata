<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              test.example.com
 * @since             1.0.0
 * @package           Primer
 *
 * @wordpress-plugin
 * Plugin Name:       Primer MyData
 * Plugin Slug:       primer-mydata
 * Plugin URI:        primer.gr/plugin/
 * Description:       Issue receipts and invoices with woocommerce.
 * Version:           4.1.8
 * Author:            Primer Software
 * Author URI:        primer.gr
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       primer
 * Domain Path:       /languages
 */

if ( ! defined('ABSPATH') ) { exit; }

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PRIMER_VERSION', '4.1.8');
define( 'PRIMER_NAME', 'Primer MyData' );

/**
 * Currently plugin path.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you change path.
 */
$upload_dir = wp_upload_dir();
define( 'PRIMER_PATH', plugin_dir_path( __FILE__ ) );
define( 'PRIMER_URL', plugins_url( '', __FILE__ ) );
define( 'PRIMER_QR_IMAGE_DIR', $upload_dir['basedir'] . '/primer_qrs/' );
define( 'PRIMER_QR_IMAGE_URL', $upload_dir['baseurl'] . '/primer_qrs/' );
define( 'PRIMER_SERVER_PATH', $_SERVER["DOCUMENT_ROOT"].'/wp-content/plugins/primer-mydata' );

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) || is_multisite() && in_array('woocommerce/woocommerce.php', array_flip(get_site_option('active_sitewide_plugins'))) ) {
	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/class-primer-activator.php
	 */
	function activate_primer() {
		require_once PRIMER_PATH . 'includes/class-primer-activator.php';
		Primer_Activator::activate();
	}
	 /**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-primer-deactivator.php
	 */
	function deactivate_primer() {
		require_once PRIMER_PATH . 'includes/class-primer-deactivator.php';
		Primer_Deactivator::deactivate();
	}

	register_activation_hook( __FILE__, 'activate_primer' );
	register_deactivation_hook( __FILE__, 'deactivate_primer' );
	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require PRIMER_PATH . 'includes/class-primer.php';

	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    1.0.0
	 */
	function run_primer() {
		$plugin = new Primer();
		$plugin->run();
	}

	add_action( 'plugins_loaded', 'run_primer' ); // wait until 'plugins_loaded' hook fires, for WP Multisite compatibility
} else {
	add_action('admin_notices', 'primer_error_notice');

	function primer_error_notice() {
		global $current_screen;
		if ($current_screen->parent_base == 'plugins') {
			echo '<div class="error"><p>Primer MyData '.__('requires <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a> need to be activated to work. Please install and activate <a href="'.admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce').'" target="_blank">WooCommerce</a> first.', 'primer').'</p></div>';
		}
	}
	$plugin = plugin_basename(__FILE__);
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if(is_plugin_active($plugin)){
		deactivate_plugins( $plugin);
	}
	if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

}

add_action('wp_ajax_vat_number_validation', 'vat_number_validation');
add_action('wp_ajax_nopriv_vat_number_validation', 'vat_number_validation');

/**
 * Validates a VAT number and applies zero tax for EU customers.
 *
 */
function vat_number_validation()
{
    // Get the mydata settings from the WordPress options.
    $mydata_settings = get_option('primer_mydata');
    // Set the customer as not VAT exempt by default.
    WC()->customer->set_is_vat_exempt(false);
    // Get the country and VAT number from the POST request.
    $country = $_POST['isCountry'];
    $vatNumber = sanitize_text_field($_POST['vat']);
    $isChecked = $_POST['isChecked'];
    // Check if zero tax is enabled for EU customers outside the EU and the checkbox is not checked.
    if ($mydata_settings['zero_tax_out_EU'] === 'on' && $country !== 'GR' && !check_zone_country($country) && $isChecked === '0') {
        WC()->customer->set_is_vat_exempt(true);
        WC()->cart->calculate_totals();
        wp_send_json_success(array('message' => 'Zero tax applied.'));
    }
    if ( $mydata_settings['vies_check'] == 'on' && $isChecked == 0) {

        // Create a new SoapClient instance for the VIES service.
        //WC()->customer->set_is_vat_exempt(false);
        $soapClient = new SoapClient('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl');
        // Prepare the parameters for the checkVat method
        $parameters = array(
            'countryCode' => $country,
            'vatNumber' => strval($vatNumber)
        );

        if ($parameters['countryCode'] == 'GR') {
            $parameters['countryCode'] = 'EL';
        } elseif ($parameters['countryCode'] == 'AU') {
            $parameters['countryCode'] = 'AT';
        }

        if ($parameters['countryCode'] == 'CY') {
            $parameters['vatNumber'] = preg_replace('/^CY/', '', $parameters['vatNumber']);
        } else {
         //   $parameters['vatNumber'] = filter_var($parameters['vatNumber'], FILTER_SANITIZE_NUMBER_INT);
        }

        try {
            // Call the checkVat method
            $result = $soapClient->checkVat($parameters);

            // Check if the VAT number is valid
            if ($result->valid) {

                if ($parameters['countryCode'] != 'EL') {

                    WC()->customer->set_is_vat_exempt(true);

                } else {
                    WC()->customer->set_is_vat_exempt(false);
                }
                WC()->cart->calculate_totals();
                wp_send_json_success();
            } else {
                WC()->customer->set_is_vat_exempt(false);
                WC()->cart->calculate_totals();
                // VAT number is not valid
                wp_send_json_error(array('message' => 'Invalid VAT number.'));
            }

        } catch (SoapFault $e) {
            WC()->customer->set_is_vat_exempt(false);
            WC()->cart->calculate_totals();
            // An error occurred during the SOAP request
            wp_send_json_error(array('message' => 'VAT number validation error: ' . $e->getMessage()));
        }

    } else {
        WC()->cart->calculate_totals();
        wp_send_json_error(array('message' => 'Enable VIES check'));
    }

}

add_action('wp_ajax_reset_of_the_tax_from_vies', 'reset_of_the_tax_from_vies');
add_action('wp_ajax_nopriv_reset_of_the_tax_from_vies', 'reset_of_the_tax_from_vies');

function reset_of_the_tax_from_vies() {
    $mydata_settings = get_option('primer_mydata');
    if ( $mydata_settings['vies_check'] == 'on' || $mydata_settings['zero_tax_out_EU'] == 'on') {


        $isChecked = $_POST['isChecked'];

        if ($isChecked == 0) {

            WC()->cart->calculate_totals();

            wp_send_json_success(array('message' => 'Invoice is check.'));
            // Additional actions or logic for when the checkbox is checked
        } else {

            WC()->customer->set_is_vat_exempt(false);
            WC()->cart->calculate_totals();
            // Additional actions or logic for when the checkbox is not checked
            wp_send_json_success(array('message' => 'Invoice is not check.'));
        }
    }

}


function primer_timologio_for_wc_aade_fill() {
    $vat = sanitize_text_field($_POST['vat']);
    $check = primer_check_for_valid_vat_aade($vat);
    wp_send_json($check);
}

add_action('wp_ajax_primer_timologio_for_wc_aade_fill', 'primer_timologio_for_wc_aade_fill');
add_action('wp_ajax_nopriv_primer_timologio_for_wc_aade_fill', 'primer_timologio_for_wc_aade_fill');

// Add custom tax rate for qualifying VAT number
//add_filter( 'woocommerce_product_tax_class', 'primer_custom_tax_class', 1, 2 );
/*function primer_custom_tax_class( $tax_class, $product ) {
    if ( WC()->session->get('is_vat_qualifies_for_zero_tax') ) {
        return 'Zero Rate';
    }
    return $tax_class;
} */

function primer_check_if_aade_isalive($timeout = 3) {
    $url="https://www1.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2?WSDL";

    $check_alive_args = array(
        'timeout' 		=> $timeout,
        'method'		=> 'POST',
        'httpversion' 	=> '1.1',
        'user-agent' => 'page-check/1.0',
        'sslverify'		=> false,
        'headers'       => array(
            'Content-type' => ''
        )
    );

    $result = wp_remote_request($url,$check_alive_args);
    if ( is_wp_error( $result ) ) {
        return $result->get_error_message();
    }else{
    return wp_remote_retrieve_response_code($result);
        }
}

function primer_check_for_valid_vat_aade($vat, $country=null) {
    $mydata_settings = get_option('primer_mydata');
    $mydata_settings['checkout_failed_aade'] = 'no';
    $aade_us = $mydata_settings['username_validation'];
    $aade_pass = $mydata_settings['password_validation'];
    if($mydata_settings['checkout_vat_validation'] == 'off'){
        return 'Enable automatic vat validation in mydata settings';
    }
    if ( primer_check_if_aade_isalive() !== 500) {
        wp_mail(get_option('admin_email'),__("AADE service is down",TEXT_DOMAIN),__("AADE service is down! Please de-activate the AADE VAT validation",TEXT_DOMAIN));
        return primer_check_if_aade_isalive();
    }
    $vat = preg_replace('/[^0-9,.]/','',$vat);
    $result = get_transient( "{$vat}_aade_check");
    if ( false === $result ) {
        $envelope = '<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:ns1="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:ns2="http://rgwspublic2/RgWsPublic2Service" xmlns:ns3="http://rgwspublic2/RgWsPublic2">
		<env:Header>
	      <ns1:Security>
	         <ns1:UsernameToken>
	            <ns1:Username>'. $aade_us . '</ns1:Username>
	            <ns1:Password>' . $aade_pass .'</ns1:Password>
	         </ns1:UsernameToken>
	      </ns1:Security>
	   </env:Header>
	   <env:Body>
	      <ns2:rgWsPublic2AfmMethod>
	         <ns2:INPUT_REC>
	            <ns3:afm_called_by/>
	            <ns3:afm_called_for>'. $vat .'</ns3:afm_called_for>
	         </ns2:INPUT_REC>
	      </ns2:rgWsPublic2AfmMethod>
	   </env:Body>
	</env:Envelope>';

        $url = 'https://www1.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2?WSDL';

        $curl_args_checkout = array(
            'method' => 'POST',
            'httpversion' 	=> '1.1',
            'headers'		=> array(
                'Content-Type'	=> '',
                'Content-Length' => strlen($envelope)
        ),
            'sslverify'		=> false
        );
        $curl_args_checkout['body'] = $envelope;
        $send_api = wp_remote_request($url,$curl_args_checkout);
        $result = wp_remote_retrieve_body($send_api);
        $result = preg_replace('/(<\s*)\w+:/','$1',$result);
        $result = preg_replace('/(<\/\s*)\w+:/','$1',$result);
        try {
            $xml = new SimpleXMLElement($result);
            $returns = [];

            if (empty($xml->Body->rgWsPublic2AfmMethodResponse->result->rg_ws_public2_result_rtType->error_rec->error_code)) {
                foreach ($xml->Body->rgWsPublic2AfmMethodResponse->result->rg_ws_public2_result_rtType->basic_rec->children() as $k=>$v) {
                    if (!empty($v))
                        $returns[$k] = (string)$v;
                }

                if (!empty($xml->Body->rgWsPublic2AfmMethodResponse->result->rg_ws_public2_result_rtType->firm_act_tab)) {
                    foreach ($xml->Body->rgWsPublic2AfmMethodResponse->result->rg_ws_public2_result_rtType->firm_act_tab->children() as $k=>$v) {
                        if (!empty($v->firm_act_descr)) {
                            if (!array_key_exists('activities',$returns)){
                                $returns['activities']=[];
                            }
                            array_push($returns['activities'],(string)$v->firm_act_descr);
                        }
                    }
                }
                $result=$returns;
                set_transient( "{$vat}_aade_check", is_array($result) ? $result : 'false', DAY_IN_SECONDS );
            }else {
                $returns['response_code'] = $xml->Body->rgWsPublic2AfmMethodResponse->result->rg_ws_public2_result_rtType->error_rec->error_code;
                $result=$returns;
                $mydata_settings['checkout_failed_aade'] = 'yes';
            }
        }
        catch(Exception $e) {
            $result=false;
        }
    }
    update_option('primer_mydata', $mydata_settings);
    return $result;



}
add_action('woocommerce_checkout_process', 'billing_phone_field_process');
function billing_phone_field_process() {
    $mydata_settings = get_option('primer_mydata');
    if ($_POST['billing_country'] == 'GR' && $mydata_settings['checkout_failed_aade'] == 'yes' && $_POST['billing_invoice_type'] == 'primer_invoice' && $mydata_settings['checkout_vat_validation'] == 'on') {
        wc_add_notice( 'The VAT number you completed could not be found in AADE Books.Please enter a correct Greek VAT Number.', 'error' );
    }
}



add_action( 'woocommerce_product_options_general_product_data', 'product_types_select' );

function product_types_select() {

            global $post;

            echo '<div class="options_group primer_product_types">';
			woocommerce_wp_radio( array(
                'id'		=> 'product_types',
                'label'	=> __('Product Types', 'primer'),
                'options'	=> array(
                    'Products' => __('Products', 'primer'),
                    'Services' => __('Services', 'primer'),
                    'Goods' => __('Goods', 'primer'),
			))
            );
		    echo '</div>';
}

add_action( 'woocommerce_process_product_meta', 'product_types_select_save' );

function product_types_select_save($post_id) {

    $product = wc_get_product($post_id);
    $product_types = isset($_POST['product_types']) ? $_POST['product_types'] : '';
    $product->update_meta_data('product_types', sanitize_text_field($product_types));
    $product->save();

}

