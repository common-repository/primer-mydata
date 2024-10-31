<?php
require_once(ABSPATH . 'wp-load.php');
require_once PRIMER_PATH . 'includes/vendor/dompdf/autoload.inc.php';
use PrimerDompdf\Dompdf;

class Create_json
{

    public function create_invoice(&$user_id, &$order_id, &$total_vat_number, &$mydata_options, &$primer_license_data,
                                   &$total, &$series, &$serie, &$number, &$currency, &$currency_symbol, &$user_data, &$insert_taxonomy,
                                   &$classificationCategory, &$classificationCategory_en, &$response_data, &$receipt_log_value, &$receipt_log_value_array,
                                   &$receipt_log_id, &$invoice_term, $gr_time,$invoice_time, &$order_total_price, &$order_invoice_type,
                                   &$order_vatNumber, &$user_order_email, &$order_country, &$user_full_name, &$primer_smtp, &$log_ids, $callingFunction, &$invoiceType,
                                   &$send_api_invoice, &$classificationType, $is_credit = false, $receipt_id = '')
    {
        $invoice_data = array(
            "invoice" => array(),
        );
        $order = new WC_Order($order_id);
        $id_of_order = $order->get_id();
        $check_timeout = get_post_meta($id_of_order, 'failed_48', true);
        $check_transmission_failure_48 = get_post_meta($id_of_order, 'transmission_failure_check', true);
        /*   if(($mydata_options['timeout_check_48'] == 1 && $check_timeout != 'yes') || ($mydata_options['timeout_check_48'] == 1 && $check_transmission_failure_48 != 1)){
               $receipt_log_value .= __('Could not connect to aade for 48 hours. Please check your settings or contact primer software.', 'primer');
               $response_data .= '<div class="notice notice-error"><p>'.__('Could not connect to aade for 48 hours. Please check your settings or contact primer software.', 'primer').'</p></div>';
               $receipt_log_value_array[] = __('Could not connect to aade for 48 hours. Please check your settings or contact primer software.','primer');
               break;
           } */
        $order_country = $order->get_billing_country();
        if(empty($order_country)){
            $order_country = 'GR';
        }
        $order_paid_date = null;
        if (!empty($order->get_date_paid())) {
            $order_paid_date = date('F j, Y', $order->get_date_paid()->getTimestamp());
        } else {
            $order_paid_date = date('F j, Y', $order->get_date_created()->getTimestamp());
        }
        if($invoice_time == null || $invoice_time == '') {
            $issue_date = new DateTime("now", new DateTimeZone("Europe/Athens"));
            $invoice_time = $issue_date->format('H:i');
        }
        $order_total_price = $order->get_total();
        $user_id = $order->get_user_id();
        $vies = get_post_meta($order_id, 'is_vat_exempt', true);
        if (!empty($order->get_billing_first_name())) {
            $user_first_name = $order->get_billing_first_name();
            $user_last_name = $order->get_billing_last_name();
            $user_full_name = $user_first_name . ' ' . $user_last_name;
        } else if (!empty($order->get_billing_company())) {
            $user_full_name = $order->get_billing_company();
        } else {
            $user_full_name = 'RETAIL CLIENT';
        }

        $country_out_of_eu = false;

        if( check_zone_country($order_country) == false ) {
            $country_out_of_eu = true;
        }
        $licenses = get_option('primer_licenses');
        $user_data = $user_full_name ? $user_full_name : 'Customer';
        $order_invoice_type = get_post_meta($id_of_order, '_billing_invoice_type', true);
        $insert_taxonomy = 'receipt_status';
        $invoice_term = '';
        $invoice_data['invoice'][0]['issuer']['vatNumber'] = "$total_vat_number";
        $invoice_data['invoice'][0]['issuer']['country'] = 'GR';
        if ($callingFunction == 'convert_select_orders' || $callingFunction == 'convert_order_to_invoice' ) {
            if (isset($licenses['currentBranchID']) && $licenses['currentBranchID'] != "0") {
                $invoice_data['invoice'][0]['issuer']['branch'] = $licenses['currentBranchID'];
            } else {
                // If currentBranchID is not stored or is 0, set it to "0"
                $invoice_data['invoice'][0]['issuer']['branch'] = "0";
            }
        } elseif ($callingFunction == 'primer_cancel_invoice') {
            $order_id_from_receipt = get_post_meta($order_id, 'order_id_from_receipt', true);
            $branchIDfromReceipt = get_post_meta($order_id_from_receipt, 'branchID', true);
            if ($branchIDfromReceipt == null) {
                $invoice_data['invoice'][0]['issuer']['branch'] = "0";
            } else {
                $invoice_data['invoice'][0]['issuer']['branch'] = $branchIDfromReceipt;
            }
        } elseif ( $callingFunction == 'convert_order_to_invoice_failed' ) {
            $order_id_from_receipt = get_post_meta($order_id, 'order_id_from_receipt', true);
            $branchIDfromReceipt = get_post_meta($order_id_from_receipt, 'branchID', true);
            if ($branchIDfromReceipt == null) {
                $invoice_data['invoice'][0]['issuer']['branch'] = "0";
            } else {
                $invoice_data['invoice'][0]['issuer']['branch'] = $branchIDfromReceipt;
            }
        } elseif ( $callingFunction == 'primer_cancel_invoice_cron' ) {
            $order_id_from_receipt = get_post_meta($order_id, 'order_id_from_receipt', true);
            $branchIDfromReceipt = get_post_meta($order_id_from_receipt, 'branchID', true);
            if ($branchIDfromReceipt == null) {
                $invoice_data['invoice'][0]['issuer']['branch'] = "0";
            } else {
                $invoice_data['invoice'][0]['issuer']['branch'] = $branchIDfromReceipt;
            }
        }
        if($number == null || $number == '') {
            $number = 0;
        }
        $series_array = array();
        $series_array['EMPTY'] = __('EMPTY', 'primer');
        $series_array['A'] = 'A';
        $series_array['Β'] = 'B';
        $series_array['Γ'] = 'C';
        $series_array['Δ'] = 'D';
        $series_array['Ε'] = 'E';
        $series_array['Ζ'] = 'Z';
        $series_array['Η'] = 'H';
        $series_array['Θ'] = 'Q';
        $series_array['Ι'] = 'I';
        $series_array['Κ'] = 'K';
        $series_array['Λ'] = 'L';
        $series_array['Μ'] = 'M';
        $series_array['Ν'] = 'N';
        $series_array['Ξ'] = 'J';
        $series_array['Ο'] = 'O';
        $series_array['Π'] = 'P';
        $series_array['Ρ'] = 'R';
        $series_array['Σ'] = 'S';
        $series_array['Τ'] = 'T';
        $series_array['Υ'] = 'Y';
        $series_array['Φ'] = 'F';
        $series_array['Χ'] = 'X';
        $series_array['Ψ'] = 'W';
        $series_array['Ω'] = 'V';

        //$licenses = get_option('primer_licenses');
        if (!$licenses['productKind'] || $licenses['productKind'] == 'other') {
            $response_data .= '<div class="notice notice-error"><div><h3>' . __('Product Kind is not configured.PLease try re-activating your product.', 'primer') . '</h3><br><br><br><br><br></div>';
            $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
            $receipt_log_value_array[] = __('Product Kind is not configured.PLease try re-activating your product.', 'primer');
            update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value);
            //PRWTO BREAK
            return "break";
        }
        if($is_credit) {
            if (($order_invoice_type == 'receipt' || empty($order_invoice_type))) {
                $invoice_term = 'credit-receipt';
                if($licenses['productKind'] == 'goods'){
                    $invoiceType = '11.4';
                    $classificationType = 'E3_561_003';
                    $classificationCategory = 'category1_1';
                } else {
                    $invoiceType = '11.4';
                    $classificationType = 'E3_561_003';
                    $classificationCategory = 'category1_3';
                }
                if ($number == null || $number == '') {
                    if ($mydata_options['mydata_api'] == 'production_api') {
                        $serie = array_search($mydata_options['credit_receipt_series'], $series_array);
                        $series = $mydata_options['credit_receipt_series'];
                        $number = $mydata_options['credit_receipt_' . $series . ''];
                    } else {
                        $serie = array_search($mydata_options['credit_receipt_test_api_series'], $series_array);
                        $series = $mydata_options['credit_receipt_test_api_series'];
                        $number = $mydata_options['credit_receipt_' . $series . '_test_api'];
                    }
                }
            }
            if (($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice') && $order_country == 'GR') {
                $invoice_term = 'credit-invoice';
                if($licenses['productKind'] == 'goods'){
                    $invoiceType = '5.1';
                    $classificationType = 'E3_561_001';
                    $classificationCategory = 'category1_1';
                }else{
                    $invoiceType = '5.1';
                    $classificationType = 'E3_561_001';
                    $classificationCategory = 'category1_3';
                }
                if ($number == null || $number == '') {
                    if ($mydata_options['mydata_api'] == 'production_api') {
                        $serie = array_search($mydata_options['credit_invoice_series'], $series_array);
                        $series = $mydata_options['credit_invoice_series'];
                        $number = $mydata_options['credit_invoice_' . $series . ''];
                    } else {
                        $serie = array_search($mydata_options['credit_invoice_test_api_series'], $series_array);
                        $series = $mydata_options['credit_invoice_test_api_series'];
                        $number = $mydata_options['credit_invoice_' . $series . '_test_api'];
                    }
                }
            }
            if (($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice') && $order_country !== 'GR' && check_zone_country($order_country) == true) {
                $invoice_term = 'credit-invoice';
                if($licenses['productKind'] == 'goods'){
                    $invoiceType = '5.1';
                    $classificationType = 'E3_561_005';
                    $classificationCategory = 'category1_1';
                }else{
                    $invoiceType = '5.1';
                    $classificationType = 'E3_561_005';
                    $classificationCategory = 'category1_3';
                }
                if ($number == null || $number == '') {
                    if ($mydata_options['mydata_api'] == 'production_api') {
                        $serie = array_search($mydata_options['credit_invoice_series'], $series_array);
                        $series = $mydata_options['credit_invoice_series'];
                        $number = $mydata_options['credit_invoice_' . $series . ''];
                    } else {
                        $serie = array_search($mydata_options['credit_invoice_test_api_series'], $series_array);
                        $series = $mydata_options['credit_invoice_test_api_series'];
                        $number = $mydata_options['credit_invoice_' . $series . '_test_api'];
                    }
                }
            }

            if (($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice') && check_zone_country($order_country) == false) {
                $invoice_term = 'credit-invoice';
                if($licenses['productKind'] == 'goods'){
                    $invoiceType = '5.1';
                    $classificationType = 'E3_561_006';
                    $classificationCategory = 'category1_1';
                }else{
                    $invoiceType = '5.1';
                    $classificationType = 'E3_561_006';
                    $classificationCategory = 'category1_3';
                }
                if ($number == null || $number == '') {
                    if ($mydata_options['mydata_api'] == 'production_api') {
                        $serie = array_search($mydata_options['credit_invoice_series'], $series_array);
                        $series = $mydata_options['credit_invoice_series'];
                        $number = $mydata_options['credit_invoice_' . $series . ''];
                    } else {
                        $serie = array_search($mydata_options['credit_invoice_test_api_series'], $series_array);
                        $series = $mydata_options['credit_invoice_test_api_series'];
                        $number = $mydata_options['credit_invoice_' . $series . '_test_api'];
                    }
                }
            }
        } else {
            if ($order_invoice_type == 'receipt' || empty($order_invoice_type)) {
                $invoice_term = 'greek_receipt';
                if ($licenses['productKind'] == 'goods') {
                    $invoiceType = '11.1';
                    $classificationType = 'E3_561_003';
                    $classificationCategory = 'category1_1';
                } else {
                    $invoiceType = '11.2';
                    $classificationType = 'E3_561_003';
                    $classificationCategory = 'category1_3';
                }
                if ($number == null || $number == '') {
                    if ($mydata_options['mydata_api'] == 'production_api') {
                        $serie = array_search($mydata_options['invoice_numbering_gr_series'], $series_array);
                        $series = $mydata_options['invoice_numbering_gr_series'];
                        $number = $mydata_options['invoice_numbering_gr_' . $series . ''];
                    } else {
                        $serie = array_search($mydata_options['invoice_numbering_gr_test_api_series'], $series_array);
                        $series = $mydata_options['invoice_numbering_gr_test_api_series'];
                        $number = $mydata_options['invoice_numbering_gr_' . $series . '_test_api'];
                    }
                }
            } else if (($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice') && $order_country == 'GR') {
                $invoice_term = 'greek_invoice';
                if ($licenses['productKind'] == 'goods') {
                    $invoiceType = '1.1';
                    $classificationType = 'E3_561_001';
                    $classificationCategory = 'category1_1';
                } else {
                    $invoiceType = '2.1';
                    $classificationType = 'E3_561_001';
                    $classificationCategory = 'category1_3';
                }
                if ($number == null || $number == '') {
                    if ($mydata_options['mydata_api'] == 'production_api') {
                        $serie = array_search($mydata_options['invoice_numbering_gi_series'], $series_array);
                        $series = $mydata_options['invoice_numbering_gi_series'];
                        $number = $mydata_options['invoice_numbering_gi_' . $series . ''];
                    } else {
                        $serie = array_search($mydata_options['invoice_numbering_gi_test_api_series'], $series_array);
                        $series = $mydata_options['invoice_numbering_gi_test_api_series'];
                        $number = $mydata_options['invoice_numbering_gi_' . $series . '_test_api'];
                    }
                }
            } else if (($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice') && $order_country !== 'GR' && check_zone_country($order_country) == true) {
                $invoice_term = 'english_invoice';
                if ($licenses['productKind'] == 'goods') {
                    $invoiceType = '1.2';
                    $classificationType = 'E3_561_005';
                    $classificationCategory = 'category1_1';
                } else {
                    $invoiceType = '2.2';
                    $classificationType = 'E3_561_005';
                    $classificationCategory = 'category1_3';
                }
                if ($number == null || $number == '') {
                    if ($mydata_options['mydata_api'] == 'production_api') {
                        $serie = array_search($mydata_options['invoice_numbering_within_series'], $series_array);
                        $series = $mydata_options['invoice_numbering_within_series'];
                        $number = $mydata_options['invoice_numbering_within_' . $series . ''];
                    } else {
                        $serie = array_search($mydata_options['invoice_numbering_within_test_api_series'], $series_array);
                        $series = $mydata_options['invoice_numbering_within_test_api_series'];
                        $number = $mydata_options['invoice_numbering_within_' . $series . '_test_api'];
                    }
                }
            } else if (($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice') && check_zone_country($order_country) == false) {
                $invoice_term = 'english_invoice';
                if ($licenses['productKind'] == 'goods') {
                    $invoiceType = '1.3';
                    $classificationType = 'E3_561_006';
                    $classificationCategory = 'category1_1';
                } else {
                    $invoiceType = '2.3';
                    $classificationType = 'E3_561_006';
                    $classificationCategory = 'category1_3';
                }
                if ($number == null || $number == '') {
                    if ($mydata_options['mydata_api'] == 'production_api') {
                        $serie = array_search($mydata_options['invoice_numbering_outside_series'], $series_array);
                        $series = $mydata_options['invoice_numbering_outside_series'];
                        $number = $mydata_options['invoice_numbering_outside_' . $series . ''];
                    } else {
                        $serie = array_search($mydata_options['invoice_numbering_outside_test_api_series'], $series_array);
                        $series = $mydata_options['invoice_numbering_outside_test_api_series'];
                        $number = $mydata_options['invoice_numbering_outside_' . $series . '_test_api'];
                    }
                }
            } else {
                $invoice_term = 'greek_receipt';
                if ($licenses['productKind'] == 'goods') {
                    $invoiceType = '11.1';
                    $classificationType = 'E3_561_003';
                    $classificationCategory = 'category1_1';
                } else {
                    $invoiceType = '11.2';
                    $classificationType = 'E3_561_003';
                    $classificationCategory = 'category1_3';
                }
                if ($number == null || $number == '') {
                    if ($mydata_options['mydata_api'] == 'production_api') {
                        $serie = array_search($mydata_options['invoice_numbering_gr_series'], $series_array);
                        $series = $mydata_options['invoice_numbering_gr_series'];
                        $number = $mydata_options['invoice_numbering_gr_' . $series . ''];
                    } else {
                        $serie = array_search($mydata_options['invoice_numbering_gr_test_api_series'], $series_array);
                        $series = $mydata_options['invoice_numbering_gr_test_api_series'];
                        $number = $mydata_options['invoice_numbering_gr_' . $series . '_test_api'];
                    }
                }
            }
        }
        $user_data = $user_full_name ? $user_full_name : 'Customer';
        $user_order_email = get_post_meta($order_id, '_billing_email', true); //$order->get_billing_email();
        $currency = $order->get_currency();
        $currency_symbol = get_woocommerce_currency_symbol($currency);
        $primer_smtp = PrimerSMTP::get_instance();
        $total_net_value = 0;
        $total_vat_value = 0;
        $outside_vat = '';
        $order_status = $order->get_status();
        if (!empty($mydata_options['primer_rounding_calculation']) && array_key_exists('primer_rounding_calculation', $mydata_options) && $mydata_options['primer_rounding_calculation'] == 'on') {
            $order->update_status('pending', '<%Your message%>', TRUE);
            $order->calculate_totals();
            $order->update_status($order_status, '<%Your message%>', TRUE);
        }
        if ($callingFunction == "convert_order_to_invoice") {
            update_post_meta($receipt_log_id, 'receipt_log_automation_order_id', $id_of_order);
            if (!empty($receipt_log_id)) {
                update_post_meta($id_of_order, 'log_id_for_order', $receipt_log_id);
                update_post_meta($receipt_log_id, 'receipt_log_automation_order_date', $order_paid_date);
            }
        } else {
            update_post_meta($receipt_log_id, 'receipt_log_order_id', $id_of_order);
            if (!empty($receipt_log_id)) {
                update_post_meta($id_of_order, 'log_id_for_order', $receipt_log_id);
                update_post_meta($receipt_log_id, 'receipt_log_order_date', $order_paid_date);
            }
        }
        $log_ids[] = $receipt_log_id;
        $total_tax = $order->get_total_tax();
        $get_coupon = $order->get_coupon_codes();
        $shipping_total = $order->get_shipping_total();
        $shipping_tax = $order->get_shipping_tax();
        $invoice_data['invoice'][0]['invoiceHeader']['series'] = $serie == 'EMPTY' ? '0' : $serie;
        $invoice_data['invoice'][0]['invoiceHeader']['aa'] = $number;
        $invoice_data['invoice'][0]['invoiceHeader']['invoiceType'] = $invoiceType;
        $invoice_data['invoice'][0]['invoiceHeader']['currency'] = $currency;
        $invoice_data['invoice'][0]['invoiceHeader']['issueDate'] = $gr_time;
        //$branch = $invoice_data['invoice'][0]['issuer']['branch'];
        if ( $mydata_options['mydata_api'] == 'production_api' && $callingFunction != 'convert_order_to_invoice_failed' && $callingFunction !="primer_cancel_invoice_cron") {
            $arithmos = $number;
            $branch = $invoice_data['invoice'][0]['issuer']['branch'];
            $gramma = $serie == 'EMPTY' ? '0' : $serie;
            $typos = $invoiceType;
            if ( $typos == '11.1' || $typos == '11.2' ) {
                $typos = 'greek_receipt';
            }
            elseif ( $typos == '1.1' || $typos == '2.1') {
                $typos = 'greek_invoice';
            }
            elseif ( $typos == '1.2' || $typos == '2.2' || $typos == '1.3' || $typos == '2.3') {
                $typos = 'english_invoice';
            }
            elseif ( $typos == '11.4' ) {
                $typos = 'credit-receipt';
            }
            elseif ( $typos == '5.1' ) {
                $typos = 'credit-invoice';
            }
            $identifier = $gramma . "_" . $arithmos . "_" . $typos . "_" . $branch;
            global $wpdb;
            $query = $wpdb->prepare("
                SELECT post_id
                FROM {$wpdb->postmeta}
                WHERE meta_key = 'numbering_identifier' AND meta_value = %s
                LIMIT 1
            ", $identifier);
            $results = $wpdb->get_results($query);
            if ($results != null) {
                $this->numbering($order_invoice_type,$order_country,$mydata_options,$series,$is_credit);
                update_option('primer_mydata', $mydata_options);

                return "rerun";
            }
        }

        if ($currency != 'EUR' && in_array(18, $primer_license_data['wpModules'])) {
            $req_url = 'https://api.freecurrencyapi.com/v1/latest?apikey=fca_live_utrfbGSzAGB1NZw4aDkHhUWDDBQ83JBr7tLjaCWM&base_currency=EUR&currencies='.$currency;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $req_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response_json = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpcode >= 200 && $httpcode < 300) {
                $response = json_decode($response_json);
                if ($response !== null && isset($response->data->$currency)) {
                    $exchange_rate = $response->data->$currency;
                    $exchange_rate_formatted = number_format((float)$exchange_rate, 5, '.', '');
                    $invoice_data['invoice'][0]['invoiceHeader']['exchangeRate'] = $exchange_rate_formatted;
                }
            }
        } elseif ($currency != 'EUR' && !(in_array(18, $primer_license_data['wpModules']))) {
            $send_api_invoice = false;
            $receipt_log_value .= __('Only euro is accepted.', 'primer');
            $receipt_log_value_array[] = __('Only euro is accepted.', 'primer');
            $response_data .= '<div class="primer_popup popup_error"><div><h3>' . __('Only euro is accepted.', 'primer') . '</h3><br><br><br><br><br></div>';
            $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
        }
        //for failed invoices
        $transmission_failure = get_post_meta($id_of_order, 'transmission_failure_check', true);
        if ($transmission_failure == 1) {
            $order_date_failed = get_post_meta($id_of_order, 'order_date_failed', true);
            $invoice_data['invoice'][0]['transmissionFailure'] = '1';
            $invoice_data['invoice'][0]['invoiceHeader']['issueDate'] = "$order_date_failed";
        }
        if ($transmission_failure == 3) {
            $order_date_failed = get_post_meta($id_of_order, 'order_date_failed', true);
            $invoice_data['invoice'][0]['transmissionFailure'] = '2';
            $invoice_data['invoice'][0]['invoiceHeader']['issueDate'] = "$order_date_failed";
        }
        if (($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice') && $is_credit){
            $correlated_mark = get_post_meta($receipt_id,'response_invoice_mark',true);
            $invoice_data['invoice'][0]['invoiceHeader']['correlatedInvoices'] = [$correlated_mark];
        }
        $sum = 0;
        $item_count = 0;
        $tax_classes = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
        if (!in_array('', $tax_classes)) { // Make sure "Standard rate" (empty class name) is present.
            array_unshift($tax_classes, '');
        }
        $inside_tax_rate = '';
        $general_settings = get_option('primer_generals');
        $zero_total_value = $order->get_total();
        if ($general_settings['accept_zero_value_orders'] == 'on' && $zero_total_value == 0) {
            $invoice_data['invoice'][0]['invoiceDetails'][0]['name'] = 'ΠΡΟΙΟΝ';
            $invoice_data['invoice'][0]['invoiceDetails'][0]['code'] = '1';
            $invoice_data['invoice'][0]['invoiceDetails'][0]['lineNumber'] = 1;
            $invoice_data['invoice'][0]['invoiceDetails'][0]['netValue'] = "0.01";
            $invoice_data['invoice'][0]['invoiceDetails'][0]['vatCategory'] = 1;
            $invoice_data['invoice'][0]['invoiceDetails'][0]['vatAmount'] = "0.01";
            $invoice_data['invoice'][0]['paymentMethods']['paymentMethodDetails'][0]['type'] = 1;
            $invoice_data['invoice'][0]['paymentMethods']['paymentMethodDetails'][0]['amount'] = "0.02";
            $invoice_data['invoice'][0]['invoiceSummary']['totalNetValue'] = "0.01";
            $invoice_data['invoice'][0]['invoiceSummary']['totalVatAmount'] = "0.01";
            $invoice_data['invoice'][0]['invoiceSummary']['totalWithheldAmount'] = 0;
            $invoice_data['invoice'][0]['invoiceSummary']['totalFeesAmount'] = 0;
            $invoice_data['invoice'][0]['invoiceSummary']['totalStampDutyAmount'] = 0;
            $invoice_data['invoice'][0]['invoiceSummary']['totalDeductionsAmount'] = 0;
            $invoice_data['invoice'][0]['invoiceSummary']['totalOtherTaxesAmount'] = 0;
            $invoice_data['invoice'][0]['invoiceSummary']['totalGrossValue'] = "0.02";
            $invoice_data['templateId'] = '1';
            return ($invoice_data);
        } elseif ($general_settings['accept_zero_value_orders'] != 'on' && $zero_total_value == 0) {
            $receipt_log_value .= __('AADE does not accept zero value orders please enable "Accept zero total value orders" option in general settings if you want to issue this order.', 'primer');
            $receipt_log_value_array[] = __('AADE does not accept zero value orders please enable "Accept zero total value orders" option in general settings if you want to issue this order.', 'primer');
            $response_data .= '<div class="primer_popup popup_error"><div><h3>' . __('AADE does not accept zero value orders please enable "Accept zero total value orders" option in general settings if you want to issue this order.', 'primer') . '</h3><br><br><br><br><br></div>';
            $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
            if ($callingFunction == "convert_order_to_invoice") {
                update_post_meta($receipt_log_id, 'receipt_log_automation_error', $receipt_log_value);
            } else {
                update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value);
            }
            update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value);
            return "continue"; //EDW EINAI DEYTERO CONTINUE
        } else {
            ///
            $discount_difference = $order->get_total() - ($order->get_shipping_total() + $order->get_total_tax());
            $discount_percentage = 100 - (($discount_difference / $order->get_subtotal()) * 100);
            $total_sub_shipping = 0;
            foreach ($order->get_items() as $item_id => $item_data) {
                $quantity = $item_data->get_quantity();
                $product_name = $item_data->get_name();
                $product_code = $item_data->get_id();
                $sum += $quantity;
                if (!empty($get_coupon) || $order->get_total_discount() > 0) {
                    $subtotal_order_payment = $item_data->get_total();
                } else {
                    $subtotal_order_payment = $item_data->get_subtotal();
                }
                ///
                if ($discount_percentage > 0 && empty($get_coupon)) {
                    $subtotal_order_payment = number_format($subtotal_order_payment - ($subtotal_order_payment * ($discount_percentage / 100)), 2, '.', '');
                }
                if ($subtotal_order_payment == 0) {
                    continue;
                }

                // $subtotal_order_payment = $item_data->get_total();
                $product_tax_class = $item_data->get_tax_class();
                $inside_tax_rate = "";
                $taxes = $item_data->get_taxes();
                foreach ($taxes['total'] as $tax_rate_id => $tax_amount) {
                    if ($tax_amount > 0) {
                        $tax_rate = WC_Tax::_get_tax_rate($tax_rate_id);
                        $inside_tax_rate = strval($tax_rate['tax_rate']);
                        break;
                    }
                }
                $outside_vat = '';
                switch ($inside_tax_rate) {
                    case "24":
                        $vatCategory = 1;
                        break;
                    case "17":
                        $vatCategory = 4;
                        break;
                    case "13":
                        $vatCategory = 2;
                        break;
                    case "9":
                        $vatCategory = 5;
                        break;
                    case "6":
                        $vatCategory = 3;
                        break;
                    case "4":
                        $vatCategory = 6;
                        break;
                    case "0":
                        $vatCategory = 7;
                        break;
                    default:
                        $vatCategory = 7;
                        $outside_vat = 'yes';
                }
                if (!empty($get_coupon) || $order->get_total_discount() > 0) {
                    $subtotal_item_tax = $item_data->get_total_tax();
                } else {
                    $subtotal_item_tax = $item_data->get_subtotal_tax();

                }
                ///
                if ($discount_percentage > 0 && empty($get_coupon)) {
                    $subtotal_item_tax = $item_data->get_subtotal_tax() - ($item_data->get_subtotal_tax() * ($discount_percentage / 100));
                    //$subtotal_item_tax = $subtotal_item_tax - ($subtotal_item_tax * ($discount_percentage / 100));
                    //$subtotal_item_tax = round($subtotal_item_tax, 2);
                }
                $tax_classes = WC_Tax::get_tax_classes();
                $classes_names = array();
                $classes_names[''] = __('Standard rate', 'woocommerce');
                if (!empty($tax_classes)) {
                    foreach ($tax_classes as $class) {
                        $classes_names[sanitize_title($class)] = esc_html($class);
                    }
                }
                $order_vat_exemption_category = '';
                $order_vat_exemption_name = $classes_names[$product_tax_class];
                $order_vat_exemption_name = str_replace(' ', '_', $order_vat_exemption_name);
                if (($inside_tax_rate == "0" || empty($inside_tax_rate))  && !empty($mydata_options['' . $order_vat_exemption_name . '']) && in_array(16, $primer_license_data['wpModules'])) {
                    $order_vat_exemption_category = $mydata_options['' . $order_vat_exemption_name . ''];
                }
                if ($inside_tax_rate == "0" && !(in_array(16, $primer_license_data['wpModules'])) && ($order_country == 'GR' || ($order_invoice_type == 'receipt' && $order_country == 'GR'))) {
                    $receipt_log_value .= __('The order has 0% VAT included.The edition that you currently have does not support zero vat orders.Please choose another order to convert or go to www.primer.gr/shop and choose another edition.', 'primer');
                    $receipt_log_value_array[] = __('The order has 0% VAT included.The edition that you currently have does not support zero vat orders.Please choose another order to convert or go to www.primer.gr/shop and choose another edition.', 'primer');
                    update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value);
                    $response_data .= '<div class="primer_popup popup_error"><div><h3>' . __('The order has 0% VAT included.The edition that you currently have does not support zero vat orders.Please choose another order to convert or go to www.primer.gr/shop and choose another edition.', 'primer') . '</h3><br><br><br><br><br></div>';
                    $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
                    if ($callingFunction == "convert_order_to_invoice") {
                        update_post_meta($receipt_log_id, 'receipt_log_automation_error', $receipt_log_value);
                    } else {
                        update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value);
                    }
                    return "break"; //PRWTO BREAK 2
                }
                if ($inside_tax_rate == "0" && ($order_country == 'GR' || ($order_invoice_type == 'receipt' && $order_country == 'GR')) && (empty($mydata_options['' . $order_vat_exemption_name . '']) || $mydata_options['' . $order_vat_exemption_name . ''] == 0) && in_array(16, $primer_license_data['wpModules'])) {
                    $receipt_log_value .= __('The order has 0% VAT included.Please go to MyData settings and configure a vat exemption category for the specific tax class that the product is attached to it.', 'primer');
                    $receipt_log_value_array[] = __('The order has 0% VAT included.Please go to MyData settings and configure a vat exemption category for the specific tax class that the product is attached to it.', 'primer');
                    update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value);

                    $response_data .= '<div class="primer_popup popup_error"><div><h3>' . __('The order has 0% VAT included.Please go to MyData settings and configure a vat exemption category for the specific tax class that the product is attached to it.', 'primer') . '</h3><br><br><br><br><br></div>';
                    $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
                    if ($callingFunction == "convert_order_to_invoice") {
                        update_post_meta($receipt_log_id, 'receipt_log_automation_error', $receipt_log_value);
                    } else {
                        update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value);
                    }
                    return "break"; // DEYTERO BREAK 2
                }
                if ($order_country != 'GR' && ($order_vat_exemption_category == '' || empty($order_vat_exemption_category) || $order_vat_exemption_category == 0)) {
                    $order_vat_exemption_category = 27;
                }
                //make all amounts 2 decimals
                $subtotal_item_tax = number_format((float)$subtotal_item_tax, 2, '.', '');
                $subtotal_order_payment = number_format((float)$subtotal_order_payment, 2, '.', '');
                if ($outside_vat != 'yes') {
                    $total_net_value += $subtotal_order_payment;
                    $total_vat_value += $subtotal_item_tax;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['name'] = $product_name;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['code'] = $product_code;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['lineNumber'] = $item_count + 1;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['netValue'] = $subtotal_order_payment;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatCategory'] = (int)$vatCategory;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatAmount'] = "$subtotal_item_tax";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['classificationCategory'] = $classificationCategory;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['classificationType'] = $classificationType;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['amount'] = $subtotal_order_payment;
                    $string_id = $item_count + 1;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['id'] = (string)$string_id;
                    if ($licenses['productKind'] == 'goods') {
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['quantity'] = (int)$quantity;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['measurementUnit'] = 1;
                    }
                    if ($subtotal_item_tax == "0.00") {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = $order_vat_exemption_category;
                    }
                } else {
                    $total_net_value += $subtotal_order_payment;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['name'] = $product_name;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['code'] = $product_code;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['lineNumber'] = $item_count + 1;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['netValue'] = $subtotal_order_payment;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatCategory'] = 7;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatAmount'] = "0.00";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['classificationCategory'] = $classificationCategory;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['classificationType'] = $classificationType;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['amount'] = $subtotal_order_payment;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 27;

                    if ($licenses['productKind'] == 'goods') {
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['quantity'] = (int)$quantity;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['measurementUnit'] = 1;
                    }
                    //check if there is actually a vat amount to create second line
                    if ($subtotal_item_tax > 0) {
                        //increment count, so it does not overwrite the next element of array
                        $total_net_value += $subtotal_item_tax;
                        $total_vat_value += $subtotal_item_tax;
                        $item_count++;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['name'] = "ΦΠΑ $inside_tax_rate %";
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['code'] = 'ΦΠΑ'.($item_count);
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['lineNumber'] = $item_count + 1;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['netValue'] = "$subtotal_item_tax";
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatCategory'] = 7;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatAmount'] = "0.00";
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['classificationCategory'] = $classificationCategory_en;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['amount'] = "$subtotal_item_tax";
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 27;
                        if ($licenses['productKind'] == 'goods') {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['quantity'] = 1;
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['measurementUnit'] = 1;
                        }
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 27;
                    } else {
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 27;
                    }
                    if ($vies == 'yes') {
                        if ($licenses['productKind'] == 'goods') {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 8;
                        } else {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                        }
                        if ($country_out_of_eu == true) {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                        }
                    }
                }
                $item_count++;
            }
            if (!empty($get_coupon) || $order->get_total_discount() > 0) {
                $subtotal = ($order->get_subtotal() - $order->get_total_discount());
            } else {
                $subtotal = $order->get_subtotal();
            }
            ///
            if ($discount_percentage > 0 && empty($get_coupon)) {
                $discount_amount = round(($order->get_subtotal() * $discount_percentage) / 100, 2);
                $subtotal = $order->get_subtotal() - $discount_amount;
            }
            // implementation of shipping in json
            if ($shipping_total > 0) {
                $shipping_tax_rate = '';
                foreach ($order->get_items('tax') as $item_id => $item_tax) {
                    $tax_data = $item_tax->get_data();
                    if (count($order->get_items('tax')) > 1) {
                        if ($tax_data['shipping_tax_total'] == 0) {
                            continue;
                        }
                    }
                    $shipping_tax_rate = $tax_data['rate_percent'];
                }
                $vatCategory_shipping = '';
                switch ($shipping_tax_rate) {
                    case "24":
                        $vatCategory_shipping = 1;
                        break;
                    case "17":
                        $vatCategory_shipping = 4;
                        break;
                    case "13":
                        $vatCategory_shipping = 2;
                        break;
                    case "9":
                        $vatCategory_shipping = 5;
                        break;
                    case "6":
                        $vatCategory_shipping = 3;
                        break;
                    case "4":
                        $vatCategory_shipping = 6;
                        break;
                    case "0":
                        $vatCategory_shipping = 7;
                        break;
                    default:
                        $vatCategory_shipping = 7;
                        $outside_vat = 'yes';
                }
                $shipping_total = number_format((float)$shipping_total, 2, '.', '');
                $shipping_tax = number_format((float)$shipping_tax, 2, '.', '');
                if ($outside_vat == 'yes') {
                    $total_net_value += $shipping_total;
                    $vatCategory_shipping = 7;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['name'] = "ΜΕΤΑΦΟΡΙΚΑ";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['code'] = "EA";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['lineNumber'] = $item_count + 1;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['netValue'] = $shipping_total;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatCategory'] = $vatCategory_shipping;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatAmount'] = "0.00";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['classificationCategory'] = "category1_5";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['classificationType'] = "E3_562";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['amount'] = $shipping_total;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 27;
                    if ($vies == 'yes') {
                        if ($licenses['productKind'] == 'goods') {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 8;
                        } else {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                        }
                        if ( $country_out_of_eu == true ) {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                        }
                    }
                    if ($licenses['productKind'] == 'goods') {
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['quantity'] = 1;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['measurementUnit'] = 1;
                    }
                    $total_sub_shipping = $subtotal;
                    $subtotal = $subtotal + $shipping_total;
                    if ($shipping_tax > 0) {
                        $total_net_value += $shipping_tax;
                        $total_vat_value += $shipping_tax;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['name'] = "ΜΕΤΑΦΟΡΙΚΑ ΦΠΑ";
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['code'] = "ΦΠΑ".($item_count + 1);
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['lineNumber'] = $item_count + 2;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['netValue'] = $shipping_tax;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['vatCategory'] = $vatCategory_shipping;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['vatAmount'] = "00";
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['incomeClassification'][0]['classificationCategory'] = "category1_95";
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['incomeClassification'][0]['amount'] = $shipping_tax;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['vatExemptionCategory'] = 27;
                        if ($licenses['productKind'] == 'goods') {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['quantity'] = 1;
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['measurementUnit'] = 1;
                        }
                    } else {
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = $order_vat_exemption_category;
                        if ($vies == 'yes') {
                            if ($licenses['productKind'] == 'goods') {
                                $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 8;
                            } else {
                                $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                            }
                            if ($country_out_of_eu == true ) {
                                $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                            }
                        }
                    }
                    if ($shipping_tax == 0 && in_array(16, $primer_license_data['wpModules']) && $order_vat_exemption_category == '') {
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 27;
                        if ($vies == 'yes') {
                            if ($licenses['productKind'] == 'goods') {
                                $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 8;
                            } else {
                                $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                            }
                            if ( $country_out_of_eu == true ) {
                                $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                            }
                        }
                    }
                } else {
                    $total_net_value += $shipping_total;
                    $total_vat_value += $shipping_tax;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['name'] = "ΜΕΤΑΦΟΡΙΚΑ";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['code'] = "EA";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['lineNumber'] = $item_count + 1;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['netValue'] = $shipping_total;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatCategory'] = $vatCategory_shipping;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatAmount'] = "$shipping_tax";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['classificationCategory'] = "category1_5";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['classificationType'] = "E3_562";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['amount'] = $shipping_total;
                    if ($vatCategory_shipping == 7) {
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = $order_vat_exemption_category;
                    }
                    if ($shipping_tax == 0 && in_array(16, $primer_license_data['wpModules']) && $order_vat_exemption_category == '') {
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 27;
                    }
                    if ($vies == 'yes') {
                        if ($licenses['productKind'] == 'goods') {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 8;
                        } else {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                        }
                        if ( $country_out_of_eu == true ) {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                        }
                    }
                    $total_sub_shipping = $subtotal;
                    $subtotal = $subtotal + $shipping_total;
                }
            }
            $fee_total = 0;
            $fee_total_tax = 0;
            $fee_tax_rate = 0;
            $vatCategory_fee = 0;
            $fee_net_value = 0;
            if (!empty($primer_license_data['wpModules'])) {
                if (in_array(14, $primer_license_data['wpModules'])) {
                    foreach ($order->get_items('fee') as $item_id => $item_fee) {
                        $fee_total = $item_fee->get_total();
                        $fee_total_tax = $item_fee->get_total_tax();
                        $fee_net_value = $fee_total;
                        $taxes = $item_fee->get_taxes();
                        foreach ($taxes['total'] as $tax_rate_id => $tax_amount) {
                            if ($tax_amount > 0) {
                                $tax_rate = WC_Tax::_get_tax_rate($tax_rate_id);
                                $fee_tax_rate = $tax_rate['tax_rate'];
                                break;
                            }
                        }
                        switch ($fee_tax_rate) {
                            case "24":
                                $vatCategory_fee = 1;
                                break;
                            case "17":
                                $vatCategory_fee = 4;
                                break;
                            case "13":
                                $vatCategory_fee = 2;
                                break;
                            case "9":
                                $vatCategory_fee = 5;
                                break;
                            case "6":
                                $vatCategory_fee = 3;
                                break;
                            case "4":
                                $vatCategory_fee = 6;
                                break;
                            case "0":
                                $vatCategory_fee = 7;
                                break;
                            default:
                                $vatCategory_fee = 7;
                                $outside_vat = 'yes';
                        }
                    }
                } else {
                    foreach ($order->get_items('fee') as $item_id => $item_fee) {
                        $check_fee = $item_fee->get_total();
                    }
                    if ($check_fee > 0) {
                        $receipt_log_value .= __('order could not be converted because product value sum is not equal with total value. A payment fee was used and your edition does not support payment fees. Please create a new order without payment fee or go to www.primer.gr to select another MyData plugin version.', 'primer');
                        $response_data .= '<div class="primer_popup popup_error"><div><h3>' . __('order could not be converted because product value sum is not equal with total value. A payment fee was used and your edition does not support payment fees. Please create a new order without payment fee or go to www.primer.gr to select another MyData plugin version.', 'primer') . '</h3><br><br><br><br><br></div>';
                        $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
                        $receipt_log_value_array[] = __('order could not be converted because product value sum is not equal with total value. A payment fee was used and your edition does not support payment fees. Please create a new order without payment fee or go to www.primer.gr to select another MyData plugin version.', 'primer');
                        update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value);
                        if ($callingFunction == "convert_order_to_invoice") {
                            update_post_meta($receipt_log_id, 'receipt_log_automation_error', $receipt_log_value);
                        } else {
                            update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value);
                        }
                        return "break"; //EDW EINAI TO TELEYTAIO BREAK
                    }

                }
            }
            if ($fee_total > 0) {
                if ($shipping_total > 0) {
                    $item_count++;
                }
                $fee_total = number_format((float)$fee_total, 2, '.', '');
                $fee_total_tax = number_format((float)$fee_total_tax, 2, '.', '');
                $fee_net_value = number_format((float)$fee_net_value, 2, '.', '');
                $subtotal = $subtotal + $fee_total;
                if ($outside_vat != 'yes') {
                    $total_net_value += $fee_total;
                    $total_vat_value += $fee_total_tax;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['name'] = "ΔΙΑΦΟΡΑ ΕΞΟΔΑ";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['code'] = "ΔΕ";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['lineNumber'] = $item_count + 1;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['netValue'] = $fee_total;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatCategory'] = $vatCategory_fee;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatAmount'] = "$fee_total_tax";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['classificationCategory'] = "category1_5";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['classificationType'] = "E3_562";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['amount'] = $fee_net_value;
                    if ($vatCategory_fee == 7) {
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = $order_vat_exemption_category;
                    }
                    if ($fee_total_tax == 0 && in_array(16, $primer_license_data['wpModules']) && $order_vat_exemption_category == '') {
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 27;
                    }
                } else {
                    $total_net_value += $fee_total;
                    $vatCategory_fee = 7;
                    if ($shipping_tax > 0 && $outside_vat == 'yes') {
                        $item_count ++;
                    }
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['name'] = "ΔΙΑΦΟΡΑ ΕΞΟΔΑ";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['code'] = "ΔΕ";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['lineNumber'] = $item_count + 1;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['netValue'] = $fee_total;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatCategory'] = $vatCategory_fee;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatAmount'] = "0.00";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['classificationCategory'] = "category1_5";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['classificationType'] = "E3_562";
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['incomeClassification'][0]['amount'] = $fee_net_value;
                    $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 27;
                    if ($vies == 'yes') {
                        if ($licenses['productKind'] == 'goods') {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 8;
                        } else {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                        }
                        if ( $country_out_of_eu == true ) {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory']  = 4;
                        }
                    }
                    if ($licenses['productKind'] == 'goods') {
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['quantity'] = 1;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['measurementUnit'] = 1;
                    }
                    $total_sub_shipping = $subtotal;
                    $subtotal = $subtotal + $shipping_total;
                    if ($fee_total_tax > 0) {
                        $total_net_value += $fee_total_tax;
                        $total_vat_value += $fee_total_tax;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['name'] = "ΔΙΑΦΟΡΑ ΕΞΟΔΑ";
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['code'] = "ΦΠΑ".($item_count - 1);
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['lineNumber'] = $item_count + 2;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['netValue'] = $fee_total_tax;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['vatCategory'] = $vatCategory_fee;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['vatAmount'] = "00";
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['incomeClassification'][0]['classificationCategory'] = "category1_95";
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['incomeClassification'][0]['amount'] = $fee_total_tax;
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['vatExemptionCategory'] = 27;
                        if ($vies == 'yes') {
                            if ($licenses['productKind'] == 'goods') {
                                $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 8;
                            } else {
                                $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                            }
                            if ( $country_out_of_eu == true ) {
                                $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['vatExemptionCategory'] = 4;
                            }
                        }

                        if ($licenses['productKind'] == 'goods') {
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['quantity'] = 1;
                            $invoice_data['invoice'][0]['invoiceDetails'][$item_count + 1]['measurementUnit'] = 1;
                        }
                    } else {
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = $order_vat_exemption_category;
                        if ($vies == 'yes') {
                            if ($licenses['productKind'] == 'goods') {
                                $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 8;
                            } else {
                                $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                            }
                            if ( $country_out_of_eu == true ) {
                                $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                            }
                        }
                    }
                    if ($fee_total_tax == 0 && in_array(16, $primer_license_data['wpModules']) && $order_vat_exemption_category == '') {
                        $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 27;
                        if ($vies == 'yes') {
                            if ($licenses['productKind'] == 'goods') {
                                $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 8;
                            } else {
                                $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                            }
                            if ( $country_out_of_eu == true ) {
                                $invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatExemptionCategory'] = 4;
                            }
                        }
                    }
                }
            }
            $total = $order->get_total();

            $total_difference = $total_net_value + $total_vat_value;
            ///
            $total = number_format((float)$total, 2, '.', '');
            $total_difference = number_format((float)$total_difference, 2, '.', '');
            if ($discount_difference > 0 && empty($get_coupon)) {
                if ($total_difference < $total) {
                    $total = $total_difference;
                }
                if ($total_difference > $total) {
                    $total += 0.01;
                }
            }
            $invoice_data['invoice'][0]['paymentMethods']['paymentMethodDetails'][0]['type'] = 1;
            $invoice_data['invoice'][0]['paymentMethods']['paymentMethodDetails'][0]['amount'] = "$total";
            $subtotal = $total_net_value;
            $subtotal = number_format((float)$subtotal, 2, '.', '');
            $total_tax = $total_vat_value;
            $total_tax = number_format((float)$total_tax, 2, '.', '');
            $total = number_format((float)$total, 2, '.', '');
            $order_total_price = number_format((float)$order_total_price, 2, '.', '');
            $shipping_total = number_format((float)$shipping_total, 2, '.', '');
            $total_sub_shipping = number_format((float)$total_sub_shipping, 2, '.', '');
            if ($outside_vat != 'yes') {
                $invoice_data['invoice'][0]['invoiceSummary']['totalNetValue'] = "$total_net_value";
                $invoice_data['invoice'][0]['invoiceSummary']['totalVatAmount'] = "$total_vat_value";
            } else {
                $invoice_data['invoice'][0]['invoiceSummary']['totalNetValue'] = "$total";
                $invoice_data['invoice'][0]['invoiceSummary']['totalVatAmount'] = "0.00";
            }
            $invoice_data['invoice'][0]['invoiceSummary']['totalWithheldAmount'] = 0;
            $invoice_data['invoice'][0]['invoiceSummary']['totalFeesAmount'] = 0;
            $invoice_data['invoice'][0]['invoiceSummary']['totalStampDutyAmount'] = 0;
            $invoice_data['invoice'][0]['invoiceSummary']['totalDeductionsAmount'] = 0;
            $invoice_data['invoice'][0]['invoiceSummary']['totalOtherTaxesAmount'] = 0;
            $invoice_data['invoice'][0]['invoiceSummary']['totalGrossValue'] = "$total";
            if ($outside_vat != 'yes') {
                $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][0]['classificationCategory'] = $classificationCategory;
                $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][0]['classificationType'] = $classificationType;
                if ($shipping_total > 0) {
                    $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][0]['amount'] = "$total_sub_shipping";
                    //////
                    $total = $total_net_value + $total_vat_value;
                    $invoice_data['invoice'][0]['invoiceSummary']['totalGrossValue'] = "$total";
                    $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][1]['classificationCategory'] = "category1_5";
                    $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][1]['classificationType'] = "E3_562";
                    if ($fee_total > 0) {
                        $shipping_total = number_format((float)($shipping_total + $fee_total), 2, '.', '');
                        $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][1]['amount'] = "$shipping_total";
                    } else {
                        $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][1]['amount'] = "$shipping_total";
                    }
                } else if ($fee_total > 0) {
                    $total_for_product = number_format((float)($subtotal - $fee_total), 2, '.', '');
                    $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][0]['amount'] = "$total_for_product";
                    $invoice_data['invoice'][0]['invoiceSummary']['totalGrossValue'] = "$order_total_price";
                    $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][1]['classificationCategory'] = "category1_5";
                    $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][1]['classificationType'] = "E3_562";
                    $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][1]['amount'] = "$fee_net_value";
                } else {
                    $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][0]['amount'] = "$subtotal";
                }
                $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][0]['id'] = "1";
            } else {
                $amount_class = number_format((float)($total - $total_tax - $fee_net_value - $shipping_total), 2, '.', '');
                $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][0]['classificationCategory'] = $classificationCategory;
                $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][0]['classificationType'] = $classificationType;
                $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][0]['amount'] = "$amount_class";
                $total_counter = 1;
                if ($total_tax != 0) {
                    $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][$total_counter]['classificationCategory'] = $classificationCategory_en;
                    $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][$total_counter]['amount'] = "$total_tax";
                    $total_counter = 2;
                }
                if ($shipping_total > 0) {
                    $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][$total_counter]['classificationCategory'] = "category1_5";
                    $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][$total_counter]['classificationType'] = "E3_562";
                    if ($fee_total = 0) {
                        $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][$total_counter]['amount'] = "$shipping_total";
                    } else {
                        $ship_and_fee = number_format((float)($fee_net_value + $shipping_total), 2, '.', '');
                        $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][$total_counter]['amount'] = "$ship_and_fee";
                    }

                }
                if ($fee_total > 0) {
                    $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][$total_counter]['classificationCategory'] = "category1_5";
                    $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][$total_counter]['classificationType'] = "E3_562";
                    $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'][$total_counter]['amount'] = "$fee_net_value";
                }
            }
        }
        // filter array of incomeClassification for amount > 0
        $invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'] = array_values(array_filter($invoice_data['invoice'][0]['invoiceSummary']['incomeClassification'], function($obj) {
            return ($obj["amount"]) > 0;
        }));
        $order_city = $order->get_billing_city();
        $order_postcode = $order->get_billing_postcode();
        $order_address = $order->get_billing_address_1();
        $order_phone = $order->get_billing_phone();
        $order_vatNumber = get_post_meta($id_of_order, '_billing_vat', true);
        $shipping_company = get_post_meta($order_id, '_shipping_company', true);
        $shipping_address = $order->get_shipping_address_1();
        $shipping_city = get_post_meta($order_id,'_shipping_city', true);
        $shipping_zip_code = get_post_meta($order_id, '_shipping_postcode', true);
        $order_comment = $order->get_customer_note();
        if(array_key_exists('mydata_invoice_notes', $mydata_options)) {
            $primer_invoice_notes = $mydata_options['mydata_invoice_notes'] != null ? $mydata_options['mydata_invoice_notes'] : '';
        }else{
            $primer_invoice_notes = '';
        }
        $length_notes = strlen( (string)$primer_invoice_notes );
        if($length_notes > 750) {
            $primer_invoice_notes = substr($primer_invoice_notes, 0, 750);
        }
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $customer_activity = get_post_meta($order_id, '_billing_store', true);
            $customer_doy = get_post_meta($order_id, '_billing_doy', true);

        if ($order_country == 'GR' && ($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice')) {
            $order_vatNumber = trim($order_vatNumber);
            $invoice_data['invoice'][0]['counterpart']['vatNumber'] = $order_vatNumber;
            $invoice_data['invoice'][0]['counterpart']['country'] = $order_country;
        }
        if ($order_country !== 'GR' && ($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice')) {
            $order_billing_company_name = $order->get_billing_company();
            $invoice_data['invoice'][0]['counterpart']['vatNumber'] = $order_vatNumber;
            $invoice_data['invoice'][0]['counterpart']['country'] = $order_country;
            $invoice_data['invoice'][0]['counterpart']['address']['city'] = $order_city;
            $invoice_data['invoice'][0]['counterpart']['address']['postalCode'] = $order_postcode;
            $invoice_data['invoice'][0]['counterpart']['address']['number'] = 0;
            $invoice_data['invoice'][0]['counterpart']['address']['street'] = $order_address;
            $invoice_data['invoice'][0]['counterpart']['name'] = $order_billing_company_name;
            $customer_name = $order_billing_company_name;
        }
        $invoice_data['invoice'][0]['extra']['showOptionalFields'] = false;
        $invoice_data['invoice'][0]['extra']['time'] = $invoice_time;
        $invoice_data['templateId'] = '1';
        $invoice_data['invoice'][0]['extra']['sendEmail'] = false;
        if ($mydata_options['primer_use_api_smtp'] == 'on') {
            $invoice_data['invoice'][0]['extra']['sendEmail'] = true;
            $invoice_data['invoice'][0]['extra']['email'] = $user_order_email; // always
        }
        $use_upload_logo = isset($mydata_options['primer_use_logo']) ? $mydata_options['primer_use_logo'] : '';
        if (!empty($mydata_options['image_api_id']) && !empty($use_upload_logo)) {
            $photo_id_arg = explode(':', $mydata_options['image_api_id']);
            if (count($photo_id_arg) > 1) {
                $response_value = $photo_id_arg[1];
                $response_value = str_replace('"', '', $response_value);
                $photo_id = ltrim($response_value);
                $invoice_data['photoId'] = $photo_id;
            }
        }
        $invoice_data['invoice'][0]['extra']['notes'] = $order_comment . $primer_invoice_notes;
        $invoice_data['invoice'][0]['extra']['address'] = $order_address; // always
        $invoice_data['invoice'][0]['extra']['phone'] = $order_phone; // always
        $invoice_data['invoice'][0]['extra']['tk'] = $order_postcode; // always
        $invoice_data['invoice'][0]['extra']['city'] = $order_city; //always
        $invoice_data['invoice'][0]['extra']['customerCode'] = $user_id; // always
        $invoice_data['invoice'][0]['extra']['customerName'] = $customer_name; // always
        $invoice_data['invoice'][0]['extra']['locationName'] = "ΕΔΡΑ ΠΕΛΑΤΗ";
        $invoice_data['invoice'][0]['extra']['locationAddress'] = $shipping_address; // always
        $invoice_data['invoice'][0]['extra']['locationCity'] = $shipping_city;
        $invoice_data['invoice'][0]['extra']['locationZipCode'] = $shipping_zip_code;
        $invoice_data['invoice'][0]['extra']['isFromPlugin'] = "true";
        if ($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice') {
            $invoice_data['invoice'][0]['extra']['customerActivity'] = $customer_activity; // invoice
            if ($order_country == 'GR'){
                $invoice_data['invoice'][0]['extra']['customerDoy'] = primer_return_doy_args()[$customer_doy];
            }

             // invoice
            $invoice_data['invoice'][0]['extra']['customerVat'] = $order_vatNumber;// invoice
        }
        if ($callingFunction == "convert_order_to_invoice") {
            update_post_meta($receipt_log_id, 'receipt_log_automation_error', $receipt_log_value);
            update_post_meta($receipt_log_id, 'json_send_to_api', json_encode($invoice_data));
        } elseif ($callingFunction == "convert_order_to_invoice_failed") {
            update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value);
            update_post_meta($receipt_log_id, 'json_send_to_api', json_encode($invoice_data));
        } else {
            update_post_meta($receipt_log_id, 'json_send_to_api', json_encode($invoice_data));
            update_post_meta($receipt_log_id, 'receipt_log_order_id', $id_of_order);
            update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value);
        }


        return ($invoice_data);
    }

    public function numbering($order_invoice_type, $order_country, &$mydata_options, &$series, $is_credit = false): void
    {
        if($is_credit){
            if ($order_invoice_type == 'receipt'  ) {
                if($mydata_options['mydata_api'] == 'production_api'){
                    $mydata_options['credit_receipt_'.$series.''] = $mydata_options['credit_receipt_'.$series.''] + 1;
                }else{
                    $mydata_options['credit_receipt_'.$series.'_test_api'] = $mydata_options['credit_receipt_'.$series.'_test_api'] + 1;
                }
            }
            if ($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice') {
                if($mydata_options['mydata_api'] == 'production_api'){
                    $mydata_options['credit_invoice_'.$series.''] = $mydata_options['credit_invoice_'.$series.''] + 1;
                }else{
                    $mydata_options['credit_invoice_'.$series.'_test_api'] = $mydata_options['credit_invoice_'.$series.'_test_api'] + 1;
                }
            }
        } else {
            if ($order_invoice_type == 'receipt' && $order_country == 'GR') {
                if ($mydata_options['mydata_api'] == 'production_api') {
                    $mydata_options['invoice_numbering_gr_' . $series . ''] = $mydata_options['invoice_numbering_gr_' . $series . ''] + 1;
                } else {
                    $mydata_options['invoice_numbering_gr_' . $series . '_test_api'] = $mydata_options['invoice_numbering_gr_' . $series . '_test_api'] + 1;
                }
            }
            if ($order_invoice_type == 'receipt' && $order_country !== 'GR' && check_zone_country($order_country) == true) {
                if ($mydata_options['mydata_api'] == 'production_api') {
                    $mydata_options['invoice_numbering_gr_' . $series . ''] = $mydata_options['invoice_numbering_gr_' . $series . ''] + 1;
                } else {
                    $mydata_options['invoice_numbering_gr_' . $series . '_test_api'] = $mydata_options['invoice_numbering_gr_' . $series . '_test_api'] + 1;
                }
            }
            if ($order_invoice_type == 'receipt' && check_zone_country($order_country) == false) {
                if ($mydata_options['mydata_api'] == 'production_api') {
                    $mydata_options['invoice_numbering_gr_' . $series . ''] = $mydata_options['invoice_numbering_gr_' . $series . ''] + 1;
                } else {
                    $mydata_options['invoice_numbering_gr_' . $series . '_test_api'] = $mydata_options['invoice_numbering_gr_' . $series . '_test_api'] + 1;
                }
            }
            if (($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice') && $order_country == 'GR') {
                if ($mydata_options['mydata_api'] == 'production_api') {
                    $mydata_options['invoice_numbering_gi_' . $series . ''] = $mydata_options['invoice_numbering_gi_' . $series . ''] + 1;
                } else {
                    $mydata_options['invoice_numbering_gi_' . $series . '_test_api'] = $mydata_options['invoice_numbering_gi_' . $series . '_test_api'] + 1;
                }
            }
            if (($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice') && $order_country !== 'GR' && check_zone_country($order_country) == true) {
                if ($mydata_options['mydata_api'] == 'production_api') {
                    $mydata_options['invoice_numbering_within_' . $series . ''] = $mydata_options['invoice_numbering_within_' . $series . ''] + 1;
                } else {
                    $mydata_options['invoice_numbering_within_' . $series . '_test_api'] = $mydata_options['invoice_numbering_within_' . $series . '_test_api'] + 1;
                }
            }

            if (($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice') && check_zone_country($order_country) == false) {
                if ($mydata_options['mydata_api'] == 'production_api') {
                    $mydata_options['invoice_numbering_outside_' . $series . ''] = $mydata_options['invoice_numbering_outside_' . $series . ''] + 1;
                } else {
                    $mydata_options['invoice_numbering_outside_' . $series . '_test_api'] = $mydata_options['invoice_numbering_outside_' . $series . '_test_api'] + 1;
                }
            }

            if ((empty($order_invoice_type) && $order_country == 'GR') || (empty($order_invoice_type) && $order_country != 'GR')) {
                if ($mydata_options['mydata_api'] == 'production_api') {
                    $mydata_options['invoice_numbering_gr_' . $series . ''] = $mydata_options['invoice_numbering_gr_' . $series . ''] + 1;
                } else {
                    $mydata_options['invoice_numbering_gr_' . $series . '_test_api'] = $mydata_options['invoice_numbering_gr_' . $series . '_test_api'] + 1;
                }
            }
        }
    }

    public function get_invoice_status(&$api_type, &$id_of_order, &$gr_time, &$serie, &$number, &$invoiceType, &$order_invoice_type, &$order_vatNumber, &$user_vat,
                                       &$total, $auth, &$invoice_term, &$insert_taxonomy, &$order, &$user_data, &$user_id, &$order_total_price, &$currency_symbol,
                                       &$order_country, &$mydata_options, &$series, &$total_vat_number, &$receipt_log_value, &$receipt_log_value_array, &$user_order_email,
                                       &$response_data, &$receipt_log_id, $url_slug, &$callingFunction, &$generated_uid, $post_id_failed,$connectionFailedMessage)
    {

        $primer_smtp = PrimerSMTP::get_instance();
        $primer_smtp_options = get_option('primer_emails');
        $primer_license_data = get_option('primer_licenses');
        $credit = false;
        if ($api_type == 'test') {
            $url_slug = 'https://test-mydataapi.primer.gr';
        }
        $invoice_status_url = $url_slug . '/v2/invoices/getInvoiceFromUID';
        $time_for_call = get_post_meta($id_of_order, 'order_date_failed_timeout', true);
        if (empty($time_for_call)) {
            $time_for_call = $gr_time;
        }
        $branch = '';
        if ($callingFunction == 'convert_select_orders' || $callingFunction == 'convert_order_to_invoice' ) {
            if (isset($primer_license_data['currentBranchID']) && $primer_license_data['currentBranchID'] != "0") {
                $branch = $primer_license_data['currentBranchID'];
            } else {
                // If currentBranchID is not stored or is 0, set it to "0"
                $branch = "0";
            }
        } elseif ($callingFunction == 'primer_cancel_invoice') {
            $order_id_from_receipt = get_post_meta($id_of_order, 'order_id_from_receipt', true);
            $branchIDfromReceipt = get_post_meta($order_id_from_receipt, 'branchID', true);
            if ($branchIDfromReceipt == null) {
                $branch = "0";
            } else {
                $branch = $branchIDfromReceipt;
            }
        } elseif ( $callingFunction == 'convert_order_to_invoice_failed' ) {
            $order_id_from_receipt = get_post_meta($id_of_order, 'order_id_from_receipt', true);
            $branchIDfromReceipt = get_post_meta($order_id_from_receipt, 'branchID', true);
            if ($branchIDfromReceipt == null) {
                $branch = "0";
            } else {
                $branch = $branchIDfromReceipt;
            }
        } elseif ( $callingFunction == 'primer_cancel_invoice_cron' ) {
            $order_id_from_receipt = get_post_meta($id_of_order, 'order_id_from_receipt', true);
            $branchIDfromReceipt = get_post_meta($order_id_from_receipt, 'branchID', true);
            if ($branchIDfromReceipt == null) {
                $branch = "0";
            } else {
                $branch = $branchIDfromReceipt;
            }
        }
        if($serie == 'EMPTY'){
            $serie = '0';
        }
        //
        $generated_uid = strtoupper(sha1(iconv("UTF-8", "ISO-8859-7",strval($user_vat).'-'.strval($time_for_call).'-'.strval($branch).'-'.strval($invoiceType).'-'.strval($serie).'-'.strval($number))));
        $curl_args = array(
            'timeout' => 15,
            'redirection' => 10,
            'method' => 'POST',
            'httpversion' => '1.1',
            'headers' => array(
                'Authorization' => 'Basic ' . $auth,
                'Content-Type' => 'application/json'
            ),
            'sslverify' => false
        );
        $curl_args['body'] = '{
				"uid": "'.$generated_uid.'"
			}';
        $last_response_request = wp_remote_post($invoice_status_url, $curl_args);
        $last_response = wp_remote_retrieve_body($last_response_request);
        $last_info = wp_remote_retrieve_response_code($last_response_request);
        if($serie == '0') {
            $serie = 'EMPTY';
        }
        if (!empty($last_info)) {
            if ($last_info == '200' || $last_info > 500) {
                $last_response_to_array = json_decode($last_response);
                $responseUid = $last_response_to_array[0]->uid;
                $responseMark = $last_response_to_array[0]->mark;
                $responseAuthCode = $last_response_to_array[0]->authenticationCode;
                if (!ini_get('allow_url_fopen')) {
                    $response_data .= '<div class="primer_popup popup_error"><div><h3>' . __('Php option allow_url_fopen is disabled! Please communicate with your hosting provider in order to activate it.', 'primer') . '</h3><br><br><br><br><br></div>';
                    $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
                    $receipt_log_value .= __('Php option allow_url_fopen is disabled! Please communicate with your hosting provider in order to activate it.', 'primer');
                    return "break";
                }
                    if ($callingFunction == 'primer_cancel_invoice') {
                        $post_id = wp_insert_post(array(
                            'post_type' => 'primer_receipt',
                            'post_title' => 'Credit Receipt for order #' . $id_of_order,
                            'comment_status' => 'closed',
                            'ping_status' => 'closed',
                            'post_status' => 'publish',
                        ));
                    } elseif ($callingFunction == 'convert_order_to_invoice_failed') {
                        $post_id = wp_insert_post(array(
                            'post_type' => 'primer_receipt',
                            'post_title' => 'Receipt for order #' . $id_of_order . '-failed',
                            'comment_status' => 'closed',
                            'ping_status' => 'closed',
                            'post_status' => 'publish',
                        ));
                    } elseif ($callingFunction == 'primer_cancel_invoice_cron') {
                        $post_id = wp_insert_post(array(
                            'post_type' => 'primer_receipt',
                            'post_title' => 'Credit Receipt for order #' . $id_of_order . '-failed',
                            'comment_status' => 'closed',
                            'ping_status' => 'closed',
                            'post_status' => 'publish',
                        ));
                    } else {
                        $post_id = wp_insert_post(array(
                            'post_type' => 'primer_receipt',
                            'post_title' => 'Receipt for order #' . $id_of_order,
                            'comment_status' => 'closed',
                            'ping_status' => 'closed',
                            'post_status' => 'publish',
                        ));
                    }
                wp_set_object_terms($post_id, $invoice_term, $insert_taxonomy, false);
                if ($post_id) {
                    $issue_date = new DateTime("now", new DateTimeZone("Europe/Athens"));
                    $invoice_date = $issue_date->format('d/m/Y');
                    $invoice_time = $issue_date->format('H:i');
                    if ( $callingFunction == 'primer_cancel_invoice' || $callingFunction == 'primer_cancel_invoice_cron') {
                        update_post_meta($post_id, 'credit_success_mydata_date', $invoice_date);
                        update_post_meta($post_id, 'credit_success_mydata_time', $invoice_time);
                    } else {
                        update_post_meta($post_id, 'success_mydata_date', $invoice_date);
                        update_post_meta($post_id, 'success_mydata_time', $invoice_time);
                    }
                    update_post_meta($post_id, 'receipt_type', $invoice_term);
                    update_post_meta($post_id, 'send_to_api_type', $api_type);
                    update_post_meta($post_id, 'order_id_to_receipt', $id_of_order);
                    if ( $callingFunction == 'primer_cancel_invoice' || $callingFunction == 'primer_cancel_invoice_cron') {
                        update_post_meta($id_of_order, 'order_id_from_credit_receipt', $post_id);
                    } else {
                        update_post_meta($id_of_order, 'order_id_from_receipt', $post_id);
                    }
                    add_post_meta($post_id, 'receipt_client', $user_data);
                    add_post_meta($post_id, 'receipt_client_id', $user_id);
                    add_post_meta($post_id, 'receipt_price', $order_total_price . ' ' . $currency_symbol);
                    update_post_meta($post_id, '_primer_receipt_number', $number);
                    update_post_meta($post_id, '_primer_receipt_series', $serie);
                    if ( $last_info == 512 ) {
                        update_post_meta($post_id, 'connection_fail_message', 'ΑΔΥΝΑΜΙΑ ΔΙΑΣΥΝΔΕΣΗΣ ΠΑΡΟΧΟΥ - ΑΑΔΕ');
                    } elseif ( $last_info > 500 ) {
                        update_post_meta($post_id, 'connection_fail_message', 'ΑΔΥΝΑΜΙΑ ΔΙΑΣΥΝΔΕΣΗΣ ΟΝΤΟΤΗΤΑΣ - ΠΑΡΟΧΟΥ');
                    }
                    if ($serie == "EMPTY" ) {
                        $identifier = "0" . "_" . $number . "_" . $invoice_term . "_" . $branch;
                    } else {
                        $identifier = $serie . "_" . $number . "_" . $invoice_term . "_" . $branch;
                    }
                    update_post_meta($post_id, 'numbering_identifier',$identifier);
                    if ( $callingFunction == 'primer_cancel_invoice' || $callingFunction == 'primer_cancel_invoice_cron') {
                        update_post_meta( $post_id, 'credit_receipt', 'yes' );
                        $credit = true;
                    }
                    $this->numbering($order_invoice_type, $order_country, $mydata_options, $series, $credit);
                    update_option('primer_mydata', $mydata_options);
                    if (!empty($responseMark)) {
                        update_post_meta($post_id, 'response_invoice_uid', $responseMark);
                    }
                    if (!empty($responseUid)) {
                        update_post_meta($post_id, 'response_invoice_mark', $responseUid);
                    }
                    if (!empty($responseAuthCode)) {
                        update_post_meta($post_id, 'response_invoice_authcode', $responseAuthCode);
                    }
                    update_post_meta($post_id, 'branchID', $branch);
                    update_post_meta($post_id, 'connection_fail_message', '');
                    if ($callingFunction == 'convert_order_to_invoice_failed' || $callingFunction == 'primer_cancel_invoice_cron') {
                        update_post_meta($post_id, 'connection_fail_message', $connectionFailedMessage);
                    }
                }

                primer_generate_qr($post_id, $generated_uid);
                //$post_id = get_post_meta($id_of_order, 'order_id_from_receipt', true);
                update_post_meta($post_id, 'response_invoice_mark', $responseMark);
                update_post_meta($post_id, 'response_invoice_uid', $responseUid);
                update_post_meta($post_id, 'response_invoice_authcode', $responseAuthCode);
                $primer_options = new Primer_Options();
                $post_ids_str = '';
                if (!empty($post_id)) {
                    $post_ids_str = $total_vat_number . $responseMark;
                }
                $post_arr_id = explode(" ", $post_id);
                $use_url_params = '?type_logo=id';
                $generate_html_response = $primer_options->export_receipt_as_static_html_by_page_id($post_arr_id, $use_url_params);
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
                    if (!empty($post_id)) {
                        $primer_license_data = get_option('primer_licenses');
                        $post_url = get_the_permalink($post_id);
                        $post_url = $post_url . '?receipt=view&username='.$primer_license_data['username'];
                        $arrContextOptions = array(
                            "ssl" => array(
                                "verify_peer" => false,
                                "verify_peer_name" => false,
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
                        $Vat_exemption_categories = $this->getVatExemptionCategories();
                        $Vat_exemption_categories_en = $this->getVatExemptionCategoriesEn();
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
                        // instantiate and use the dompdf class
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
                        $headers = 'From: ' . $primer_smtp_options['from_email_field'] ? $primer_smtp_options['from_email_field'] : 'Primer ' . get_bloginfo('admin_email');
                        if (!empty($primer_smtp_options['email_subject'])) {
                            $primer_smtp_subject = $primer_smtp_options['email_subject'];
                        } else {
                            $primer_smtp_subject = __('Test email subject', 'primer');
                        }
                        if (!empty($primer_smtp_options['email_from_name'])) {
                            $from_name_email = $primer_smtp_options['email_from_name'];
                        } else {
                            $from_name_email = '';
                        }
                        if (!empty($primer_smtp_options['quote_available_content'])) {
                            $primer_smtp_message = $primer_smtp_options['quote_available_content'];
                            $client_first_name = get_post_meta($id_of_order, '_billing_first_name', true);
                            $client_last_name = get_post_meta($id_of_order, '_billing_last_name', true);
                            $client_email = get_post_meta($id_of_order, '_billing_email', true);
                            $streetAddress = get_post_meta($id_of_order, '_billing_address_1', true);
                            $townCity = get_post_meta($id_of_order, '_billing_city', true);
                            $phone = get_post_meta($id_of_order, '_billing_phone' , true);
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
                        if ( $callingFunction == 'primer_cancel_invoice' || $callingFunction == 'primer_cancel_invoice_cron' ) {
                            $order_log_id = get_post_meta($id_of_order, 'credit_log_id_for_order', true);
                        } else {
                            $order_log_id = get_post_meta($id_of_order, 'log_id_for_order', true);
                        }

                        if (!empty($order_log_id)) {

                            if ( $callingFunction == 'primer_cancel_invoice' || $callingFunction == 'primer_cancel_invoice_cron') {

                                update_post_meta($post_id, 'credit_log_id_for_order', $order_log_id);

                            } else {

                                update_post_meta($post_id, 'log_id_for_order', $order_log_id);

                            }
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
                            $mailResult = false;
                            if ($primer_smtp_type == 'wordpress_default') {
                                $headers = array('Content-Type: text/html; charset=UTF-8');
                                $mailResultSMTP = wp_mail($user_order_email, $primer_smtp_subject, $primer_smtp_message, $headers, $attachments);
                            } else {
                                $mailResultSMTP = $primer_smtp->primer_mail_sender($user_order_email, $from_name_email, $primer_smtp_subject, $primer_smtp_message, $attachments);
                            }
                            if (!$primer_smtp->credentials_configured()) {
                                $email_logs .= __('Configure your SMTP credentials', 'primer') . "\n";
                            }

                            if (!empty($mailResultSMTP['error']) && !$primer_smtp->credentials_configured()) {
                                $response_data .= '<div class="primer_popup popup_error"><div><h3>' . $GLOBALS['phpmailer']->ErrorInfo . '</h3><br><br><br><br><br></div>';
                                $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
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
                    }
                    update_post_meta($id_of_order, 'transmission_failure_check', 2);
                    if ( $callingFunction == 'primer_cancel_invoice' || $callingFunction == 'primer_cancel_invoice_cron') {
                        update_post_meta($id_of_order, 'cancelled','yes');
                        update_post_meta($post_id, 'cancelled','yes');
                    }
                    //$response_data = '<div class="primer_popup popup_success"><p>' . __('Orders converted', 'primer') . '</p></div>';
                    $response_data = '<div class="primer_popup popup_success"><div>';
                    $response_data .= '<h3>'.__("Orders converted", "primer").'</h3><br><br><br><br><br>';
                    $response_data .= '<button class="popup_ok button button-primary">OK</button>';
                    $response_data .= '</div></div>';
                    return "break";
                } else {
                    $last_response = __('Something went wrong. Please try again!', 'primer');
                    $receipt_log_value_array[] = $last_response;
                    $response_data .= '<div class="primer_popup popup_error"><div><h3><strong>' . $last_response . '</strong></h3><br><br><br><br><br></div>';
                    $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
                    if ($callingFunction == "convert_order_to_invoice") {
                        update_post_meta($receipt_log_id, 'receipt_log_automation_error', $last_response);
                    } else {
                        update_post_meta($receipt_log_id, 'receipt_log_error', $last_response);
                    }
                    return "break";
                }
            } else {
                $last_response = __('Something went wrong. Please try again!', 'primer');
                $receipt_log_value = $last_response;
                $receipt_log_value_array[] = $last_response;
                $response_data .= '<div class="primer_popup popup_error"><div><h3><strong>' . $last_response . '</strong></h3></div>';
                $response_data .= '<button class="popup_ok button button-primary">OK</button></div>';
                if ($callingFunction == "convert_order_to_invoice") {
                    update_post_meta($receipt_log_id, 'receipt_log_automation_error', (string)$last_response);
                } else {
                    update_post_meta($receipt_log_id, 'receipt_log_error', (string)$last_response);
                }
                return "break";
            }
        }

    public function getVatExemptionCategories(): array{
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
        return $Vat_exemption_categories;
    }

    public function getVatExemptionCategoriesEn(): array{
        $Vat_exemption_categories_en = array();
        $Vat_exemption_categories_en[0] = 'Please select a tax exemption category';
        $Vat_exemption_categories_en[1] = 'Without VAT – article 2 and 3 of the VAT Code';
        $Vat_exemption_categories_en[2] = 'Without VAT - article 5 of the VAT Code';
        $Vat_exemption_categories_en[3] = 'Without VAT - article 13 of the VAT Code';
        $Vat_exemption_categories_en[4] = 'Without VAT - article 14 of the VAT Code';
        $Vat_exemption_categories_en[5] = 'Without VAT - article 16 of the VAT Code';
        $Vat_exemption_categories_en[6] = 'Without VAT - article 19 of the VAT Code';
        $Vat_exemption_categories_en[7] = 'Without VAT - article 22 of the VAT Code';
        $Vat_exemption_categories_en[8] = 'Without VAT - article 24 of the VAT Code';
        $Vat_exemption_categories_en[9] = 'Without VAT - article 25 of the VAT Code';
        $Vat_exemption_categories_en[10] = 'Without VAT - article 26 of the VAT Code';
        $Vat_exemption_categories_en[11] = 'Without VAT - article 27 of the VAT Code';
        $Vat_exemption_categories_en[12] = 'Without VAT - article 27 - Seagoing Vessels of the VAT code';
        $Vat_exemption_categories_en[13] = 'Without VAT - ΠΟΛ.1029/1995';
        $Vat_exemption_categories_en[14] = 'Without VAT - article 27.1.γ - Seagoing Vessels of the VAT code';
        $Vat_exemption_categories_en[15] = 'Without VAT - article 28 of the VAT code';
        $Vat_exemption_categories_en[16] = 'Without VAT - article 39 of the VAT code';
        $Vat_exemption_categories_en[17] = 'Without VAT - article 39a of the VAT code';
        $Vat_exemption_categories_en[18] = 'Without VAT - article 40 of the VAT code';
        $Vat_exemption_categories_en[19] = 'Without VAT - article 41 of the VAT code';
        $Vat_exemption_categories_en[20] = 'Without VAT - article 47 of the VAT code';
        $Vat_exemption_categories_en[21] = 'VAT included - article 43 of the VAT code';
        $Vat_exemption_categories_en[22] = 'VAT included - article 44 of the VAT code';
        $Vat_exemption_categories_en[23] = 'VAT included - article 45 of the VAT code';
        $Vat_exemption_categories_en[24] = 'VAT included - article 46 of the VAT code';
        $Vat_exemption_categories_en[25] = 'Without VAT - article 6 of the VAT code';
        $Vat_exemption_categories_en[26] = 'Without VAT - ΠΟΛ.1167/2015';
        $Vat_exemption_categories_en[27] = 'Without VAT – Other VAT exceptions';
        return $Vat_exemption_categories_en;
    }

    }

?>
