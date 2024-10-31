<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// reference the Dompdf namespace
use PrimerDompdf\Dompdf;

require_once PRIMER_PATH . 'includes/vendor/autoload.php';
require_once PRIMER_PATH. 'admin/includes/my_data_json.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PrimerCron {
	public function __construct() {
		add_action( 'primer_cron_save_settings', array( $this, 'primer_save_automation_data' ));
        add_action('primer_cron_process', array( $this, 'convert_order_to_invoice' ), 1);
        add_action('primer_cron_process_failed', array( $this, 'convert_order_to_invoice_failed' ), 1);
        add_action('primer_cron_process_credit_failed', array( $this, 'primer_cancel_invoice_cron' ), 1);
		add_action('wp_ajax_primer_fire_cron', array( $this, 'ajax_fire_cron' ));
		add_action( 'primer_cron_save_settings', array( $this, 'primer_save_export_data' ));
		add_action('primer_cron_export_process', array( $this, 'export_invoice_to_report' ));
		add_action('wp_ajax_export_invoice_to_report', array( $this, 'export_invoice_to_report' ));
        add_action('wp_ajax_primer_get_woocommerce_tax_rates', array( $this, 'primer_get_woocommerce_tax_rates' ));
        add_action('primer_cron_primer_license_remaining', array(&$this, 'primer_cron_license_remaining'), 1);
	}
	public function primer_save_automation_data() {
		$next_timestamp = wp_next_scheduled( 'primer_cron_process' );
		$automation_options = get_option('primer_automation');
		$activation_automation = $automation_options['activation_automation'];
		$automation_duration = $automation_options['automation_duration'];
		$current_schedule = wp_get_schedule('primer_cron_process');
		if ($current_schedule !== $automation_duration) {
            wp_clear_scheduled_hook('primer_cron_process');
            wp_schedule_event( time(), $automation_duration, 'primer_cron_process');
		} elseif (!$next_timestamp) {
			wp_schedule_event( time(), $automation_duration, 'primer_cron_process');
		}
		if (!empty($automation_options) && !empty($activation_automation)) {
            wp_clear_scheduled_hook('primer_cron_process');
			wp_schedule_event( time(), $automation_duration, 'primer_cron_process');
		} else {
			wp_clear_scheduled_hook( 'primer_cron_process' );
		}
	}

	public function primer_save_export_data() {
		$next_timestamp = wp_next_scheduled( 'primer_cron_process' );
		$export_options = get_option('primer_export');
		$activation_automation_export = $export_options['export_enable_schedule'];
		$automation_export_duration = $export_options['export_time'];
		$current_schedule = wp_get_schedule('primer_cron_export_process');
		if ($current_schedule !== $automation_export_duration) {
            wp_clear_scheduled_hook('primer_cron_export_process');
			wp_schedule_event( time(), $automation_export_duration, 'primer_cron_export_process');
		} elseif (!$next_timestamp) {
			wp_schedule_event( time(), $automation_export_duration, 'primer_cron_export_process');
		}
		if (!empty($export_options) && !empty($activation_automation_export)) {
            wp_clear_scheduled_hook('primer_cron_export_process');
			wp_schedule_event( time(), $automation_export_duration, 'primer_cron_export_process');
		} else {
			wp_clear_scheduled_hook( 'primer_cron_process' );
		}
	}


    public function primer_cron_license_remaining() {
        $licenses = get_option( 'primer_licenses' );
        $username = $licenses['username'];
        $password = $licenses['password'];
        $licenseKey = $licenses['serialNumber'];
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
        if (!empty($response_to_array)) {
            if (!is_string($response_to_array) && is_object($response_to_array)) {
                $licenses['monthRemainingInvoices'] = $response_to_array->monthRemainingInvoices;
                $licenses['endDate'] = $response_to_array->endDate;
                if($response_to_array->monthRemainingInvoices > 0){
                    $mydata_options = get_option('primer_mydata');
                    $mydata_options['check_0_remaining'] = 2;
                    update_option( 'primer_mydata', $mydata_options );
                }
            } else {
                $licenses['monthRemainingInvoices'] = '';
            }
        }
        update_option( 'primer_licenses', $licenses );
        wp_die();
    }

	/**
	 * Primer Automation Settings conversation
	 */
        public function convert_order_to_invoice()
        {
            global $wpdb, $woocommerce;
            $log_ids = array();

            if (!defined('MINUTE_IN_SECONDS')) {
                define('MINUTE_IN_SECONDS', 60); // Define it as 60 seconds if not already defined
            }
            if (get_transient('convert_order_to_invoice_lock')) {
                // Already running, skip execution
                return;
            }
            // Set a transient lock to prevent concurrent execution
            set_transient('convert_order_to_invoice_lock', true, MINUTE_IN_SECONDS);


        // Get Notification Emails
        $automation_options = get_option('primer_automation');
        $mydata_options = get_option('primer_mydata');
            $classificationType = '';
            $classificationCategory = '';
            $classificationCategory_en = 'category1_95';
            $api_url = $mydata_options['mydata_api'];
            $api_urls = array();
            switch ($api_url) {
                case 'test_api':
                    $api_urls[] = 'https://test-mydataapi.primer.gr/v2/invoices/sendData';
                    $url = 'https://test-mydataapi.primer.gr/v2/invoices/sendData';
                    $api_type = 'test';
                    break;
                case 'production_api':
                    $api_urls[] = 'https://wp-mydataapi.primer.gr/v2/invoices/sendData';
                    $url = 'https://wp-mydataapi.primer.gr/v2/invoices/sendData';
                    $api_type = 'production';
                    break;
                default:
                    $api_urls[] = 'https://test-mydataapi.primer.gr/v2/invoices/sendData';
                    $url = 'https://test-mydataapi.primer.gr/v2/invoices/sendData';
                    $api_type = 'test';
            }
            $primer_license_data = get_option('primer_licenses');
            $username = $primer_license_data['username'] ? $primer_license_data['username'] : '';
            $password = $primer_license_data['password'] ? $primer_license_data['password'] : '';
            $user_vat = $primer_license_data['companyVatNumber'];
            $callingFunction = "convert_order_to_invoice";
            $send_api_invoice = true;
            $url_slug = 'https://wp-mydataapi.primer.gr';
            $auth = base64_encode("$username" . ':' . "$password");
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
            $orders = array();
            $count_orders = 0;
            $response_data = '';
            $receipt_log_value = '';
            $receipt_log_value_array = array();
            $activation_automation = $automation_options['activation_automation'];
            $limit_automation_orders = $automation_options['automation_limit'];
            if(empty($limit_automation_orders)){
                $limit_automation_orders = 20;
            } else if($limit_automation_orders == 'unlimited'){
                $limit_automation_orders = -1;
            }
            if (!empty($automation_options) && !empty($activation_automation)) {
                $primer_conditions = $automation_options['primer_conditions'];
                $primer_start_order_date = $automation_options['calendar_date_timestamp'];
                if (!empty($primer_conditions)) {
                    $condition_order_status = '';
                    foreach ($primer_conditions as $primer_condition) {
                        $condition_order_status = $primer_condition['receipt_order_states'];
                        $order_args = array(
                            'return' => 'ids',
                            'limit' => $limit_automation_orders,
                            'order' => 'DESC',
                            'meta_key'     => 'receipt_status',
                            'meta_value' => 'not_issued',
                        );
                        $order_args['status'] = $condition_order_status;
                        if (!empty($primer_start_order_date)) {
                            $order_args['date_created'] = '>=' . $primer_start_order_date;
                        }
                        $orders = wc_get_orders($order_args);
                        $orders = array_unique($orders);
                        $count_orders += count($orders);
                        $mydata_options['already_running_orders'] = count($orders) * 5;
                        update_option('primer_mydata', $mydata_options);
                        array_map( 'sanitize_text_field', $orders );
                        //$start_time = microtime(true);
                        foreach ($orders as $order_id) {
                                $order = new WC_Order($order_id);
                                $id_of_order = $order->get_id();
                                $issue_date = new DateTime("now", new DateTimeZone("Europe/Athens"));
                                $gr_time = $issue_date->format('Y-m-d');
                                $receipt_log_id = wp_insert_post(array(
                                    'post_type' => 'pr_log_automation',
                                    'post_title' => 'Receipt automation report for #' . $id_of_order,
                                    'comment_status' => 'closed',
                                    'ping_status' => 'closed',
                                    'post_status' => 'publish',
                                ));
                                if (!ini_get('allow_url_fopen')) {
                                    $response_data .= '<div class="primer_popup popup_error"><div><h3>'.__('Php option allow_url_fopen is disabled! Please communicate with your hosting provider in order to activate it.', 'primer') .'</h3><br><br><br><br><br></div>';
                                    $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
                                    $receipt_log_value_array [] = __('Php option allow_url_fopen is disabled! Please communicate with your hosting provider in order to activate it.', 'primer');
                                    $receipt_log_value .= __('Php option allow_url_fopen is disabled! Please communicate with your hosting provider in order to activate it.', 'primer');
                                    update_post_meta($receipt_log_id, 'receipt_log_automation_error', $receipt_log_value);
                                    break ;
                                }
                                if ( get_post_meta($id_of_order, 'receipt_status', true) == 'issued' ) {
                                    continue ;
                                }
                                $order_country = $order->get_billing_country();
                                if(empty($order_country)){
                                    $order_country = 'GR';
                                }
                                if ( ! empty( $order->get_date_paid() ) ) {
                                    $order_paid_date = date( 'F j, Y', $order->get_date_paid()->getTimestamp() );
                                } else {
                                    $order_paid_date = date( 'F j, Y', $order->get_date_created()->getTimestamp() );
                                }
                                if (!empty($receipt_log_id)) {
                                    update_post_meta($id_of_order, 'log_id_for_order', $receipt_log_id);
                                    update_post_meta($receipt_log_id, 'receipt_log_automation_order_date', $order_paid_date);
                                }
                                $i = 1;
                                $invoice_data = "";
                                while ($i<=1) {
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
                                    $insert_taxonomy='receipt_status';
                                    $serie='';
                                    $series='';
                                    $number='';
                                    $invoice_time = '';
                                    $invoice_term='';
                                    $response_data='';
                                    $invoiceType='';
                                    $receipt_log_value='';
                                    $total='';
                                    $create_json_instance = new Create_json();
                                    $Vat_exemption_categories = $create_json_instance->getVatExemptionCategories();
                                    $Vat_exemption_categories_en = $create_json_instance->getVatExemptionCategoriesEn();
                                    $mydata_options = get_option('primer_mydata');
                                    $invoice_data = $create_json_instance -> create_invoice($user_id,$order_id,$total_vat_number,$mydata_options,$primer_license_data,
                                        $total,$series,$serie,$number,$currency,$currency_symbol,$user_data,$insert_taxonomy,
                                        $classificationCategory,$classificationCategory_en,$response_data,$receipt_log_value,$receipt_log_value_array,
                                        $receipt_log_id,$invoice_term,$gr_time, $invoice_time, $order_total_price,$order_invoice_type,
                                        $order_vatNumber,$user_order_email,$order_country,$user_full_name,$primer_smtp,$log_ids, $callingFunction,$invoiceType,
                                        $send_api_invoice,$classificationType);
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
                                    $is_timeout = false;
                                    $response_to_array = null;
                                    if ( is_wp_error( $response ) ) {
                                        $response_data .= '<div class="notice notice-error"><p>'.$response->get_error_message().'</p></div>';
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
                                    $time_for_call_timeout_48 = get_post_meta($id_of_order, 'order_datetime_failed', true);
                                    $time_for_call_timeout_1 = '';
                                    if ($time_for_call_timeout_48 && ($response_code > 500 || $is_timeout)) {
                                        $time_for_call_timeout_1 = date('Y-m-d H:i:s', strtotime($time_for_call_timeout_48 . ' + 2 days'));
                                        if ($time_for_call_timeout_1 > $gr_time) {
                                            $mydata_options['timeout_check_48'] = 0;
                                        } else {
                                            $mydata_options['timeout_check_48'] = 1;
                                            update_post_meta($id_of_order, 'failed_48', 'yes');
                                        }
                                        update_option('primer_mydata', $mydata_options);
                                    }

                                    $general_settings = get_option('primer_generals');
                                    if ($general_settings['primer_cron_transmission_failure'] != 'on' && ($response_code == 502 || $response_code == 512)) {
                                        $receipt_log_value .= __('Could not connect to AADE. Please check your general settings if you want to enable transmission failure option or contact Primer Software if the problem persists.', 'primer');
                                        $response_data .= '<div class="notice notice-error"><p>' . __('Could not connect to AADE. Please check your general settings if you want to enable transmission failure option or contact Primer Software if the problem persists.', 'primer') . '</p></div>';
                                        $receipt_log_value_array[] = __('Could not connect to AADE. Please check your general settings if you want to enable transmission failure option or contact Primer Software if the problem persists.', 'primer');

                                        continue;
                                    }
                                    $string_0_remaining = 'You have no other Monthly Invoices remaining';
                                    $check_string_remaining = strpos($response_message, $string_0_remaining);
                                    if ($check_string_remaining !== false && $api_type == 'production') {
                                        $mydata_options['check_0_remaining'] = 1;
                                        update_option('primer_mydata', $mydata_options);
                                    }
                                    if ($mydata_options['check_0_remaining'] == 1 && $api_type == 'production') {
                                        $receipt_log_value .= __('You have no other monthly invoices left.Please go to MyData settings and press "Get Remaining" button if you are sure that the month is passed and you have remaining invoices.', 'primer');
                                        $response_data .= '<div class="notice notice-error"><p>' . __('You have no other monthly invoices left.Please go to MyData settings and press "Get Remaining" button if you are sure that the month is passed and you have remaining invoices.', 'primer') . '</p></div>';
                                        $receipt_log_value_array[] = __('You have no other monthly invoices left.Please go to MyData settings and press "Get Remaining" button if you are sure that the month is passed and you have remaining invoices.', 'primer');

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
                                            update_option('primer_mydata', $mydata_options);
                                        }
                                        //end checking
                                    } else if (!$validate_response) {
                                        $find_code_error = 'Gateway';
                                        $code_position = strpos($response_message, $find_code_error);
                                        $response_to_array = json_decode($response_message);
                                        if ($response_code == 400) {
                                            $response_data .= '<div class="notice notice-error"><p>' . $response_message . '</p></div>';
                                            $receipt_log_value_array[] = $response_message;
                                            update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                                            continue;
                                        }
                                        if ($response_code == 403) {
                                            $response_data .= '<div class="notice notice-error"><p>' . $response_message . '</p></div>';
                                            $receipt_log_value_array[] = $response_message;
                                            update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                                            continue;
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
                                                        update_post_meta($post_id, 'send_to_api_type', $api_type);
                                                        update_post_meta($post_id, 'receipt_type', $invoice_term);
                                                        update_post_meta($post_id, 'order_id_to_receipt', $id_of_order);
                                                        update_post_meta($id_of_order, 'order_id_from_receipt', $post_id);
                                                        add_post_meta($post_id, 'receipt_client', $user_data);
                                                        add_post_meta($post_id, 'receipt_client_id', $user_id);
                                                        add_post_meta($post_id, 'receipt_price', $order_total_price . ' ' . $currency_symbol);
                                                        update_post_meta($post_id, '_primer_receipt_number', $number);
                                                        update_post_meta($post_id, '_primer_receipt_series', $serie);
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
                                                        $zip_file_name = $upload_dir . '/exported_html_files/' . $post_ids_str . '_html.zip';
                                                        ob_start();
                                                        echo $primer_options->create_zip($files, $zip_file_name, $all_files . '/');
                                                        $create_zip = ob_get_clean();

                                                        if ($create_zip == 'created') {
                                                            $primer_options->rmdir_recursive($upload_dir . '/exported_html_files/tmp_files');
                                                        }
                                                    }
                                                            $post_url = get_the_permalink($post_id);
                                                            $post_url = $post_url . '?receipt=view&username='.$primer_license_data['username'];
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
                                                            // Modify the height of .information.left and .information.right elements
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
                                                            $options = $dompdf->getOptions();
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
                                                            $output = $dompdf->output();
                                                            file_put_contents($upload_dir . '/email-invoices/' . $post_name . '.pdf', $output);
                                                            $attachments = $upload_dir . '/email-invoices/' . $post_name . '.pdf';
                                                            $post_issued = 'issued';
                                                            update_post_meta($post_id, 'receipt_status', $post_issued);
                                                            update_post_meta($id_of_order, 'receipt_status', $post_issued);
                                                            add_post_meta($post_id, 'receipt_client', $user_data);
                                                            add_post_meta($post_id, 'receipt_client_id', $user_id);
                                                            add_post_meta($post_id, 'receipt_price', $order_total_price . ' ' . $currency_symbol);
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
                                                                $country = get_post_meta($order_id, '_billing_country', true);
                                                                $streetAdress = get_post_meta($order_id, '_billing_address_1', true);
                                                                $townCity = get_post_meta($order_id, '_billing_city', true);
                                                                $phone = get_post_meta($order_id, '_billing_phone' , true);
                                                                $primer_smtp_message = str_replace('{ClientFirstName}', $client_first_name, $primer_smtp_message);
                                                                $primer_smtp_message = str_replace('{ClientLastName}', $client_last_name, $primer_smtp_message);
                                                                $primer_smtp_message = str_replace('{ClientEmail}', $client_email, $primer_smtp_message);
                                                                $primer_smtp_message = str_replace('{StreetAddress}', $streetAdress, $primer_smtp_message);
                                                                $primer_smtp_message = str_replace('{TownCity}', $townCity, $primer_smtp_message);
                                                                $primer_smtp_message = str_replace('{Phone}', $phone, $primer_smtp_message);
                                                            } else {
                                                                $primer_smtp_message = __('Test email message', 'primer');
                                                            }
                                                            $primer_automatically_send_file = $primer_condition['client_email_send'];
                                                            $primer_smtp_type = $primer_smtp_options['smtp_type'];
                                                            $order_log_id = get_post_meta($id_of_order, 'log_id_for_order', true);
                                                            if ($order_log_id) {
                                                                update_post_meta($post_id, 'log_id_for_order', $order_log_id);
                                                                $invoice_date = get_the_date('F j, Y', $post_id);
                                                                update_post_meta($order_log_id, 'receipt_log_invoice_id', $post_id);
                                                                update_post_meta($order_log_id, 'receipt_log_invoice_date', $invoice_date);
                                                                update_post_meta($order_log_id, 'receipt_log_client', $user_data);
                                                                update_post_meta($order_log_id, 'receipt_log_automation_status', 'issued');
                                                                $get_issue_status = get_post_meta($post_id, 'receipt_status', true);
                                                                if (empty($get_issue_status)) {
                                                                    $get_issue_status = 'issued';
                                                                }
                                                                update_post_meta($order_log_id, 'receipt_log_status', $get_issue_status);
                                                                update_post_meta($order_log_id, 'receipt_log_error', $receipt_log_value);
                                                            }
                                                            $email_logs = '';
                                                            if (!empty($primer_automatically_send_file) && $primer_automatically_send_file === 'on') {
                                                                $primer_smtp = PrimerSMTP::get_instance();
                                                                if (!empty($primer_smtp_options['email_from_name'])) {
                                                                    $from_name_email = $primer_smtp_options['email_from_name'];
                                                                //if (!empty($primer_smtp_options['primer_from_email'])) {
                                                                //    $from_name_email = $primer_smtp_options['primer_from_email'];
                                                                } else {
                                                                    $from_name_email = '';
                                                                }
                                                                if ($primer_smtp_type == 'wordpress_default') {
                                                                    $headers = array('Content-Type: text/html; charset=UTF-8');
                                                                    $mailResultSMTP = wp_mail($user_order_email, $primer_smtp_subject, $primer_smtp_message, $headers, $attachments);
                                                                    if(!$mailResultSMTP) {
                                                                        $email_logs = __('Email failed. Please check your email configuration', 'primer');
                                                                      update_post_meta($order_log_id,'receipt_log_automation_email_error', 'Email failed. Please check your email configuration');
                                                                      update_post_meta($order_log_id,'receipt_log_automation_email', 'not_sent');

                                                                    }
                                                                } else {
                                                                    $mailResultSMTP = $primer_smtp->primer_mail_sender($user_order_email, $from_name_email, $primer_smtp_subject, $primer_smtp_message, $attachments);
                                                                    if(!$mailResultSMTP) {
                                                                        $email_logs = __('Email failed. Please check your email configuration', 'primer');
                                                                        update_post_meta($order_log_id,'receipt_log_automation_email_error', 'Email failed. Please check your email configuration');
                                                                        update_post_meta($order_log_id,'receipt_log_automation_email', 'not_sent');
                                                                    }
                                                                }
                                                                if (!$primer_smtp->credentials_configured() && $primer_smtp_type != 'wordpress_default') {
                                                                    $email_logs .= __('Configure your SMTP credentials', 'primer') . "\n";

                                                                }

                                                                if (!empty($mailResultSMTP['error']) && (!$primer_smtp->credentials_configured() && $primer_smtp_type != 'wordpress_default')) {
                                                                    update_post_meta($order_log_id, 'receipt_log_automation_email', 'not_sent');
                                                                    $email_logs .= $GLOBALS['phpmailer']->ErrorInfo . "\n";
                                                                    update_post_meta($order_log_id, 'receipt_log_automation_email_error', $email_logs);
                                                                    update_post_meta($order_log_id, 'receipt_log_automation_total_status', 'only_errors');
                                                                } else {
                                                                    update_post_meta($order_log_id, 'receipt_log_automation_email', 'sent');
                                                                    update_post_meta($order_log_id, 'receipt_log_automation_total_status', 'only_issued');
                                                                }
                                                                if(!filter_var($user_order_email,FILTER_VALIDATE_EMAIL)) {
                                                                    update_post_meta($order_log_id, 'receipt_log_automation_email', 'not_sent');
                                                                    update_post_meta($order_log_id, 'receipt_log_automation_email_error', 'Invalid email');
                                                                }
                                                                if($email_logs != '') {
                                                                    update_post_meta($order_log_id, 'receipt_log_automation_email', 'not_sent');
                                                                    update_post_meta($order_log_id, 'receipt_log_automation_email_error', $email_logs);
                                                                }
                                                                update_post_meta($post_id, 'exist_error_log', 'exist_log');
                                                            } else {
                                                                if (!$primer_smtp->credentials_configured()) {
                                                                    $email_logs .= __('Configure your SMTP credentials', 'primer') . "\n";
                                                                }
                                                                $email_logs .= __('Send email automatically on order conversion disabled', 'primer') . "\n";
                                                                update_post_meta($order_log_id, 'receipt_log_automation_email', 'not_sent');
                                                                update_post_meta($order_log_id, 'receipt_log_automation_email_error', $email_logs);
                                                                update_post_meta($order_log_id, 'receipt_log_automation_total_status', 'only_issued');
                                                            }
                                                                update_post_meta($id_of_order, 'transmission_failure_check', 2);
                                                                $mydata_options['timeout_check_48'] = 0;
                                                                update_post_meta($id_of_order, 'failed_48', 'no');
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
                                                    $response_data = '<div class="notice notice-success"><p>' . __("Orders converted", "primer") . '</p></div>';
                                                } else {
                                                    update_option('primer_mydata', $mydata_options);
                                                    $response_data .= is_object($response_from_array[0]->errors) ? json_encode($response_from_array[0]->errors) : $response_from_array[0]->errors;
                                                    $receipt_log_value_array[] = $response_data;
                                                    continue;
                                                }
                                            }
                                        } else {
                                            $receipt_log_value_array[] = __('API not sent.', 'primer');
                                            update_post_meta($receipt_log_id, 'receipt_log_automation_error', $receipt_log_value_array);
                                        }
                                    } else {
                                        $receipt_log_value_array[] = __('API not sent.', 'primer');
                                        update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                                    }
                                    if (!empty($receipt_log_value_array)) {
                                        update_post_meta($receipt_log_id, 'receipt_log_automation_error', $receipt_log_value_array);
                                    }
                                } else {
                                    break;
                                }
                            }
                        }
                    }

                    if (!empty($log_ids)) {
                       foreach ($log_ids as $log_id) {
                           $check_if_error = get_post_meta($log_id, 'receipt_log_automation_error', true);
                            if(!empty($check_if_error) && $check_if_error != ''){
                                update_post_meta($log_id, 'receipt_log_automation_total_status', 'only_errors');
                            }
                        }
                    }
                    $primer_send_success_log = '';
                    $primer_send_fail_log = '';
                    if (!empty($automation_options['send_successful_log'])) {
                        $primer_send_success_log = $automation_options['send_successful_log'];
                    }
                    if (!empty($automation_options['send_failed_log'])) {
                        $primer_send_fail_log = $automation_options['send_failed_log'];
                    }

                    if($count_orders > 0) {
                        if($automation_options['send_email_to_admin']=='on'){
                            $this->export_csv_log($log_ids, $primer_send_success_log, $primer_send_fail_log);
                        }
                    }

                }

            delete_transient('convert_order_to_invoice_lock');
            wp_die();
        }
    public function convert_order_to_invoice_failed()
    {
        global $wpdb, $woocommerce;
        $log_ids = array();
        $emails = array();
        $mydata_options = get_option('primer_mydata');
        $callingFunction = "convert_order_to_invoice_failed";
            $classificationType = '';
            $classificationCategory = '';
            $classificationCategory_en = 'category1_95';
            $api_url = $mydata_options['mydata_api'];
            $api_urls = array();
            switch ($api_url) {
                case 'test_api':
                    $api_urls[] = 'https://test-mydataapi.primer.gr/v2/invoices/sendData';
                    $url = 'https://test-mydataapi.primer.gr/v2/invoices/sendData';
                    $api_type = 'test';
                    break;
                case 'production_api':
                    $api_urls[] = 'https://wp-mydataapi.primer.gr/v2/invoices/sendData';
                    $url = 'https://wp-mydataapi.primer.gr/v2/invoices/sendData';
                    $api_type = 'production';
                    break;
                default:
                    $api_urls[] = 'https://test-mydataapi.primer.gr/v2/invoices/sendData';
                    $url = 'https://test-mydataapi.primer.gr/v2/invoices/sendData';
                    $api_type = 'test';
            }
            $primer_license_data = get_option('primer_licenses');
            $username = $primer_license_data['username'] ? $primer_license_data['username'] : '';
            $password = $primer_license_data['password'] ? $primer_license_data['password'] : '';
            $user_vat = $primer_license_data['companyVatNumber'];
            $url_slug = 'https://wp-mydataapi.primer.gr';
            $send_api_invoice = true;
            $auth = base64_encode("$username" . ':' . "$password");
            $curl_args = array(
                'timeout' => 30,
                'redirection' => 10,
                'method' => 'POST',
                'httpversion' => '1.1',
                'headers' => array(
                    'Authorization' => 'Basic ' . $auth,
                    'Content-Type' => 'application/json',
                    'charset=UTF-8'
                ),
                'sslverify' => false
            );
            $total_vat_number = "$user_vat";
            $post_ids = array();
            $order_ids = array();
            $log_ids = array();
            $response_data = '';
            $receipt_log_value = '';
            $receipt_log_value_array = array();
            $order_args = array(
                'return' => 'ids',
                'limit' => 499,
                'order' => 'DESC'
            );
            $orders = wc_get_orders($order_args);
            $mydata_options['already_running_orders'] = count($orders) * 5;
            update_option('primer_mydata', $mydata_options);
            foreach ($orders as $order_id) {
                $invoice_data = array(
                    "invoice" => array(),
                );
                $order = new WC_Order($order_id);
                if (is_a($order, 'WC_Order_Refund')) {
                    $order = wc_get_order($order->get_parent_id());
                }
                $id_of_order = $order->get_id();
                $order_ids[] = $id_of_order;
                $check_timeout = get_post_meta($id_of_order, 'failed_48',true);
                $check_transmission_failure_48 = get_post_meta($id_of_order, 'transmission_failure_check',true);
                $issued_order = get_post_meta($id_of_order, 'receipt_status', true);
                $transmission_failure = get_post_meta($id_of_order, 'transmission_failure_check',true);
                $post_id_failed = get_post_meta($id_of_order, 'order_id_from_receipt', true);
                $branchID = get_post_meta($post_id_failed, 'branchID', true);
                if( $branchID == null ) {
                    $branchID = 0;
                }
                $check_api_type = get_post_meta($post_id_failed, 'send_to_api_type', true);
                $already_issued = get_post_meta($post_id_failed, 'response_invoice_mark', true);
                if ( ($transmission_failure == 1 || $transmission_failure == 3) && $check_api_type == 'production' && empty($already_issued)) {
                    //checks if it flagged the conversion and the order for 48hours logic
                    $time_for_call_timeout_48 = get_post_meta($id_of_order, 'order_datetime_failed', true);
                    $time_for_call_timeout_1 = '';
                    $issue_date = new DateTime("now", new DateTimeZone("Europe/Athens"));
                    $order_datetime_co = get_post_meta($id_of_order, 'order_datetime_failed', true);
                    if($order_datetime_co != ''){
                        $issue_date = new DateTime($order_datetime_co, new DateTimeZone("Europe/Athens"));
                    }
                    $gr_time = $issue_date->format('Y-m-d');
                    $invoice_time = $issue_date->format('H:i');
                    if ($time_for_call_timeout_48) {
                        $time_for_call_timeout_1 = date('Y-m-d H:i:s', strtotime($time_for_call_timeout_48 . ' + 2 days'));
                        if ($time_for_call_timeout_1 > $gr_time) {
                            $mydata_options['timeout_check_48'] = 0;
                        } else {
                            $mydata_options['timeout_check_48'] = 1;
                            update_post_meta($id_of_order, 'failed_48', 'yes');
                        }
                        update_option('primer_mydata', $mydata_options);
                    }
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
                        $receipt_log_value .= __('Php option allow_url_fopen is disabled! Please communicate with your hosting provider in order to activate it.', 'primer');
                        break ;
                    }
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
                    $insert_taxonomy='receipt_status';
                    $serie = get_post_meta($post_id_failed, '_primer_receipt_series', true);
                    $series = '';
                    $number = get_post_meta($post_id_failed, '_primer_receipt_number', true);
                    $connectionFailedMessage = get_post_meta($post_id_failed, 'connection_fail_message', true);
                    $invoice_term='';
                    $response_data='';
                    $invoiceType =  '';
                    $receipt_log_value='';
                    $total='';
                    $create_json_instance = new Create_json();
                    $Vat_exemption_categories = $create_json_instance->getVatExemptionCategories();
                    $Vat_exemption_categories_en = $create_json_instance->getVatExemptionCategoriesEn();
                    $invoice_data = $create_json_instance -> create_invoice($user_id,$order_id,$total_vat_number,$mydata_options,$primer_license_data,
                        $total,$series,$serie,$number,$currency,$currency_symbol,$user_data,$insert_taxonomy,
                        $classificationCategory,$classificationCategory_en,$response_data,$receipt_log_value,$receipt_log_value_array,
                        $receipt_log_id,$invoice_term,$gr_time, $invoice_time, $order_total_price,$order_invoice_type,
                        $order_vatNumber,$user_order_email,$order_country,$user_full_name,$primer_smtp,$log_ids, $callingFunction,$invoiceType,
                        $send_api_invoice,$classificationType);
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
                        if (is_wp_error($response)) {
                            $response_data .= '<div class="notice notice-error"><p>' . $response->get_error_message() . '</p></div>';
                            $receipt_log_value_array[] = $response->get_error_message();
                        } else {
                            $response_to_array = wp_remote_retrieve_body($response);
                        }
                        if (!empty($response_to_array)) {
                            $response_to_array = json_decode($response_to_array);
                        }
                        $string_0_remaining = 'You have no other Monthly Invoices remaining';
                        $check_string_remaining = strpos($response_message, $string_0_remaining);
                        if ($check_string_remaining !== false && $api_type == 'production') {
                            $mydata_options['check_0_remaining'] = 1;
                            update_option('primer_mydata', $mydata_options);
                        }
                        $generated_uid = strtoupper(sha1(iconv("UTF-8", "ISO-8859-7",strval($invoice_data['invoice'][0]['issuer']['vatNumber']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['issueDate']).'-'.strval($invoice_data['invoice'][0]['issuer']['branch']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['invoiceType']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['series']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['aa']))));
                        $string_r ='It has already been sent for another invoice';
                        $validate_response = strpos($response_message, $string_r);
                        if( $validate_response !== false ) {
                            //start checking
                            $create_status_instance = new Create_json();
                            $status = $create_status_instance -> get_invoice_status($api_type, $id_of_order, $gr_time, $serie, $number, $invoiceType, $order_invoice_type, $order_vatNumber, $user_vat,
                                $total, $auth, $invoice_term, $insert_taxonomy,$order, $user_data, $user_id, $order_total_price, $currency_symbol,
                                $order_country, $mydata_options, $series, $total_vat_number, $receipt_log_value, $receipt_log_value_array, $user_order_email,
                                $response_data, $receipt_log_id, $url_slug, $callingFunction,$generated_uid, $post_id_failed, $connectionFailedMessage);



                            if ( $status == "break" ) {
                                continue;
                            }
                            else {
                                //echo "<br>";
                                update_option('primer_mydata', $mydata_options);
                            }
                            //end checking
                        }

                        if ($mydata_options['check_0_remaining'] == 1 && $api_type == 'production') {
                            $receipt_log_value .= __('You have no other monthly invoices left.Please go to MyData settings and press "Get Remaining" button if you are sure that the month is passed and you have remaining invoices.', 'primer');
                            $response_data .= '<div class="notice notice-error"><p>' . __('You have no other monthly invoices left.Please go to MyData settings and press "Get Remaining" button if you are sure that the month is passed and you have remaining invoices.', 'primer') . '</p></div>';
                            $receipt_log_value_array[] = __('You have no other monthly invoices left.Please go to MyData settings and press "Get Remaining" button if you are sure that the month is passed and you have remaining invoices.', 'primer');
                            break;
                        }
                        //An uparxei mark gia to invoice kai exei ftasei edw tote stamataei edw to cron logo oti einai $response_code 400. Sto invoice status sunexeizei giati $last_info einai 200. To $response edw einai "It has already been sent for another invoice "
                        if ($response_code == 400 || $response_code == 422) {
                            $response_data .= '<div class="notice notice-error"><p>' . $response_message . '</p></div>';
                            $receipt_log_value_array[] = $response_message;
                            update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                            continue;
                        }
                        if ($response_code == 403) {
                            $response_data .= '<div class="notice notice-error"><p>' . $response_message . '</p></div>';
                            $receipt_log_value_array[] = $response_message;
                            update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                            continue;
                        }

                        $response_from_array = $response_to_array->response;
                        if (!empty($response_to_array)) {
                            if (!empty($response_from_array)) {
                                if ($response_from_array[0]->statusCode == 'Success') {
                                    $invoice_uid = $response_from_array[0]->invoiceUid;
                                    $invoice_mark = $response_from_array[0]->invoiceMark;
                                    $invoice_authcode = $response_from_array[0]->authenticationCode;
                                    $post_id = wp_insert_post(array(
                                        'post_type' => 'primer_receipt',
                                        'post_title' => 'Receipt for order #' . $id_of_order . '-failed',
                                        'comment_status' => 'closed',
                                        'ping_status' => 'closed',
                                        'post_status' => 'publish',
                                    ));
                                    wp_set_object_terms($post_id, $invoice_term, $insert_taxonomy, false);
                                    if ($post_id) {
                                        $order_date_co = get_post_meta($id_of_order, 'order_date_failed', true);
                                        $order_datetime_co = get_post_meta($id_of_order, 'order_datetime_failed', true);
                                        $issue_date = new DateTime($order_datetime_co, new DateTimeZone("Europe/Athens"));
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
                                        add_post_meta($post_id, 'receipt_price', $order_total_price . ' ' . $currency_symbol);
                                        update_post_meta($post_id, '_primer_receipt_number', $number);
                                        update_post_meta($post_id, '_primer_receipt_series', $serie);
                                        update_option('primer_mydata', $mydata_options);
                                        if (!empty($invoice_uid)) {
                                            update_post_meta($post_id, 'response_invoice_uid', $invoice_uid);
                                            update_post_meta($id_of_order, 'transmission_failure_check', 2);
                                        }
                                        if (!empty($invoice_mark)) {
                                            update_post_meta($post_id, 'response_invoice_mark', $invoice_mark);
                                        }
                                        if (!empty($invoice_authcode)) {
                                            update_post_meta($post_id, 'response_invoice_authcode', $invoice_authcode);
                                        }
                                        update_post_meta($post_id,'branchID', $branchID);
                                        update_post_meta($post_id,'connection_fail_message',$connectionFailedMessage);
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
                                        $zip_file_name = $upload_dir . '/exported_html_files/' . $post_ids_str . '_html.zip';
                                        ob_start();
                                        echo $primer_options->create_zip($files, $zip_file_name, $all_files . '/');
                                        $create_zip = ob_get_clean();
                                        if ($create_zip == 'created') {
                                            $primer_options->rmdir_recursive(WP_CONTENT_DIR . '/uploads/exported_html_files/tmp_files');
                                        }
                                        $zip_response = ($create_zip == 'created') ? $upload_url . '/' . $post_ids_str . '_html.zip' : false;
                                    }
                                    if ($zip_response !== false) {
                                        $html_body_args['invoiceFile'] = curl_file_create($_SERVER["DOCUMENT_ROOT"] . '/wp-content/uploads/exported_html_files/' . $post_ids_str . '_html.zip', 'application/zip', $post_ids_str . '_html.zip');
                                    }

                                    update_post_meta($id_of_order, 'transmission_failure_check', 2);
                                    $post_url = get_the_permalink($post_id);
                                    $post_url = $post_url . '?receipt=view&username='.$primer_license_data['username'];
                                    $arrContextOptions = array(
                                        "ssl" => array(
                                            "verify_peer" => false,
                                            "verify_peer_name" => false,
                                        ),
                                    );
                                    $homepage = file_get_contents($post_url, false, stream_context_create($arrContextOptions));
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
                                    $options = $dompdf->getOptions();
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
                                    $output = $dompdf->output();
                                    file_put_contents($upload_dir . '/email-invoices/' . $post_name . '.pdf', $output);
                                    $attachments = $upload_dir . '/email-invoices/' . $post_name . '.pdf';
                                    $post_issued = 'issued';
                                    update_post_meta($post_id, 'receipt_status', $post_issued);
                                    update_post_meta($id_of_order, 'receipt_status', $post_issued);
                                    add_post_meta($post_id, 'receipt_client', $user_data);
                                    add_post_meta($post_id, 'receipt_client_id', $user_id);
                                    add_post_meta($post_id, 'receipt_price', $order_total_price . ' ' . $currency_symbol);
                                    $primer_smtp_options = get_option('primer_emails');
                                    $headers = 'From: ' . $primer_smtp_options['from_email_field'] ? $primer_smtp_options['from_email_field'] : 'Primer ' . get_bloginfo('admin_email');
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
                                        if (empty($get_issue_status)) {
                                            $get_issue_status = 'issued';
                                        }
                                        update_post_meta($order_log_id, 'receipt_log_status', $get_issue_status);
                                        update_post_meta($order_log_id, 'receipt_log_error', $receipt_log_value);
                                    }
                                    $email_logs = '';
                                    if (!empty($primer_automatically_send_file) && $primer_automatically_send_file === 'yes' && $user_order_email != '' && $user_order_email != null) {
                                        $primer_smtp = PrimerSMTP::get_instance();
                                        if (!empty($primer_smtp_options['email_from_name'])) {
                                            $from_name_email = $primer_smtp_options['email_from_name'];
                                        } else {
                                            $from_name_email = '';
                                        }
                                        if ($primer_smtp_type == 'wordpress_default') {
                                            $headers = array('Content-Type: text/html; charset=UTF-8');
                                            $mailResultSMTP = wp_mail($user_order_email, $primer_smtp_subject, $primer_smtp_message, $headers, $attachments);
                                        } else {
                                            $mailResultSMTP = $primer_smtp->primer_mail_sender($user_order_email, $from_name_email, $primer_smtp_subject, $primer_smtp_message, $attachments);
                                        }
                                        if (!$primer_smtp->credentials_configured() && $primer_smtp_type != 'wordpress_default') {
                                            $email_logs .= __('Configure your SMTP credentials', 'primer') . "\n";
                                        }

                                        if (!empty($mailResultSMTP['error']) && (!$primer_smtp->credentials_configured() && $primer_smtp_type != 'wordpress_default')) {
                                            $response_data .= '<div class="notice notice-error"><p>' . $GLOBALS['phpmailer']->ErrorInfo . '</p></div>';
                                            update_post_meta($order_log_id, 'receipt_log_email', 'not_sent');
                                            $email_logs .= $GLOBALS['phpmailer']->ErrorInfo . "\n";
                                            update_post_meta($order_log_id, 'receipt_log_email_error', $email_logs);
                                            update_post_meta($order_log_id, 'receipt_log_total_status', 'only_errors');
                                        } else {
                                            update_post_meta($order_log_id, 'receipt_log_email', 'sent');
                                            update_post_meta($order_log_id, 'receipt_log_total_status', 'only_issued');
                                        }

                                        update_post_meta($post_id, 'exist_error_log', 'exist_log');
                                    } else {
                                        if (!$primer_smtp->credentials_configured()) {
                                            $email_logs .= __('Configure your SMTP credentials', 'primer') . "\n";
                                        }
                                        $email_logs .= __('Send email automatically on order conversion disabled', 'primer') . "\n";
                                        update_post_meta($order_log_id, 'receipt_log_email', 'not_sent');
                                        update_post_meta($order_log_id, 'receipt_log_email_error', $email_logs);
                                        update_post_meta($order_log_id, 'receipt_log_total_status', 'only_issued');
                                    }
                                    update_post_meta($id_of_order, 'transmission_failure_check', 2);
                                    $mydata_options['timeout_check_48'] = 0;
                                    update_post_meta($id_of_order, 'failed_48', 'no');
                                    update_option('primer_mydata', $mydata_options);
                                    $response_data = '<div class="notice notice-success"><p>' . __("Orders converted", "primer") . '</p></div>';


                                } else {
                                    update_option('primer_mydata', $mydata_options);
                                    $response_data .= is_object($response_from_array[0]->errors) ? json_encode($response_from_array[0]->errors) : $response_from_array[0]->errors;
                                    $receipt_log_value_array[] = $response_data;
                                    continue;
                                }
                            }
                        }
                    } else {
                        $receipt_log_value .= __('API not sent.', 'primer');
                        $receipt_log_value_array[] = __('API not sent.', 'primer');
                        update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                        $response_data .= '<div class="notice notice-error"><p>' . __('API not sent.', 'primer') . '</p></div>';
                    }

                    if (!empty($receipt_log_value_array)) {
                        update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                    }
                } else {
                    continue;
                }


                if (!empty($log_ids)) {
                    foreach ($log_ids as $log_id) {
                        update_post_meta($log_id, 'receipt_log_error', $receipt_log_value_array);
                        if (!empty($receipt_log_value_array)) {
                            update_post_meta($log_id, 'receipt_log_total_status', 'only_errors');
                        }
                    }
                }
            }


            wp_die();

    }
    public function primer_cancel_invoice_cron() {
        $mydata_options = get_option('primer_mydata');

            $send_characterizations = $mydata_options['send_characterizations'];
            $classificationType = '';
            $classificationCategory = '';
            $classificationCategory_en = 'category1_95';
            $api_url = $mydata_options['mydata_api'];
            $api_type = '';
            $url = '';
            $api_urls = array();
            $url_slug = '';
            switch ($api_url) {
                case 'test_api':
                    $api_urls[] = 'https://test-mydataapi.primer.gr/v2/invoices/sendData';
                    $url = 'https://test-mydataapi.primer.gr/v2/invoices/sendData';
                    $api_type = 'test';
                    break;
                case 'production_api':
                    $api_urls[] = 'https://wp-mydataapi.primer.gr/v2/invoices/sendData';
                    $url = 'https://wp-mydataapi.primer.gr/v2/invoices/sendData';
                    $api_type = 'production';
                    break;
                default:
                    $api_urls[] = 'https://test-mydataapi.primer.gr/v2/invoices/sendData';
                    $url = 'https://test-mydataapi.primer.gr/v2/invoices/sendData';
                    $api_type = 'test';
            }

            $primer_license_data = get_option('primer_licenses');
            $username = $primer_license_data['username'] ? $primer_license_data['username'] : '';
            $password = $primer_license_data['password'] ? $primer_license_data['password'] : '';
            $user_vat = $primer_license_data['companyVatNumber'];
            $callingFunction = "primer_cancel_invoice_cron";
            $url_slug = 'https://wp-mydataapi.primer.gr';
            $send_api_invoice = true;
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
            $receipt_args = array(
                'posts_per_page' => -1,
                'post_type' => 'primer_receipt',
                'post_status' => 'publish',
            );
            $receipt_ids = array();
            $receipt_query = new WP_Query( $receipt_args );
            if ($receipt_query->have_posts()):
                while ($receipt_query->have_posts()):
                    $receipt_query->the_post();
                    $receipt_status_text = '';
                        $transmission_failure_receipt = get_post_meta(get_the_ID(),'transmission_failure_check',true);
                    if(!empty($transmission_failure_receipt)){
                    $receipt_ids[] = get_the_ID();
                        }
                endwhile;
            endif;
            $response_data = '';
            $receipt_log_value = '';
            $receipt_log_value_array = array();
            $orders = array();
            if (!empty($receipt_ids)) {
                foreach ( $receipt_ids as $receipt_id ) {
                    $receipt_id = (int)$receipt_id;
                    $order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
                    $check_for_mark = get_post_meta ($order_id, 'order_id_from_credit_receipt', true);
                    if ( empty(get_post_meta($check_for_mark, 'response_invoice_mark' ,true))) {
                    $get_mark_from_receipt = get_post_meta($order_id, 'order_id_from_receipt', true);
                    $post_id_failed = get_post_meta($order_id, 'order_id_from_receipt', true);
                    $receipt_id_failed = get_post_meta ($order_id, 'order_id_from_credit_receipt', true);
                    $connectionFailedMessage = get_post_meta($receipt_id_failed, 'connection_fail_message', true);
                    $branchID = get_post_meta($post_id_failed, 'branchID', true);
                    if( $branchID == null ) {
                        $branchID = 0;
                    }
                    $order = new WC_Order($order_id);
                    $id_of_order = $order->get_id();
                    $orders[] = $id_of_order;
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
                    if (!empty($order->get_date_paid())) {
                        $order_paid_date = date('F j, Y', $order->get_date_paid()->getTimestamp());
                    } else {
                        $order_paid_date = date('F j, Y', $order->get_date_created()->getTimestamp());
                    }
                    update_post_meta($receipt_log_id, 'receipt_log_order_id', $id_of_order);
                    if (!empty($receipt_log_id)) {
                        update_post_meta($id_of_order, 'credit_log_id_for_order', $receipt_log_id);
                        update_post_meta($receipt_log_id, 'receipt_log_order_date', $order_paid_date);
                    }
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
                    $insert_taxonomy='receipt_status';
                    //$serie = '' ;
                    $serie = get_post_meta($receipt_id, '_primer_receipt_series', true);
                    $series='';
                    //$number = '' ;
                    $number = get_post_meta($receipt_id, '_primer_receipt_number', true);
                    $invoice_term='';
                    $response_data='';
                    $invoice_time = '';
                    $receipt_log_value='';
                    $total='';
                    $invoice_type = get_the_terms($receipt_id, 'receipt_status');
                    $invoice_type_slug = $invoice_type[0]->slug;
                    $invoice_type_name = explode('-', $invoice_type_slug);
                    $order_invoice_type = $invoice_type_name[1];
                    $vat_number = get_post_meta($id_of_order, '_billing_vat', true);
                    $insert_taxonomy = 'receipt_status';
                    $create_json_instance = new Create_json();
                    $Vat_exemption_categories = $create_json_instance->getVatExemptionCategories();
                    $Vat_exemption_categories_en = $create_json_instance->getVatExemptionCategoriesEn();
                    $invoice_data = $create_json_instance -> create_invoice($user_id,$order_id,$total_vat_number,$mydata_options,$primer_license_data,
                        $total,$series,$serie,$number,$currency,$currency_symbol,$user_data,$insert_taxonomy,
                        $classificationCategory,$classificationCategory_en,$response_data,$receipt_log_value,$receipt_log_value_array,
                        $receipt_log_id,$invoice_term,$gr_time, $invoice_time, $order_total_price,$order_invoice_type,
                        $order_vatNumber,$user_order_email,$order_country,$user_full_name,$primer_smtp,$log_ids, $callingFunction,$invoiceType,
                        $send_api_invoice, $classificationType, true, $get_mark_from_receipt, $connectionFailedMessage);


                    if ($invoice_data == "break") {
                        break;
                    }
                    elseif ($invoice_data == "continue") {
                        continue;
                    }



                    if ($send_api_invoice) {
                        $curl_args['body'] = json_encode($invoice_data);
                        $response = wp_remote_post($url, $curl_args);
                        //update_post_meta($receipt_id, 'otinanai', $invoice_type);
                        $response_message = wp_remote_retrieve_body($response);
                        $response_code = wp_remote_retrieve_response_code($response);
                        $response_to_array = null;
                        if (is_wp_error($response)) {
                            $response_data .= '<div class="notice notice-error"><p>' . $response->get_error_message() . '</p></div>';
                            $receipt_log_value_array[] = $response->get_error_message();
                        }
                    } else {
                        $response_to_array = wp_remote_retrieve_body($response);
                    }

                    if (!empty($response_to_array)) {
                        $response_to_array = json_decode($response_to_array);
                    }
                    $general_settings = get_option('primer_generals');
                    if ($general_settings['primer_cron_transmission_failure'] != 'on' && ($response_code == 502 || $response_code == 512)) {
                        $receipt_log_value .= __('Could not connect to AADE. Please check your general settings if you want to enable transmission failure option or contact Primer Software if the problem persists.', 'primer');
                        $response_data .= '<div class="notice notice-error"><p>' . __('Could not connect to AADE. Please check your general settings if you want to enable transmission failure option or contact Primer Software if the problem persists.', 'primer') . '</p></div>';
                        $receipt_log_value_array[] = __('Could not connect to AADE. Please check your general settings if you want to enable transmission failure option or contact Primer Software if the problem persists.', 'primer');
                        break;
                    }

                    $string_0_remaining = 'You have no other Monthly Invoices remaining';
                    $check_string_remaining = strpos($response_message, $string_0_remaining);
                    if ($check_string_remaining !== false && $api_type == 'production') {
                        $mydata_options['check_0_remaining'] = 1;
                        update_option('primer_mydata', $mydata_options);
                    }
                        $generated_uid = strtoupper(sha1(iconv("UTF-8", "ISO-8859-7",strval($invoice_data['invoice'][0]['issuer']['vatNumber']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['issueDate']).'-'.strval($invoice_data['invoice'][0]['issuer']['branch']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['invoiceType']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['series']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['aa']))));
                        $string_r ='It has already been sent for another invoice';
                        $validate_response = strpos($response_message, $string_r);
                        if( $validate_response !== false ) {
                            //start checking
                            $create_status_instance = new Create_json();
                            $status = $create_status_instance -> get_invoice_status($api_type, $id_of_order, $gr_time, $serie, $number, $invoiceType, $order_invoice_type, $order_vatNumber, $user_vat,
                                $total, $auth, $invoice_term, $insert_taxonomy,$order, $user_data, $user_id, $order_total_price, $currency_symbol,
                                $order_country, $mydata_options, $series, $total_vat_number, $receipt_log_value, $receipt_log_value_array, $user_order_email,
                                $response_data, $receipt_log_id, $url_slug, $callingFunction,$generated_uid, $post_id_failed,$connectionFailedMessage);

                            if ( $status == "break" ) {
                                continue;
                            }
                            else {
                                //echo "<br>";
                                update_option('primer_mydata', $mydata_options);
                            }
                            //end checking
                        }
                    if ($mydata_options['check_0_remaining'] == 1 && $api_type == 'production') {
                        $receipt_log_value .= __('You have no other monthly invoices left. Please go to MyData settings and press "Get Remaining" button if you are sure that the month is passed and you have remaining invoices.', 'primer');
                        $response_data .= '<div class="notice notice-error"><p>' . __('You have no other monthly invoices left. Please go to MyData settings and press "Get Remaining" button if you are sure that the month is passed and you have remaining invoices.', 'primer') . '</p></div>';
                        $receipt_log_value_array[] = __('You have no other monthly invoices left. Please go to MyData settings and press "Get Remaining" button if you are sure that the month is passed and you have remaining invoices.', 'primer');
                        break;
                    }
                    $response_to_array = json_decode($response_message);
                    if ($response_code == 400 || $response_code == 422) {
                        $response_data .= '<div class="notice notice-error"><p>' . $response_message . '</p></div>';
                        $receipt_log_value_array[] = $response_to_array;
                        update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                        continue;
                    }
                    if ($response_code == 403) {
                        $response_data .= '<div class="notice notice-error"><p>' . $response_message . '</p></div>';
                        $receipt_log_value_array[] = $response_message;
                        update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                        continue;
                    }

                    //$generated_uid = strtoupper(sha1(iconv("UTF-8", "ISO-8859-7",strval($invoice_data['invoice'][0]['issuer']['vatNumber']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['issueDate']).'-'.strval($invoice_data['invoice'][0]['issuer']['branch']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['invoiceType']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['series']).'-'.strval($invoice_data['invoice'][0]['invoiceHeader']['aa']))));
                    if (!empty($response_to_array)) {
                        $response_from_array = $response_to_array->response;
                        if (!empty($response_from_array)) {
                            if (($response_from_array[0]->statusCode == 'Success')) {
                                $invoice_uid = $response_from_array[0]->invoiceUid;
                                $invoice_mark = $response_from_array[0]->invoiceMark;
                                $invoice_authcode = $response_from_array[0]->authenticationCode;
                                $post_id = wp_insert_post(array(
                                    'post_type' => 'primer_receipt',
                                    'post_title' => 'Credit Receipt for order #' . $id_of_order. '-failed',
                                    'comment_status' => 'closed',
                                    'ping_status' => 'closed',
                                    'post_status' => 'publish',
                                ));
                                wp_set_object_terms($post_id, $invoice_term, $insert_taxonomy, false);
                                if ($post_id) {
                                    $order_date_co = get_post_meta($id_of_order, 'order_date_failed', true);
                                    $order_datetime_co = get_post_meta($id_of_order, 'order_datetime_failed', true);
                                    $issue_date = new DateTime($order_datetime_co, new DateTimeZone("Europe/Athens"));
                                    $invoice_date = $issue_date->format('d/m/Y');
                                    $invoice_time = $issue_date->format('H:i');
                                    update_post_meta($post_id, 'credit_success_mydata_date', $invoice_date);
                                    update_post_meta($post_id, 'credit_success_mydata_time', $invoice_time);
                                    update_post_meta($post_id, 'send_to_api_type', $api_type);
                                    //update_post_meta($post_id, 'receipt_type', $invoice_term);
                                    if ($api_type == 'test') {
                                        $url_slug = 'https://test-mydataapi.primer.gr';
                                    }
                                    update_post_meta($post_id, 'order_id_to_receipt', $id_of_order);
                                    update_post_meta($id_of_order, 'order_id_from_credit_receipt', $post_id);
                                    add_post_meta($post_id, 'receipt_client', $user_data);
                                    add_post_meta($post_id, 'receipt_client_id', $user_id);
                                    add_post_meta($post_id, 'receipt_price', $order_total_price . ' ' . $currency_symbol);
                                    update_post_meta($post_id, '_primer_receipt_number', $number);
                                    update_post_meta($post_id, '_primer_receipt_series', $serie);
                                    update_post_meta($post_id, 'credit_receipt', 'yes');
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
                                    update_post_meta($post_id,'branchID', $branchID);
                                    update_post_meta($post_id,'connection_fail_message',$connectionFailedMessage);
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
                                    $zip_file_name = $upload_dir . '/exported_html_files/' . $post_ids_str . '_html.zip';
                                    ob_start();
                                    echo $primer_options->create_zip($files, $zip_file_name, $all_files . '/');
                                    $create_zip = ob_get_clean();

                                    if ($create_zip == 'created') {
                                        $primer_options->rmdir_recursive($upload_dir . '/exported_html_files/tmp_files');
                                    }
                                    $zip_response = ($create_zip == 'created') ? $upload_url . '/' . $post_ids_str . '_html.zip' : false;
                                }
                                if ($zip_response !== false) {
                                    $html_body_args['invoiceFile'] = curl_file_create(WP_CONTENT_DIR . '/uploads/exported_html_files/' . $post_ids_str . '_html.zip', 'application/zip', $post_ids_str . '_html.zip');
                                }
                                if ($mydata_options['primer_use_api_smtp'] == 'on') {
                                    $html_post_fields['sendEmail'] = "true";
                                    $html_post_fields['email'] = $user_order_email;
                                }
                                        $post_url = get_the_permalink($post_id);
                                        $post_url = $post_url . '?receipt=view&username='.$primer_license_data['username'];

                                        $arrContextOptions = array(
                                            "ssl" => array(
                                                "verify_peer" => false,
                                                "verify_peer_name" => false,
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
                                        $options = $dompdf->getOptions();
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
                                        $upload_dir_file = $upload_dir . '/email-invoices/' . $post_name . '.pdf';

                                        if (!empty(realpath($upload_dir . '/email-invoices/'))) {
                                            file_put_contents($upload_dir_file, $output);
                                        }
                                        $attachments = $upload_dir . '/email-invoices/' . $post_name . '.pdf';
                                        $post_issued = 'issued';
                                        update_post_meta($post_id, 'receipt_status', $post_issued);
                                        update_post_meta($id_of_order, 'receipt_status', $post_issued);
                                        add_post_meta($post_id, 'receipt_client', $user_data);
                                        add_post_meta($post_id, 'receipt_client_id', $user_id);
                                        add_post_meta($post_id, 'receipt_price', $order_total_price . ' ' . $currency_symbol);
                                        $primer_smtp_options = get_option('primer_emails');
                                        $headers = 'From: ' . $primer_smtp_options['from_email_field'] ? $primer_smtp_options['from_email_field'] : 'Primer ' . get_bloginfo('admin_email');
                                        if (!empty($primer_smtp_options['email_subject'])) {
                                            $primer_smtp_subject = $primer_smtp_options['email_subject'];
                                        } else {
                                            $primer_smtp_subject = __('Test email subject', 'primer');
                                        }

                                        if (!empty($primer_smtp_options['quote_available_content'])) {
                                            $primer_smtp_message = $primer_smtp_options['quote_available_content'];
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
                                            if (empty($get_issue_status)) {
                                                $get_issue_status = 'issued';
                                            }
                                            update_post_meta($order_log_id, 'receipt_log_status', $get_issue_status);
                                            update_post_meta($order_log_id, 'receipt_log_error', $receipt_log_value);
                                            if ($receipt_log_value != null) {
                                                update_post_meta($order_log_id, 'receipt_log_total_status', 'only_errors');
                                            }
                                        }
                                        $email_logs = '';
                                        if (!empty($primer_automatically_send_file) && $primer_automatically_send_file === 'yes' && $user_order_email != '' && $user_order_email != null) {
                                            $primer_smtp = PrimerSMTP::get_instance();
                                            if (!empty($primer_smtp_options['email_from_name'])) {
                                                $from_name_email = $primer_smtp_options['email_from_name'];
                                            } else {
                                                $from_name_email = '';
                                            }
                                            if ($primer_smtp_type == 'wordpress_default') {
                                                $headers = array('Content-Type: text/html; charset=UTF-8');
                                                $mailResultSMTP = wp_mail($user_order_email, $primer_smtp_subject, $primer_smtp_message, $headers, $attachments);
                                            } else {
                                                $mailResultSMTP = $primer_smtp->primer_mail_sender($user_order_email, $from_name_email, $primer_smtp_subject, $primer_smtp_message, $attachments);
                                            }
                                            if (!$primer_smtp->credentials_configured()) {
                                                $email_logs .= __('Configure your SMTP credentials', 'primer') . "\n";
                                            }
                                            if (!$primer_smtp->credentials_configured()) {
                                                $email_logs .= __('Configure your SMTP credentials', 'primer') . "\n";
                                            }

                                            if (!empty($mailResultSMTP['error']) && !$primer_smtp->credentials_configured()) {
                                                $response_data .= '<div class="notice notice-error"><p>' . $GLOBALS['phpmailer']->ErrorInfo . '</p></div>';
                                                update_post_meta($order_log_id, 'receipt_log_email', 'not_sent');
                                                $email_logs .= $GLOBALS['phpmailer']->ErrorInfo . "\n";
                                                update_post_meta($order_log_id, 'receipt_log_email_error', $email_logs);
                                                update_post_meta($order_log_id, 'receipt_log_total_status', 'only_errors');
                                            } else {
                                                update_post_meta($order_log_id, 'receipt_log_email', 'sent');
                                                update_post_meta($order_log_id, 'receipt_log_total_status', 'only_issued');
                                            }
                                            update_post_meta($post_id, 'exist_error_log', 'exist_log');
                                        } else {
                                            if (!$primer_smtp->credentials_configured()) {
                                                $email_logs .= __('Configure your SMTP credentials', 'primer') . "\n";
                                            }
                                            $email_logs .= __('Send email automatically on order conversion disabled', 'primer') . "\n";
                                            update_post_meta($order_log_id, 'receipt_log_email', 'not_sent');
                                            update_post_meta($order_log_id, 'receipt_log_email_error', $email_logs);
                                            update_post_meta($order_log_id, 'receipt_log_total_status', 'only_issued');
                                        }
                                        update_post_meta($id_of_order, 'transmission_failure_check', 2);
                                        $response_data = '<div class="notice notice-success"><p>' . __("Orders converted", "primer") . '</p></div>';
                            } else { // if(( $response_from_array[0]->statusCode == 'Success'))
                                $mydata_options['last_request'] = $orders;
                                if (!empty($url_slug)) {
                                    $mydata_options['last_request_url'] = $url_slug;
                                }
                                update_option('primer_mydata', $mydata_options);
                                $response_data .= is_object($response_from_array[0]->errors) ? json_encode($response_from_array[0]->errors) : $response_from_array[0]->errors;
                                $receipt_log_value_array[] = $response_data;
                                continue;
                            }
                        } else { // if(!empty($response_from_array))
                            $receipt_log_value .= __('API not sent.', 'primer');
                            $receipt_log_value_array[] = __('API not sent.', 'primer');
                            update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                            $inside_response_msg = $response_message ? $response_message : __('Something wrong', 'primer');
                            $response_data .= '<div class="notice notice-error"><p>' . $inside_response_msg . ' ' . $response_code . '</p></div>';
                        }
                    } else { // if(!empty($response_to_array))
                        $receipt_log_value .= __('API not sent.', 'primer');
                        $receipt_log_value_array[] = __('API not sent.', 'primer');
                        update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                        $response_data .= '<div class="notice notice-error"><p>' . __('API not sent.', 'primer') . '</p></div>';
                    }

                    if (!empty($receipt_log_value_array)) {
                        update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value_array);
                        update_post_meta($receipt_log_id, 'receipt_log_total_status', 'only_errors');
                    }
                    if (!empty($log_ids)) {
                        foreach ($log_ids as $log_id) {
                            update_post_meta($log_id, 'receipt_log_error', $receipt_log_value_array);
                            if (!empty($receipt_log_value_array)) {
                                update_post_meta($log_id, 'receipt_log_total_status', 'only_errors');
                            }
                        }
                    }
                }
                }
            }

        wp_die();
    }



    public function primer_get_woocommerce_tax_rates(){
        global $wpdb;
        $tax_rates = array();
        $taxes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates");
        foreach($taxes  as $tax) {
            $tax_rates[] = $tax->tax_rate;
        }
        $tax_rates_arr = array_values(array_unique($tax_rates, SORT_REGULAR));
        $response = json_encode(array('counter' => count($tax_rates_arr)));
        die($response);
    }

    /**
     * Primer Export reports
     */
    public function export_invoice_to_report()
    {
        global $wpdb, $woocommerce;
        $primer_export_options = get_option('primer_export');
        $invoice_number_title = __('Invoice Number', 'primer');
        $invoice_series_number_title = __('Series of the Invoice', 'primer');
        $client_name_title = __('Client Name', 'primer');
        $client_vat_title = __('Client VAT', 'primer');
        $client_company_title = __('Client Company', 'primer');
        $client_activity_title = __('Client Activity', 'primer');
        $client_address_title = __('Client Address', 'primer');
        $client_phone_title = __('Client Phone number', 'primer');
        $client_email_title = __('Client Email', 'primer');
        $client_webpage_title = __('Client Webpage', 'primer');
        $product_name_title = __('Product name', 'primer');
        $product_quantity_title = __('Product Quantity', 'primer');
        $vat_amount_per_product_title = __('VAT Amount per product', 'primer');
        $total_amount_per_product_title = __('Total Amount per product', 'primer');
        $net_amount_per_product_title = __('Net amount (without VAT) per product', 'primer');
        $total_amount_title = __('Total Amount', 'primer');
        $total_vat_amount_title = __('Total (Sum) VAT Amount', 'primer');
        $total_net_amount_title = __('Total (Sum) Net Amount', 'primer');
        $invoice_date_title = __('Invoice Date', 'primer');
        $invoice_type_title = __('Invoice Type', 'primer');
        $invoice_headers = array(
            $invoice_number_title,
            $invoice_series_number_title,
            $client_name_title,
            $client_company_title,
            $client_vat_title,
            $client_activity_title,
            $client_address_title,
            $client_phone_title,
            $client_email_title,
            $client_webpage_title,
            $product_name_title,
            $product_quantity_title,
            $vat_amount_per_product_title,
            $total_amount_per_product_title,
            $net_amount_per_product_title,
            $total_amount_title,
            $total_vat_amount_title,
            $total_net_amount_title,
            $invoice_date_title,
            $invoice_type_title
        );
        $client_name = isset($_POST['export_select_client_name']) ? sanitize_text_field($_POST['export_select_client_name']) : $primer_export_options['export_select_client_name'];
        $client_vat = isset($_POST['export_select_client_vat']) ? sanitize_text_field($_POST['export_select_client_vat']) : $primer_export_options['export_select_client_vat'];
        $client_company = isset($_POST['export_select_client_company']) ? sanitize_text_field($_POST['export_select_client_company']) : $primer_export_options['export_select_client_company'];
        $client_activity = isset($_POST['export_select_client_activity']) ? sanitize_text_field($_POST['export_select_client_activity']) : $primer_export_options['export_select_client_activity'];
        $client_address = isset($_POST['export_select_client_address']) ? sanitize_text_field($_POST['export_select_client_address']) : $primer_export_options['export_select_client_address'];
        $client_phone = isset($_POST['export_select_client_phone']) ? sanitize_text_field($_POST['export_select_client_phone']) : $primer_export_options['export_select_client_phone'];
        $client_email = isset($_POST['export_select_client_email']) ? sanitize_text_field($_POST['export_select_client_email']) : $primer_export_options['export_select_client_email'];
        $client_web = isset($_POST['export_select_client_webpage']) ? sanitize_text_field($_POST['export_select_client_webpage']) : $primer_export_options['export_select_client_webpage'];
        $product_name = isset($_POST['export_select_product_name']) ? sanitize_text_field($_POST['export_select_product_name']) : $primer_export_options['export_select_product_name'];
        $product_quantity = isset($_POST['export_select_product_quantity']) ? sanitize_text_field($_POST['export_select_product_quantity']) : $primer_export_options['export_select_product_quantity'];
        $vat_amount_per_product = isset($_POST['export_select_vat_amount']) ? sanitize_text_field($_POST['export_select_vat_amount']) : $primer_export_options['export_select_vat_amount'];
        $total_amount_per_product = isset($_POST['export_select_total_amount_per_product']) ? sanitize_text_field($_POST['export_select_total_amount_per_product']) : $primer_export_options['export_select_total_amount_per_product'];
        $net_amount_per_product = isset($_POST['export_select_net_amount_per_product']) ? sanitize_text_field($_POST['export_select_net_amount_per_product']) : $primer_export_options['export_select_net_amount_per_product'];
        $total_amount = isset($_POST['export_select_total_amount']) ? sanitize_text_field($_POST['export_select_total_amount']) : $primer_export_options['export_select_total_amount'];
        $total_vat_amount = isset($_POST['export_select_total_vat_amount']) ? sanitize_text_field($_POST['export_select_total_vat_amount']) : $primer_export_options['export_select_total_vat_amount'];
        $total_net_amount = isset($_POST['export_select_total_net_amount']) ? sanitize_text_field($_POST['export_select_total_net_amount']) : $primer_export_options['export_select_total_net_amount'];
        $invoice_date = isset($_POST['export_select_invoice_date']) ? sanitize_text_field($_POST['export_select_invoice_date']) : $primer_export_options['export_select_invoice_date'];
        $invoice_number = isset($_POST['export_select_invoice_number']) ? sanitize_text_field($_POST['export_select_invoice_number']) : $primer_export_options['export_select_invoice_number'];
        $invoice_series_number = isset($_POST['export_select_invoice_series_number']) ? sanitize_text_field($_POST['export_select_invoice_series_number']) : $primer_export_options['export_select_invoice_series_number'];
        $export_invoice_type = isset($_POST['export_select_invoice_type']) ? sanitize_text_field($_POST['export_select_invoice_type']) : $primer_export_options['export_select_invoice_type'];
        $export_type = isset($_POST['export_type']) ? sanitize_text_field($_POST['export_type']) : 'xlsx';
        $primer_leave_blank_row = isset($_POST['export_leave_blank_row']) ? sanitize_text_field($_POST['export_leave_blank_row']) : '';
        $primer_export_from = isset($_POST['mydata_export_from']) ? sanitize_text_field($_POST['mydata_export_from']) : '';
        $primer_export_to = isset($_POST['mydata_export_to']) ? sanitize_text_field($_POST['mydata_export_to']) : '';
        $column = isset($_POST['export_select_total_vat_rate_amount']) ? sanitize_text_field($_POST['export_select_total_vat_rate_amount']) : $primer_export_options['export_select_total_vat_rate_amount'];
        $db_email_check = isset($primer_export_options['export_email_check']) ? $primer_export_options['export_email_check'] : '';
        $export_send_email = isset($_POST['export_email_check']) ? sanitize_text_field($_POST['export_email_check']) : $db_email_check;
        $ajax_send_email = isset($_POST['export_send_email']) ? sanitize_text_field($_POST['export_send_email']) : '';
        if (isset($primer_export_options['export_leave_blank_row'])) {
            if (empty($primer_leave_blank_row)) {
                $primer_leave_blank_row = $primer_export_options['export_leave_blank_row'];
            }
        }
        $primer_first_excel_column_name = isset($_POST['export_first_excel_column_name']) ? sanitize_text_field($_POST['export_first_excel_column_name']) : '';
        $primer_export_only_invoice = isset($_POST['export_only_invoice_details']) ? sanitize_text_field($_POST['export_only_invoice_details']) : '';
        if (isset($primer_export_options['export_first_excel_column_name'])) {
            if (empty($primer_first_excel_column_name)) {
                $primer_first_excel_column_name = $primer_export_options['export_first_excel_column_name'];
            }
        }
        if (isset($primer_export_options['export_only_invoice_details'])) {
            if (empty($primer_export_only_invoice)) {
                $primer_export_only_invoice = $primer_export_options['export_only_invoice_details'];
            }
        }
        if (isset($primer_export_options['mydata_export_from'])) {
            if (empty($primer_export_from)) {
                $primer_export_from = $primer_export_options['mydata_export_from'];
            }
        }

        if (isset($primer_export_options['mydata_export_to'])) {
            if (empty($primer_export_to)) {
                $primer_export_to = $primer_export_options['mydata_export_to'];
            }
        }
        $export_path_files = '';
        $upload_dir = wp_upload_dir()['basedir'];
        if (!file_exists($upload_dir . '/primer-export-invoices')) {
            mkdir($upload_dir . '/primer-export-invoices');
        }
        $export_dir_file = $upload_dir . '/primer-export-invoices';
        $export_path = isset($_POST['export_path']) ? sanitize_text_field($_POST['export_path']) : '';
        if (isset($primer_export_options['export_path'])) {
            if (empty($export_path)) {
                $export_path = $primer_export_options['export_path'];
            }
        }
        if (!empty($export_path) && $export_path == 'on') {
            $export_path_files = isset($_POST['export_path_files']) ? sanitize_text_field($_POST['export_path_files']) : $primer_export_options['export_path_files'];
            if (empty($export_path_files)) {
                $export_path_files = $export_dir_file;
            }
        }
        $receipt_ids = array();
        $client_names = array();
        $client_vats = array();
        $client_companys = array();
        $invoice_args = array();
        $test_array = array();
        $receipt_args = array(
            'posts_per_page' => -1,
            'post_type' => 'primer_receipt',
            'post_status' => 'publish',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'send_to_api_type',
                    'value' => 'test'
                ),
                array(
                    'key' => 'receipt_status',
                    'value' => 'issued'
                )
            )
        );
        if (!empty($primer_export_to) && !empty($primer_export_from)) {
            $primer_export_to = date("F jS, Y", strtotime($primer_export_to));
            $primer_export_from = date("F jS, Y", strtotime($primer_export_from));
            $receipt_args['date_query'] = array(
                array(
                    'after' => $primer_export_from,
                    'before' => $primer_export_to,
                    'inclusive' => true,
                )
            );
        } elseif (!empty($primer_export_to) && (empty($primer_export_from) || $primer_export_from = '')) {
            $primer_export_to = date("F jS, Y", strtotime($primer_export_to));
            $receipt_args['date_query'] = array(
                array(
                    'before' => $primer_export_to,
                    'inclusive' => true,
                )
            );
        } elseif ((empty($primer_export_to) || $primer_export_to = '') && !empty($primer_export_from)) {
            $primer_export_from = date("F jS, Y", strtotime($primer_export_from));
            $receipt_args['date_query'] = array(
                array(
                    'after' => $primer_export_from,
                    'inclusive' => true,
                )
            );
        }

        $invoice_client_value = array();
        $client_vat_number = array();
        $client_vat_number_str = '';
        $client_company_str = '';
        $client_web_value_str = '';
        $invoice_type_value_str = '';
        $client_activity_value = array();
        $client_address_value = array();
        $client_phone_value = array();
        $client_email_value = array();
        $client_web_value = array();
        $product_name_value = array();
        $product_quantity_value = array();
        $vat_amount_per_product_value = array();
        $total_amount_per_product_value = array();
        $net_amount_per_product_value = array();
        $total_amount_value = array();
        $total_vat_amount_value = array();
        $total_net_amount_value = array();
        $invoice_date_value = array();
        $invoice_type_value = array();
        $invoice_number_value = array();
        $invoice_type_value_str_arr = array();
        $invoice_series_number_value = array();
        $already_in_excel = array();
        $taxes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates");
        foreach($taxes  as $tax) {
            $tax_rates[] = $tax->tax_rate;
        }
        $tax_rates_arr = array_values(array_unique($tax_rates, SORT_REGULAR));
        $column = isset($_POST['export_select_total_vat_rate_amount']) ? sanitize_text_field($_POST['export_select_total_vat_rate_amount']) : $primer_export_options['export_select_total_vat_rate_amount'];
        $receipt_query = new WP_Query($receipt_args);
        $receipt_count = 0;
        if ($receipt_query->have_posts()):
            while ($receipt_query->have_posts()):
                $receipt_query->the_post();
                $receipt_status_text = '';
                $receipt_id = get_the_ID();
                if ( $receipt_id == null ) {
                    continue;
                }
                $receipt_ids[] = get_the_ID();
                $product_name_fields = get_post_meta(get_the_ID(), 'receipt_product', false);
                $count_product = count($product_name_fields);
                if ($primer_export_only_invoice == 'on') {
                    $count_product = 1;
                }
                $primer_license_data = get_option('primer_licenses');
                $receipt_status = get_post_meta(get_the_ID(), 'receipt_status', true);
                $order_from_invoice = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
                $invoice_client_value_str = get_post_meta(get_the_ID(), 'receipt_client', true);
                $user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);
                $user_data = get_user_by('ID', $user_display_name);
                if (!empty($user_data->user_url)) {
                    $client_web_value_str = $user_data->user_url;
                }
                if (!empty($order_from_invoice)) {
                    $total_order = wc_get_order($order_from_invoice);
                }
                $user_first_name = '';
                $user_last_name = '';
                $inside_tax_rate = '';
                if (empty($total_order)) {
                    continue; // Skip to the next iteration if the order doesn't exist
                }
                if (!empty($total_order)) {
                    $user_first_name = $total_order->get_billing_first_name();
                    $user_last_name = $total_order->get_billing_last_name();
                    $client_email_value_str = $total_order->get_billing_email();
                    $client_address_value = $total_order->get_billing_address_1();
                    $client_address_value .= $total_order->get_billing_address_2();
                    $client_phone_value_str = $total_order->get_billing_phone();
                    $client_vat_number_str = get_post_meta($order_from_invoice, '_billing_vat', true);
                    $client_company_str = get_post_meta($order_from_invoice, '_billing_company', true);
                    $tax_classes = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
                    if (!in_array('', $tax_classes)) { // Make sure "Standard rate" (empty class name) is present.
                        array_unshift($tax_classes, '');
                    }
                    foreach ($total_order->get_items() as $item_id => $item_data) {
                       // $product_id = $item_data->get_product_id();
                       // $product_instance = wc_get_product($product_id);
                        $product_name_value[] = $item_data->get_name();
                        $product_quantity_value[] = $item_data->get_quantity();
                        $product_tax_class = $item_data->get_tax_class();
                        $taxes = WC_Tax::get_rates_for_tax_class($product_tax_class);
                        $tax_arr = json_decode(json_encode($taxes), true);
                        $invoice_type = get_the_terms($receipt_id, 'receipt_status');
                        $invoice_type_slug = '';
                        if (!empty($invoice_type)) {
                            $invoice_type_slug = $invoice_type[0]->slug;
                        }
                        if($invoice_type_slug != 'credit-receipt' && $invoice_type_slug != 'credit-invoice'){
                            $inside_tax_rate = $item_data->get_total_tax();
                            $net_amount_per_product_value_str = $item_data->get_total();
                            $subtotal_item_tax = $item_data->get_subtotal_tax();
                            $total_order_payment = $item_data->get_total();
                        } else {
                            $inside_tax_rate = -1 * $item_data->get_total_tax();
                            $net_amount_per_product_value_str = -1 * $item_data->get_total();
                            $subtotal_item_tax = -1 * $item_data->get_subtotal_tax();
                            $total_order_payment = -1 * $item_data->get_total();
                        }
                        $total_amount_per_product_value_str = $total_order_payment + $inside_tax_rate;
                        $vat_amount_per_product_value[] = number_format((float)$inside_tax_rate, 2);
                        $total_amount_per_product_value[] = number_format((float)$total_amount_per_product_value_str, 2);
                        $net_amount_per_product_value[] = number_format((float)$net_amount_per_product_value_str, 2);
                    }
                    if (!empty($primer_leave_blank_row)) {
                        $product_name_value[] = '';
                        $product_quantity_value[] = '';
                        $vat_amount_per_product_value[] = '';
                        $total_amount_per_product_value[] = '';
                        $net_amount_per_product_value[] = '';
                    }
                    if (!empty($invoice_type)) {
                        $invoice_type_slug = $invoice_type[0]->slug;
                        switch ($invoice_type_slug) {
                            case 'greek_receipt':
                                $invoice_type_value_str = __('Απόδειξη Λιανικής Πώλησης');
                                break;
                            case 'greek_invoice':
                                $invoice_type_value_str = __('Τιμολόγιο Πώλησης');
                                break;
                            case 'english_receipt':
                                $invoice_type_value_str = __('Τιμολόγιο Πώλησης / Ενδοκοινοτικές Παραδόσεις');
                                break;
                            case 'english_invoice':
                                $invoice_type_value_str = __('Τιμολόγιο Πώλησης / Παραδόσεις Τρίτων Χωρών');
                                break;
                            case 'credit-receipt':
                                $invoice_type_value_str = __('Πιστωτικό Στοιχείο Λιανικής');
                                break;
                            case 'credit-invoice':
                                $invoice_type_value_str = __('Πιστωτικό Τιμολόγιο Συσχετιζόμενο');
                                break;
                        }
                        if($invoice_type_slug != 'credit-receipt' && $invoice_type_slug != 'credit-invoice') {
                            $total_vat_amount_value_str = $total_order->get_total_tax();
                            $total_amount_value_str = $total_order->get_total();
                            $shipping_total = $total_order->get_shipping_total();
                            $fee_total = 0;
                            foreach( $total_order->get_items('fee') as $item_id => $item_fee ) {
                                // The fee total amount
                                $fee_total = $fee_total + $item_fee->get_total();
                            }
                            $total_net_amount_value_str = $total_order->get_subtotal() + $shipping_total + $fee_total - $total_order->get_total_discount();
                        }else{
                            $total_vat_amount_value_str = -1 * $total_order->get_total_tax();
                            $total_amount_value_str = -1 * $total_order->get_total();
                            $shipping_total =  $total_order->get_shipping_total();
                            $fee_total = 0;
                            foreach( $total_order->get_items('fee') as $item_id => $item_fee ) {
                                // The fee total amount
                                $fee_total = $fee_total + $item_fee->get_total();
                            }
                            $total_net_amount_value_str = -1 * ($total_order->get_subtotal() + $shipping_total + $fee_total - $total_order->get_total_discount());
                        }
                }
                for ($i = 0; $i < $count_product; $i++) {
                    $total_vat_amount_value[] = number_format((float)$total_vat_amount_value_str, 2,'.','');
                }
                if (!empty($primer_leave_blank_row)) {
                    $total_vat_amount_value[] = '';
                }
                for ($i = 0; $i < $count_product; $i++) {
                    $total_amount_value[] = number_format((float)$total_amount_value_str, 2,'.','');
                }
                if (!empty($primer_leave_blank_row)) {
                    $total_amount_value[] = '';
                }
                for ($i = 0; $i < $count_product; $i++) {
                    $total_net_amount_value[] = number_format((float)$total_net_amount_value_str, 2,'.','');
                }
                if (!empty($primer_leave_blank_row)) {
                    $total_net_amount_value[] = '';
                }
                $user_full_name = $user_first_name . ' ' . $user_last_name;
                if (empty($invoice_client_value)) {
                    $invoice_client_value[] = $user_full_name;
                }
                if (empty($client_address_value) && !empty($client_address) && !empty($primer_license_data['companyAddress'])) {
                    $client_address_value = $primer_license_data['companyAddress'];
                }
                if (empty($client_phone_value) && !empty($primer_license_data['companyPhoneNumber'])) {
                    $client_phone_value[] = $primer_license_data['companyPhoneNumber'];
                }
                    $invoice_type_slug_name = explode('_', $invoice_type_slug);
                    if(is_array($invoice_type_slug_name) && array_key_exists(1,$invoice_type_slug_name)) {
                        $find_invoice_in_slug = $invoice_type_slug_name[1];
                    }else{
                        $find_invoice_in_slug = '';
                    }
                    for ($i = 0; $i < $count_product; $i++) {
                        if ($find_invoice_in_slug == 'invoice') {
                            $client_activity_value[] = get_post_meta($order_from_invoice, '_billing_store', true);
                        } else {
                            $client_activity_value[] = '';
                        }
                    }
                }
                if (!empty($primer_leave_blank_row)) {
                    $client_activity_value[] = '';
                }
                $receipt_number = get_post_meta(get_the_ID(), '_primer_receipt_number', true);
                $receipt_series_number = get_post_meta(get_the_ID(), '_primer_receipt_series', true);
                $receipt_order_date = get_the_date('d/m/Y', $receipt_id);
                $receipt_order_time = get_the_date('H:i', $receipt_id);
                $receipt_date = get_post_meta($receipt_id, 'success_mydata_date', true);
                $receipt_time = get_post_meta($receipt_id, 'success_mydata_time', true);
                if (empty($receipt_date)) {
                    $receipt_date = $receipt_order_date;
                }
                if (empty($receipt_time)) {
                    $receipt_time = $receipt_order_time;
                }
                for ($i = 0; $i < $count_product; $i++) {
                    $invoice_date_value[] = "$receipt_date $receipt_time";
                }
                if (!empty($primer_leave_blank_row)) {
                    $invoice_date_value[] = '';
                }
                if (isset($client_name) && !empty($client_name)) {
                    for ($i = 0; $i < $count_product; $i++) {
                        $invoice_args[$client_name][$client_name_title][] = $invoice_client_value_str;
                    }
                    if (!empty($primer_leave_blank_row)) {
                        $invoice_args[$client_name][$client_name_title][] = '';
                    }
                }
                if (isset($client_vat) && !empty($client_vat)) {
                    for ($i = 0; $i < $count_product; $i++) {
                        $invoice_args[$client_vat][$client_vat_title][] = $client_vat_number_str;
                    }
                    if (!empty($primer_leave_blank_row)) {
                        $invoice_args[$client_vat][$client_vat_title][] = '';
                    }
                }
                if (isset($client_company) && !empty($client_company)) {
                    for ($i = 0; $i < $count_product; $i++) {
                        $invoice_args[$client_company][$client_company_title][] = $client_company_str;
                    }
                if (!empty($primer_leave_blank_row)) {
                    $invoice_args[$client_company][$client_company_title][] = '';
                }
            }
                if (isset($client_activity) && !empty($client_activity)) {
                    $invoice_args[$client_activity][$client_activity_title] = $client_activity_value;
                }
                if (isset($client_address) && !empty($client_address)) {
                    for ($i = 0; $i < $count_product; $i++) {
                        $invoice_args[$client_address][$client_address_title][] = $client_address_value;
                    }
                    if (!empty($primer_leave_blank_row)) {
                        $invoice_args[$client_address][$client_address_title][] = '';
                    }
                }
                if (isset($client_phone) && !empty($client_phone)) {
                    for ($i = 0; $i < $count_product; $i++) {
                        $invoice_args[$client_phone][$client_phone_title][] = $client_phone_value_str;
                    }
                    if (!empty($primer_leave_blank_row)) {
                        $invoice_args[$client_phone][$client_phone_title][] = '';
                    }
                }
                if (isset($client_email) && !empty($client_email)) {
                    for ($i = 0; $i < $count_product; $i++) {
                        $invoice_args[$client_email][$client_email_title][] = $client_email_value_str;
                    }
                    if (!empty($primer_leave_blank_row)) {
                        $invoice_args[$client_email][$client_email_title][] = '';
                    }
                }
                if (isset($client_web) && !empty($client_web)) {
                    for ($i = 0; $i < $count_product; $i++) {
                        $invoice_args[$client_web][$client_webpage_title][] = $client_web_value_str;
                    }
                    if (!empty($primer_leave_blank_row)) {
                        $invoice_args[$client_web][$client_webpage_title][] = '';
                    }
                }
                if (isset($product_name) && !empty($product_name)) {
                    $invoice_args[$product_name][$product_name_title] = $product_name_value;
                }
                if (isset($product_quantity) && !empty($product_quantity)) {
                    $invoice_args[$product_quantity][$product_quantity_title] = $product_quantity_value;
                }
                if (isset($vat_amount_per_product) && !empty($vat_amount_per_product)) {
                    $invoice_args[$vat_amount_per_product][$vat_amount_per_product_title] = $vat_amount_per_product_value;
                }
                if (isset($total_amount_per_product) && !empty($total_amount_per_product)) {
                    $invoice_args[$total_amount_per_product][$total_amount_per_product_title] = $total_amount_per_product_value;
                }
                if (isset($net_amount_per_product) && !empty($net_amount_per_product)) {
                    $invoice_args[$net_amount_per_product][$net_amount_per_product_title] = $net_amount_per_product_value;
                }
                if (isset($total_amount) && !empty($total_amount)) {
                    $invoice_args[$total_amount][$total_amount_title] = $total_amount_value;
                }
                if (isset($total_vat_amount) && !empty($total_vat_amount)) {
                    $invoice_args[$total_vat_amount][$total_vat_amount_title] = $total_vat_amount_value;
                }
                if (isset($total_net_amount) && !empty($total_net_amount)) {
                    $invoice_args[$total_net_amount][$total_net_amount_title] = $total_net_amount_value;
                }
                if (isset($invoice_date) && !empty($invoice_date)) {
                    $invoice_args[$invoice_date][$invoice_date_title] = $invoice_date_value;
                }
                if (isset($invoice_series_number) && !empty($invoice_series_number)) {
                    for ($i = 0; $i < $count_product; $i++) {
                        $invoice_series_number_value[] = $receipt_series_number;
                        $invoice_args[$invoice_series_number][$invoice_series_number_title] = $invoice_series_number_value;
                    }
                    if (!empty($primer_leave_blank_row)) {
                        $invoice_series_number_value[] = '';
                    }
                }
                if (isset($invoice_number) && !empty($invoice_number)) {
                    for ($i = 0; $i < $count_product; $i++) {
                        $invoice_number_value[] = $receipt_number;
                        $invoice_args[$invoice_number][$invoice_number_title] = $invoice_number_value;
                    }
                    if (!empty($primer_leave_blank_row)) {
                        $invoice_number_value[] = '';
                    }
                }

                if (isset($export_invoice_type) && !empty($export_invoice_type)) {
                    for ($i = 0; $i < $count_product; $i++) {
                        $invoice_args[$export_invoice_type][$invoice_type_title][] = $invoice_type_value_str;
                    }
                    if (!empty($primer_leave_blank_row)) {
                        $invoice_args[$export_invoice_type][$invoice_type_title][] = '';
                    }
                }
                if (isset($column) && !empty($column)) {
                    $order = $total_order;
                    $order_customer_country = $order->get_billing_country();
                    $customer_country = $order_customer_country;
                    $tax_percent = array();
                    $tax_total = array();
                    $net_total = array();
                    $total_with_vat = array();
                    $i = 0;
                    foreach ($order->get_items('tax') as $item_id => $item) {
                        $tax_rate_id = $item->get_rate_id(); // Tax rate ID
                        $tax_total[$i] = $item->get_tax_total(); // Tax Total
                        $tax_percent[$i] = WC_Tax::get_rate_percent($tax_rate_id);// Tax percentage
                        $tax_rate = str_replace('%', '', $tax_percent);
                        foreach ($order->get_items() as $item_id => $item_tax) {
                            $product_tax_class = $item_tax->get_tax_class();
                            $taxes = WC_Tax::get_rates_for_tax_class($product_tax_class);

                            $tax_arr = json_decode(json_encode($taxes), true);
                            $inside_tax_rate = 0;
                            foreach ($tax_arr as $tax) {
                                if (!empty($tax['tax_rate_country']) && $tax['postcode_count'] != 0 && $tax['city_count'] != 0 && !empty($tax['tax_rate_state'])) {
                                    if ($tax['tax_rate_country'] == $order_customer_country && (in_array($order->get_billing_postcode(), $tax['postcode']) || in_array($order->get_shipping_postcode(), $tax['postcode'])) && (in_array(strtoupper($order->get_billing_city()), $tax['city']) || in_array(strtoupper($order->get_shipping_city()), $tax['city'])) && ($order->get_billing_state() == $tax['tax_rate_state'] || $order->get_shipping_state() == $tax['tax_rate_state'])) {
                                        $inside_tax_rate = $tax['tax_rate'];
                                        break;
                                    } else {
                                        continue;
                                    }
                                } elseif (!empty($tax['tax_rate_country']) && $tax['postcode_count'] != 0 && $tax['city_count'] != 0) {
                                    if ($tax['tax_rate_country'] == $order_customer_country && (in_array($order->get_billing_postcode(), $tax['postcode']) || in_array($order->get_shipping_postcode(), $tax['postcode'])) && (in_array(strtoupper($order->get_billing_city()), $tax['city']) || in_array(strtoupper($order->get_shipping_city()), $tax['city']))) {
                                        $inside_tax_rate = $tax['tax_rate'];
                                        break;
                                    } else {
                                        continue;
                                    }
                                } elseif (!empty($tax['tax_rate_country']) && $tax['postcode_count'] != 0) {
                                    if ($tax['tax_rate_country'] == $order_customer_country && (in_array($order->get_billing_postcode(), $tax['postcode']) || in_array($order->get_shipping_postcode(), $tax['postcode']))) {
                                        $inside_tax_rate = $tax['tax_rate'];
                                        break;
                                    } else {
                                        continue;
                                    }
                                } elseif (!empty($tax['tax_rate_country'])) {
                                    if ($tax['tax_rate_country'] == $order_customer_country) {
                                        $inside_tax_rate = $tax['tax_rate'];
                                        break;
                                    } else {
                                        continue;
                                    }
                                } elseif (empty($tax['tax_rate_country']) && $tax['postcode_count'] == 0 && $tax['city_count'] == 0 && empty($tax['tax_rate_state'])) {
                                    if ($item_tax->get_total_tax() != 0) {
                                        $inside_tax_rate = $tax['tax_rate'];
                                        break;
                                    } else {
                                        $inside_tax_rate = 0;
                                    }
                                }
                            }
                            if(is_int($inside_tax_rate) || is_float($inside_tax_rate)){
                                $inside_tax_rate = round($inside_tax_rate);
                            }
                        }
                        if ($order->get_shipping_total()) {
                            $check_vat_ship = 1;
                            foreach ($order->get_items('shipping') as $item_id => $item) {
                                $shipping_method_total_tax_box = $item->get_total();
                                $shipping_method_total_tax = $item->get_total_tax();
                                $inside_tax_rate_ship_tax = round($order->get_shipping_tax() / $order->get_total_shipping(), 2) * 100;
                                if ($inside_tax_rate_ship_tax == $tax_rate[$i]) {
                                    $tax_total[$i] = $tax_total[$i] + $shipping_method_total_tax;
                                }
                            }
                        }
                        foreach ($order->get_items('fee') as $item_id => $item_fee) {
                            $fee_total = $item_fee->get_total();
                            $fee_total_tax = $item_fee->get_total_tax();
                            $fee_tax_rate = round($item_fee->get_total_tax() / $item_fee->get_total(), 2) * 100;
                            if ($fee_tax_rate == $tax_rate[$i]) {
                                $net_total[$i] = $net_total[$i] + $fee_total;
                            }
                        }
                        $i++;
                    }
                    $increment_position_rate = $receipt_count;
                    for ($j = 0; $j < count($tax_rates_arr); $j++) {
                        $current_tax_rate = $tax_rates_arr[$j] . '%';
                        for ($i = 0; $i < $count_product; $i++) {
                            for ($k = 0; $k < count($tax_percent); $k++) {
                                if ($tax_rates_arr[$j] == (int)$tax_percent[$k]) {
                                    $tax_total[$k] = number_format((float)$tax_total[$k], 2);
                                    if (in_array($tax_percent[$k], $already_in_excel)) {
                                        $invoice_args[array_search($tax_percent[$k], $already_in_excel)][$tax_percent[$k]][$increment_position_rate] = $tax_total[$k];
                                    } else {
                                        $invoice_args[$column][$tax_percent[$k]][$increment_position_rate] = $tax_total[$k];
                                        $already_in_excel[$column] = $tax_percent[$k];
                                        $column++;
                                    }
                                }
                            }
                        }
                    }
                }
                $receipt_count++;
            endwhile;

        endif;
        wp_reset_postdata();

        ksort($invoice_args, SORT_NUMERIC);

        $this->export_invoice_files($receipt_ids, $invoice_args, $export_type, $primer_leave_blank_row, $primer_first_excel_column_name, $export_path, $export_path_files, $export_send_email, $ajax_send_email);
    }

    public function export_invoice_files($receipt_ids, $invoice_args, $export_type, $primer_leave_blank_row, $primer_first_excel_column_name, $export_path, $export_path_files, $export_send_email, $ajax_send_email) {
        global $wpdb;

        $post_type = 'primer_receipt';
        $type = $export_type;
        $primer_export_data = get_option('primer_export');
        $excel_leave_row = isset($primer_export_data['export_leave_blank_row']) ? $primer_export_data['export_leave_blank_row'] : $primer_leave_blank_row;
        $excel_first_row_column = isset($primer_export_data['export_first_excel_column_name']) ? $primer_export_data['export_first_excel_column_name'] : $primer_first_excel_column_name;
        $export_path_db = isset($primer_export_data['export_path']) ? sanitize_text_field($primer_export_data['export_path']) : '';
        $export_path = $export_path ? $export_path : $export_path_db;
        $export_totals_row = isset($primer_export_data['export_row_totals']) ? sanitize_text_field($primer_export_data['export_row_totals']) : '';
        $meta_values = array();
        $header_row = array();
        $data_rows = array();

        // Query the posts
        $args 	= array (
            'post_type'     => $post_type,
            'posts_per_page'=> -1,
            'post_status'   => 'publish',
            'post__in'      => $receipt_ids,
            'inclusive' => true,
        );

        $the_query = new WP_Query( apply_filters('primer_export_csv_query', $args) );

        if (!empty($invoice_args)) {
            foreach ($invoice_args as $header_k => $invoice_arg) {
                foreach ( $invoice_arg as $inside_k => $item ) {
                    $header_row[$header_k] = $inside_k;
                }
            }
        }

        if ( $the_query->have_posts() ) :
            // post meta header row
            $postmeta_headers = array();
            while ( $the_query->have_posts() ) : $the_query->the_post();
                $id = get_the_ID();
                $post_metas = get_post_meta($id, '', true);
            endwhile;

            // reset to start populating data
            rewind_posts();
            $count_product = 0;
            // initialize row with empty cells
            $row = array();
            $bottom_row = array();
            $count_k = 0;
            $post_count = 0;

            foreach ($invoice_args as $arg_k => $single_args) {
                foreach ($single_args as $single_key => $item_value) {
                    foreach ($item_value as $kv => $value) {
                        $bottom_row[$arg_k] = '';
                        if (!is_numeric($single_key)) {
                            $data_rows[$kv][$arg_k] = $value;
                        } else {
                            $data_rows[$kv][$arg_k] = '';
                        }
                    }
                }
            }

            while ( $the_query->have_posts() ) : $the_query->the_post();

                $order_from_invoice = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
                $receipt_number = get_post_meta(get_the_ID(), '_primer_receipt_number', true);
                $product_name = get_post_meta(get_the_ID(), 'receipt_product', true);
                $product_name_fields = get_post_meta(get_the_ID(), 'receipt_product', false);
                $post_count++;
            endwhile;

        endif;

        $header_row = apply_filters( 'primer_export_csv_headers', $header_row );
        $data_rows = apply_filters( 'primer_export_csv_data', $data_rows );

        // Create the filename
        if ($type == 'csv') {
            $filename = sanitize_file_name( $type . '-export-' . date( 'Y-m-d H-i' ) . '.csv' );
          //  $this->set_csv_headers( $filename );
        } else {
            $filename = sanitize_file_name( $type . '-export-' . date( 'Y-m-d H-i' ) . '.xlsx' );
//			$this->set_excel_headers( $filename );
        }
        $csv_dir_file = '';
        $downloaded_file = '';
        $downloaded_file_name = '';
        $upload_dir = wp_upload_dir()['basedir'];

        if ($export_path == 'on' && !empty($export_path_files)) {
            if (!file_exists($upload_dir . '/' . $export_path_files)) {
                mkdir($upload_dir . '/' . $export_path_files);
            }
            $csv_dir_file = $upload_dir . '/' . $export_path_files . '/'.$filename;
            $downloaded_file = WP_CONTENT_URL . '/uploads/' . $export_path_files . '/'.$filename;
            $downloaded_file_name = $filename;
        } else {
            if (!file_exists($upload_dir . '/primer-export-invoices')) {
                mkdir($upload_dir . '/primer-export-invoices');
            }
            $csv_dir_file = $upload_dir . '/primer-export-invoices/'.$filename;
            $downloaded_file = WP_CONTENT_URL . '/uploads/primer-export-invoices/'.$filename;
            $downloaded_file_name = $filename;
        }


        if ($type == 'csv') {
            $fh = @fopen( "$csv_dir_file", 'w' );
            fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );

            if (!empty($primer_first_excel_column_name) && $primer_first_excel_column_name == 'on') {
                fputcsv( $fh, $header_row );
            }


            foreach ( $data_rows as $data_row ) {
                fputcsv( $fh, $data_row );
            }

            fclose( $fh );
        } else {

            $i = 1;

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            if (!empty($excel_first_row_column)) {
                foreach ($header_row as $header_key => $header_value) {
                    $sheet->setCellValue($header_key.'1', "$header_value");
                }
                $i = 2;
            }

            foreach($data_rows as $data_items){
                foreach ($header_row as $header_key => $header_value) {
                    if (isset($data_items[$header_key])) {
                        $value = $data_items[$header_key];
                        $sheet->setCellValue($header_key . $i, $value);
                    }
                }

                $i++;
            }

            if($export_totals_row == 'on') {
                $sheet->setCellValue('A' . $i, 'ΣΥΝΟΛΑ');
                $i++;
                $emptySumHeaders = ['Client Name', 'Client Company', 'Client VAT', 'Client Activity', 'Client Address', 'Client Phone number', 'Client Email',
                                    'Client Webpage', 'Product name', 'Product Quantity', 'Invoice Number', 'Series of the Invoice', 'Invoice Date', 'Invoice Type'];

                foreach ($header_row as $header_key => $header_value) {
                    if (isset($data_items[$header_key])) {
                        if (!empty($primer_first_excel_column_name) && $primer_first_excel_column_name == 'on') {
                            $sum_start = 2;
                        }else{
                            $sum_start = 1;
                        }
                        $last_row = $i - 1;

                        if ( (in_array($header_value, $emptySumHeaders)) )  {
                            $sheet->setCellValue($header_key . $last_row + 1, '');
                        } else {
                            $sheet->setCellValue($header_key . $i, '=SUM(' . $header_key . ''.$sum_start.':' . $header_key . $last_row . ')');
                        }
                    }
                }
            }
            $writer = new Xlsx($spreadsheet);
            $writer->save($csv_dir_file);

        }

        $response = array();
        if(!empty($downloaded_file)) {
            $response = json_encode(array('file' => $downloaded_file, 'file_name' => $downloaded_file_name));
        } else {
            $response = json_encode(array('file' => '', 'file_name' => ''));
        }

        // Get Notification Emails
        $activation_email_send = isset($primer_export_data['export_email_check']) ? $primer_export_data['export_email_check'] : $export_send_email;
        $primer_smtp = PrimerSMTP::get_instance();
        $primer_smtp_subject = __('Your export file from Primer', 'primer');

        if (!empty($activation_email_send)) {
            $export_admin_emails = $primer_export_data['export_send_email'];
            if (!empty($export_admin_emails)) {
                $admin_emails = explode(',', $export_admin_emails);
                foreach ( $admin_emails as $admin_email ) {
                    $emails[] = trim( sanitize_email($admin_email) );
                }
                $primer_smtp_options = get_option('primer_emails');
                $primer_smtp_type = $primer_smtp_options['smtp_type'];
                if (!empty($emails)) {
                    foreach ( $emails as $to_admin_email ) {
                        if($primer_smtp_type == 'wordpress_default'){
                            $mailResultSMTP = wp_mail($to_admin_email, $primer_smtp_subject, 'export file','', $csv_dir_file);
                        }else{
                            if (!empty($primer_smtp_options['email_from_name'])) {
                                $from_name_email = $primer_smtp_options['email_from_name'];
                            } else {
                                $from_name_email = '';
                            }
                            $mailResultSMTP = $primer_smtp->primer_mail_sender($to_admin_email,$from_name_email, $primer_smtp_subject, 'export file', $csv_dir_file);
                        }
                    }
                }
            }
        }
        wp_die($response);
    }
	/**
	 * Set the headers for the CSV file
	 *
	 * @since 	2.0.0
	 */
	public function set_csv_headers( $filename ) {

		/*
		 * Disables caching
		 */
		$now = date("D, d M Y H:i:s");
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
		header("Last-Modified: {$now} GMT");

		/*
		 * Forces the download
		 */
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");

		/*
		 * disposition / encoding on response body
		 */
		header("Content-Disposition: attachment;filename={$filename}");
		header("Content-Transfer-Encoding: binary");
	}

	/**
	 * Set the headers for the Excel file
	 *
	 * @since 	2.0.0
	 */
	public function set_excel_headers( $filename ) {

		/*
		 * Disables caching
		 */
		$now = date("D, d M Y H:i:s");
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");

		header("Content-Type: application/vnd.ms-excel; charset=utf-8");

		header("Content-Disposition: attachment; filename=\"$filename\"");
		header("Content-Transfer-Encoding: binary");
	}

    public function export_csv_log($log_ids, $log_successful, $log_failed) {
        global $wpdb ;
        $post_type = 'pr_log_automation';
        $type = 'automation_log';
        $meta_values = array();
        $header_row = array();
        $data_rows = array();
        // Query the posts
        $args 	= array (
            'post_type'     => $post_type,
            'posts_per_page'=> -1,
            'post_status'   => 'publish',
            'inclusive' => true,
        );
        if(!empty($log_ids)) {
            $args['post__in'] = $log_ids;
        }
        if (!empty($log_successful)) {
            $meta_values['receipt_log_automation_total_status'][] = 'only_issued';
        }
        if (!empty($log_failed)) {
            $meta_values['receipt_log_automation_total_status'][] = 'only_errors';
        }

        if (!empty($meta_values)) {
            $args['meta_query']['relation'] = 'AND';
            $i = 0;
            foreach ( $meta_values as $key => $meta_value ) {
                $i++;
                $args['meta_query'][$i]['key'] = $key;
                $args['meta_query'][$i]['value'] = $meta_value;
            }
        }
        $the_query = new WP_Query($args);

        if ( $the_query->have_posts() ) :
            // post meta header row
            $postmeta_headers = array();
            while ( $the_query->have_posts() ) : $the_query->the_post();
                $id = get_the_ID();
                $post_metas = get_post_meta($id, '', true);
            endwhile;

            // header row
            $header_row = array(
                0  => __( 'Order No', 'primer' ),
                1  => __( 'Order Date', 'primer' ),
                2  => __( 'Invoice No', 'primer' ),
                3  => __( 'Invoice Date', 'primer' ),
                4  => __( 'Client', 'primer' ),
                5  => __( 'Issued receipt', 'primer' ),
                6  => __( 'Email send', 'primer' ),
                7  => __( 'Receipt Error', 'primer' ),
                8  => __( 'Email Error', 'primer' ),
            );

            $columns = count( $header_row );
            // reset to start populating data
            rewind_posts();
            $check_id = array();
            while ( $the_query->have_posts() ) : $the_query->the_post();
                $order_id = get_post_meta(get_the_ID(), 'receipt_log_automation_order_id', true);
                $order_date = get_post_meta(get_the_ID(), 'receipt_log_automation_order_date', true);
                $log_order_id = get_post_meta(get_the_ID(), 'receipt_log_automation_order_id', true);
                $receipt_log_invoice_id= get_post_meta( get_the_ID(), 'receipt_log_invoice_id', true );
                if ( get_post_meta($receipt_log_invoice_id , '_primer_receipt_series', true) == "EMPTY" ) {
                    $invoice_no = get_post_meta($receipt_log_invoice_id , '_primer_receipt_number', true);
                } else {
                    $invoice_no = get_post_meta($receipt_log_invoice_id , '_primer_receipt_series', true) . " " . get_post_meta($receipt_log_invoice_id , '_primer_receipt_number', true);
                }
                $invoice_log_id = $invoice_no;
                $invoice_log_date = get_post_meta(get_the_ID(), 'receipt_log_automation_invoice_date', true);
                $meta_key = 'order_id_to_receipt';
                $get_invoice_by_id = $wpdb->get_col(
                    $wpdb->prepare(
                        "
					SELECT key1.post_id
					FROM $wpdb->postmeta key1
					WHERE key1.meta_key = %s AND key1.meta_value = '$log_order_id'", $meta_key ) );

                if (empty($invoice_log_id)) {
                    if (!empty($get_invoice_by_id)) {
                        $invoice_log_id = $get_invoice_by_id[0];
                    }
                }
                if (empty($invoice_log_date)) {
                    if (!empty($get_invoice_by_id)) {
                        $invoice_log_date = get_the_date('F j, Y', $get_invoice_by_id[0]);
                    }
                }
                $order_from_invoice_log = get_post_meta(get_the_ID(), 'receipt_log_automation_order_id', true);
                $invoice_log_client = get_post_meta(get_the_ID(), 'receipt_log_automation_client', true);
                $order = wc_get_order( $order_from_invoice_log );
                if ( is_a( $order, 'WC_Order_Refund' ) ) {
                    $order = wc_get_order( $order->get_parent_id() );
                }
                if(!empty($order->get_billing_first_name())){
                    $user_first_name = $order->get_billing_first_name();
                    $user_last_name = $order->get_billing_last_name();
                    $user_full_name = $user_first_name . ' ' . $user_last_name;
                }else if(!empty($order->get_billing_company())){
                    $user_full_name = $order->get_billing_company();
                } else {
                    $user_full_name = 'RETAIL CLIENT';
                }

                if (empty($invoice_log_client)) {
                    $invoice_log_client = $user_full_name;
                }
                $receipt_log_status = get_post_meta(get_the_ID(), 'receipt_log_automation_status', true);
                switch ($receipt_log_status) {
                    case 'issued':
                        $receipt_log_status_text = 'Yes';
                        break;
                    default:
                        $receipt_log_status_text = 'No';
                        break;
                }
                $receipt_log_email_status = get_post_meta(get_the_ID(), 'receipt_log_automation_email', true);

                switch ($receipt_log_email_status) {
                    case 'sent':
                        $receipt_log_email_status_text = 'Yes';
                        break;
                    default:
                        $receipt_log_email_status_text = 'No';
                        break;
                }
                $receipt_log_error = json_encode(get_post_meta(get_the_ID(), 'receipt_log_automation_error', true));
                if ($receipt_log_error=="\"\"") {
                    $receipt_log_error = "Successful";
                }
                $receipt_log_email_error = get_post_meta(get_the_ID(), 'receipt_log_automation_email_error', true);
                if ($receipt_log_error!="Successful") {
                    $receipt_log_email_error = "Receipt error";
                }

                // initialize row with empty cells
                $row = array();
                // Put each posts data into the appropriate cell
                $row[0]  = $order_id;
                $row[1]  = $order_date;
                $row[2]  = $invoice_log_id;
                $row[3]  = $invoice_log_date;
                $row[4]  = $invoice_log_client;
                $row[5]  = $receipt_log_status_text;
                $row[6]  = $receipt_log_email_status_text;
                $row[7]  = $receipt_log_error;
                $row[8]  = $receipt_log_email_error;
                $row = apply_filters( 'primer_export_csv_row', $row, get_the_ID(), $header_row );
                $data_rows[] = $row;
            endwhile;
        endif;
        $header_row = apply_filters( 'primer_export_csv_headers', $header_row );
        $data_rows = apply_filters( 'primer_export_csv_data', $data_rows );
        // Create the filename
        $filename = sanitize_file_name( $type . '-export-' . date( 'Y-m-d H-i' ) . '.csv' );
        $this->set_csv_headers( $filename );
        $upload_dir = wp_upload_dir()['basedir'];
        if (!file_exists($upload_dir . '/primer-automation-logs')) {
            mkdir($upload_dir . '/primer-automation-logs');
        }
        $csv_dir_file = $upload_dir . '/primer-automation-logs/'.$filename;

        $fh = @fopen( "$csv_dir_file", 'w' );
        fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
        fputcsv( $fh, $header_row );

        foreach ( $data_rows as $data_row ) {
            fputcsv( $fh, $data_row );
        }

        fclose( $fh );

        // Get Notification Emails
        $automation_options = get_option('primer_automation');
        $activation_automation = $automation_options['activation_automation'];

        $primer_smtp = PrimerSMTP::get_instance();

        if (!empty($automation_options['email_subject'])) {
            $primer_smtp_subject = $automation_options['email_subject'];
        } else {
            $primer_smtp_subject = __('Test email subject', 'primer');
        }

        $primer_send_success_log = '';
        $primer_send_fail_log = '';
        if (!empty($automation_options['send_successful_log'])) {
            $primer_send_success_log = $automation_options['send_successful_log'];
        }
        if (!empty($automation_options['send_failed_log'])) {
            $primer_send_fail_log = $automation_options['send_failed_log'];
        }
        if (!empty($activation_automation)) {
            if (!empty($primer_send_success_log) || !empty($primer_send_fail_log)) {
                if (($primer_send_success_log == 'on') || ($primer_send_fail_log == 'on')) {
                    $automation_admin_emails = $automation_options['admin_email'];
                    if (!empty($automation_admin_emails)) {
                        $admin_emails = explode(',', $automation_admin_emails);
                        foreach ( $admin_emails as $admin_email ) {
                            $emails[] = trim( sanitize_email($admin_email) );
                        }
                        if (!empty($emails) && !empty($log_ids)) {
                            $primer_smtp_options = get_option('primer_emails');
                            foreach ( $emails as $to_admin_email ) {
                                if ( ( file_exists($csv_dir_file) ) && ( filesize($csv_dir_file) > 4 ) ) {
                                    // The CSV file is not empty
                                    $primer_smtp_type = $primer_smtp_options['smtp_type'];
                                    if($primer_smtp_type == 'wordpress_default'){
                                        $mailResultSMTP = wp_mail($to_admin_email, $primer_smtp_subject, 'export file','', $csv_dir_file);
                                    }else{
                                        if (!empty($primer_smtp_options['email_from_name'])) {
                                            $from_name_email = $primer_smtp_options['email_from_name'];
                                        } else {
                                            $from_name_email = '';
                                        }
                                        $mailResultSMTP = $primer_smtp->primer_mail_sender($to_admin_email, $from_name_email, $primer_smtp_subject, 'automation log', $csv_dir_file);
                                    }
                                }
                            }
                        }
                    }

                }
            }
        }
        die();
    }
	function ajax_fire_cron() {
		echo "OK";
		$this->convert_order_to_invoice();
		wp_die();
	}
}



new PrimerCron();
