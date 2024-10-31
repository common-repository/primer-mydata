<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }
class PrimerOrderList {


	public $orders_array = array();


	public $orders_customers = array();


	public function get($page_number) {
        date_default_timezone_set('Europe/Athens');
		$primer_licenses = get_option('primer_licenses');
        $posts_per_page = 20;
		$order_count = 0;
        $paged = $page_number;
        $orders       = wc_get_orders( array(
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'offset' => ($paged - 1) * $posts_per_page,
            'limit' => 20));
		foreach ( $orders as $order ) {
            if (is_a($order, 'WC_Order_Refund')) {
                $order = wc_get_order($order->get_parent_id());
            }
            if (empty($order)) {
                continue;
            }
            $id_of_order = $order->get_id();
            $receipt_order_status = get_post_meta($id_of_order, 'receipt_status', true);
            if (empty($receipt_order_status)) {
                update_post_meta($id_of_order, 'receipt_status', 'not_issued');
            }
            $order_country = $order->get_billing_country();
            $order_invoice_type = get_post_meta($id_of_order, '_billing_invoice_type', true);
             if (is_array($primer_licenses) && is_array($primer_licenses['wpModules'])) {
            if ((in_array(2, $primer_licenses['wpModules'])) && !in_array(3, $primer_licenses['wpModules']) && !in_array(4, $primer_licenses['wpModules'])) {
                if (($order_invoice_type !== 'primer_invoice' && $order_invoice_type !== 'invoice')) {
                    $transmission_failure = get_post_meta($id_of_order, 'transmission_failure_check', true);
                    $failed_48 = get_post_meta($id_of_order, 'failed_48', true);
                    $order_date_paid = $order->get_date_paid();
                    $order_date_created = $order->get_date_created();
                    if (!empty($order_date_paid)) {
                        $order_paid_date = $order_date_paid->format('F j, Y');
                        $order_paid_hour = $order_date_paid->format('H:i:s');
                    } else {
                        $order_paid_date = $order_date_created->format('F j, Y');
                        $order_paid_hour = $order_date_created->format('H:i:s');
                    }
                    $order_total_price = $order->get_total();
                    $user = $order->get_user();
                    $user_first_name = $order->get_billing_first_name();
                    $user_last_name = $order->get_billing_last_name();
                    $user_full_name = $user_first_name . ' ' . $user_last_name;
                    $currency = $order->get_currency();
                    $currency_symbol = get_woocommerce_currency_symbol($currency);
                    $payment_title = $order->get_payment_method_title();
                    $order_status = $order->get_status();
                    $receipt_date = '';
                    $credit_receipt_date = '';
                    $exist_receipt_id = get_order_from_receipt($id_of_order);
                    $exist_credit_id = get_post_meta($id_of_order, 'cancelled', true);
                    if (!empty($exist_credit_id)) {
                        $credit_receipt_date = 'CANCELLED';
                    }
                    if (!empty($exist_receipt_id)) {
                        $receipt_date = get_the_date('F j, Y', $exist_receipt_id[0]);
                    }
                    $log_for_order = get_post_meta($id_of_order, 'log_id_for_order', true);
                    if (!empty($log_for_order)) {
                        $log_type = get_post_type($log_for_order);
                        if ($log_type == 'pr_log_automation') {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs_automation');
                        } else {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs');
                        }
                        $receipt_status_from_meta_url = $receipt_status_from_meta_url . '&order_log=' . $log_for_order;
                    } else {
                        $receipt_status_from_meta_url = '';
                    }
                    $transmission_failure_message = '';

                    switch ($transmission_failure) {
                        case 1:
                        case 3:
                            $transmission_failure_message = 'Αδυναμία σύνδεσης';
                            break;
                        case 2:
                            $transmission_failure_message = 'Επιτυχής Αποστολή';
                            break;
                        case 4:
                            $transmission_failure_message = 'Αδυναμία Αποστολής HTML';
                            break;
                        default:
                            // Handle the default case if needed
                            break;
                    }
                    $this->orders_array[$order_count]['order_id'] = $id_of_order;
                    $this->orders_array[$order_count]['order_date'] = $order_paid_date;
                    $this->orders_array[$order_count]['order_hour'] = $order_paid_hour;
                    $this->orders_array[$order_count]['order_client'] = $user ? $user->display_name : $user_full_name;
                    $this->orders_array[$order_count]['order_price'] = $order_total_price . ' ' . $currency_symbol;
                    $this->orders_array[$order_count]['transimission_failure'] = $transmission_failure_message;
                    $this->orders_array[$order_count]['accept_48'] = $failed_48;
                    $this->orders_array[$order_count]['order_status'] = $order_status;
                    $this->orders_array[$order_count]['payment_status'] = $payment_title;
                    $this->orders_array[$order_count]['receipt_date'] = $receipt_date;
                    $this->orders_array[$order_count]['credit_receipt_date'] = $credit_receipt_date;
                    $this->orders_array[$order_count]['receipt_status'] = /*$receipt_status_from_meta_text*/
                        $receipt_status_from_meta_url;
                    $this->orders_array[$order_count]['receipt_id'] = $exist_receipt_id ? $exist_receipt_id[0] : '';
                    $this->orders_array[$order_count]['credit_receipt_id'] = $exist_credit_id ? $exist_credit_id[0] : '';
                    $order_count++;
                }
            } elseif (is_array($primer_licenses) && (in_array(3, $primer_licenses['wpModules'])) && !in_array(2, $primer_licenses['wpModules']) && !in_array(4, $primer_licenses['wpModules'])) {
                if (($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice')) {
                    //$order_country = $order->get_billing_country();
                    if ($order_country == 'GR') {
                        $transmission_failure = get_post_meta($id_of_order, 'transmission_failure_check', true);
                        $failed_48 = get_post_meta($id_of_order, 'failed_48', true);

                        $order_date_paid = $order->get_date_paid();
                        $order_date_created = $order->get_date_created();
                        if (!empty($order_date_paid)) {
                            $order_paid_date = $order_date_paid->format('F j, Y');
                            $order_paid_hour = $order_date_paid->format('H:i:s');
                        } else {
                            $order_paid_date = $order_date_created->format('F j, Y');
                            $order_paid_hour = $order_date_created->format('H:i:s');
                        }
                        $order_total_price = $order->get_total();
                        $user = $order->get_user();
                        $user_first_name = $order->get_billing_first_name();
                        $user_last_name = $order->get_billing_last_name();
                        $user_full_name = $user_first_name . ' ' . $user_last_name;
                        $currency = $order->get_currency();
                        $currency_symbol = get_woocommerce_currency_symbol($currency);
                        $payment_title = $order->get_payment_method_title();
                        $order_status = $order->get_status();
                        $receipt_date = '';
                        $credit_receipt_date = '';
                        $exist_receipt_id = get_order_from_receipt($id_of_order);
                        $exist_credit_id = get_post_meta($id_of_order, 'cancelled', true);
                        if (!empty($exist_credit_id)) {
                            $credit_receipt_date = 'CANCELLED';
                        }
                        if (!empty($exist_receipt_id)) {
                            $receipt_date = get_the_date('F j, Y', $exist_receipt_id[0]);
                        }
                        $log_for_order = get_post_meta($id_of_order, 'log_id_for_order', true);
                        if (!empty($log_for_order)) {
                            $log_type = get_post_type($log_for_order);
                            if ($log_type == 'pr_log_automation') {
                                $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs_automation');
                            } else {
                                $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs');
                            }
                            $receipt_status_from_meta_url = $receipt_status_from_meta_url . '&order_log=' . $log_for_order;
                        } else {
                            $receipt_status_from_meta_url = '';
                        }
                        switch ($transmission_failure) {
                            case 1:
                            case 3:
                                $transmission_failure_message = 'Αδυναμία σύνδεσης';
                                break;
                            case 2:
                                $transmission_failure_message = 'Επιτυχής Αποστολή';
                                break;
                            case 4:
                                $transmission_failure_message = 'Αδυναμία Αποστολής HTML';
                                break;
                            default:
                                // Handle the default case if needed
                                break;
                        }
                        $this->orders_array[$order_count]['order_id'] = $id_of_order;
                        $this->orders_array[$order_count]['order_date'] = $order_paid_date;
                        $this->orders_array[$order_count]['order_hour'] = $order_paid_hour;
                        $this->orders_array[$order_count]['order_client'] = $user ? $user->display_name : $user_full_name;
                        $this->orders_array[$order_count]['order_price'] = $order_total_price . ' ' . $currency_symbol;
                        $this->orders_array[$order_count]['order_status'] = $order_status;
                        $this->orders_array[$order_count]['transmission_failure'] = $transmission_failure_message;
                        $this->orders_array[$order_count]['accept_48'] = $failed_48;
                        $this->orders_array[$order_count]['payment_status'] = $payment_title;
                        $this->orders_array[$order_count]['receipt_date'] = $receipt_date;
                        $this->orders_array[$order_count]['credit_receipt_date'] = $credit_receipt_date;
                        $this->orders_array[$order_count]['receipt_status'] = /*$receipt_status_from_meta_text*/
                            $receipt_status_from_meta_url;
                        $this->orders_array[$order_count]['receipt_id'] = $exist_receipt_id ? $exist_receipt_id[0] : '';
                        $this->orders_array[$order_count]['credit_receipt_id'] = $exist_credit_id ? $exist_credit_id[0] : '';
                        $order_count++;
                    }
                }
            } elseif (is_array($primer_licenses) && in_array(4, $primer_licenses['wpModules']) && !in_array(2, $primer_licenses['wpModules']) && !in_array(3, $primer_licenses['wpModules'])) {
                //$order_country = $order->get_billing_country();
                if ($order_country !== 'GR') {
                    $transmission_failure = get_post_meta($id_of_order, 'transmission_failure_check', true);
                    $failed_48 = get_post_meta($id_of_order, 'failed_48', true);
                    $order_date_paid = $order->get_date_paid();
                    $order_date_created = $order->get_date_created();
                    if (!empty($order_date_paid)) {
                        $order_paid_date = $order_date_paid->format('F j, Y');
                        $order_paid_hour = $order_date_paid->format('H:i:s');
                    } else {
                        $order_paid_date = $order_date_created->format('F j, Y');
                        $order_paid_hour = $order_date_created->format('H:i:s');
                    }
                    $order_total_price = $order->get_total();
                    $user = $order->get_user();
                    $user_first_name = $order->get_billing_first_name();
                    $user_last_name = $order->get_billing_last_name();
                    $user_full_name = $user_first_name . ' ' . $user_last_name;
                    $currency = $order->get_currency();
                    $currency_symbol = get_woocommerce_currency_symbol($currency);
                    $payment_title = $order->get_payment_method_title();
                    $order_status = $order->get_status();
                    $receipt_date = '';
                    $credit_receipt_date = '';
                    $exist_receipt_id = get_order_from_receipt($id_of_order);
                    $exist_credit_id = get_post_meta($id_of_order, 'cancelled', true);
                    if (!empty($exist_credit_id)) {
                        $credit_receipt_date = 'CANCELLED';
                    }
                    if (!empty($exist_receipt_id)) {
                        $receipt_date = get_the_date('F j, Y', $exist_receipt_id[0]);
                    }
                    $log_for_order = get_post_meta($id_of_order, 'log_id_for_order', true);
                    if (!empty($log_for_order)) {
                        $log_type = get_post_type($log_for_order);
                        if ($log_type == 'pr_log_automation') {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs_automation');
                        } else {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs');
                        }
                        $receipt_status_from_meta_url = $receipt_status_from_meta_url . '&order_log=' . $log_for_order;
                    } else {
                        $receipt_status_from_meta_url = '';
                    }

                    $transmission_failure_message = '';

                    switch ($transmission_failure) {
                        case 1:
                        case 3:
                            $transmission_failure_message = 'Αδυναμία σύνδεσης';
                            break;
                        case 2:
                            $transmission_failure_message = 'Επιτυχής Αποστολή';
                            break;
                        case 4:
                            $transmission_failure_message = 'Αδυναμία Αποστολής HTML';
                            break;
                        default:
                            // Handle the default case if needed
                            break;
                    }
                    $this->orders_array[$order_count]['order_id'] = $id_of_order;
                    $this->orders_array[$order_count]['order_date'] = $order_paid_date;
                    $this->orders_array[$order_count]['order_hour'] = $order_paid_hour;
                    $this->orders_array[$order_count]['order_client'] = $user ? $user->display_name : $user_full_name;
                    $this->orders_array[$order_count]['order_price'] = $order_total_price . ' ' . $currency_symbol;
                    $this->orders_array[$order_count]['order_status'] = $order_status;
                    $this->orders_array[$order_count]['transmission_failure'] = $transmission_failure_message;
                    $this->orders_array[$order_count]['accept_48'] = $failed_48;
                    $this->orders_array[$order_count]['payment_status'] = $payment_title;
                    $this->orders_array[$order_count]['receipt_date'] = $receipt_date;
                    $this->orders_array[$order_count]['credit_receipt_date'] = $credit_receipt_date;
                    $this->orders_array[$order_count]['receipt_status'] = $receipt_status_from_meta_url;
                    $this->orders_array[$order_count]['receipt_id'] = $exist_receipt_id ? $exist_receipt_id[0] : '';
                    $this->orders_array[$order_count]['credit_receipt_id'] = $exist_credit_id ? $exist_credit_id[0] : '';
                    $order_count++;
                }
            } elseif (is_array($primer_licenses) && in_array(2, $primer_licenses['wpModules']) && in_array(3, $primer_licenses['wpModules']) && in_array(4, $primer_licenses['wpModules'])) {
                $transmission_failure = get_post_meta($id_of_order, 'transmission_failure_check', true);
                $failed_48 = get_post_meta($id_of_order, 'failed_48', true);
                if (!empty($order->get_date_paid())) {
                    $order_paid_date = date('F j, Y', $order->get_date_paid()->getTimestamp());
                    $order_paid_hour = date('H:i:s', $order->get_date_paid()->getTimestamp());
                } else {
                    $order_paid_date = date('F j, Y', $order->get_date_created()->getTimestamp());
                    $order_paid_hour = date('H:i:s', $order->get_date_created()->getTimestamp());
                }
                $order_total_price = $order->get_total();
                $user = $order->get_user();
                $user_first_name = $order->get_billing_first_name();
                $user_last_name = $order->get_billing_last_name();
                $user_full_name = $user_first_name . ' ' . $user_last_name;
                $currency = $order->get_currency();
                $currency_symbol = get_woocommerce_currency_symbol($currency);
                $payment_title = $order->get_payment_method_title();
                $order_status = $order->get_status();
                $receipt_date = '';
                $credit_receipt_date = '';
                $exist_credit_id = '';
                $exist_receipt_id = get_order_from_receipt($id_of_order);
                $exist_credit = get_post_meta($id_of_order, 'cancelled', true);
                if (!empty($exist_credit)) {
                    $credit_receipt_date = 'CANCELLED';
                    $exist_credit_id = get_order_from_receipt($id_of_order);
                }
                if (!empty($exist_receipt_id)) {
                    $receipt_date = get_the_date('F j, Y', $exist_receipt_id[0]);
                }
                $log_for_order = get_post_meta($id_of_order, 'log_id_for_order', true);
                if (!empty($log_for_order)) {
                    $log_type = get_post_type($log_for_order);
                    if ($log_type == 'pr_log_automation') {
                        $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs_automation');
                    } else {
                        $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs');
                    }
                    $receipt_status_from_meta_url = $receipt_status_from_meta_url . '&order_log=' . $log_for_order;
                } else {
                    $receipt_status_from_meta_url = '';
                }
                $transmission_failure_message = '';
                if ($transmission_failure == 1 || $transmission_failure == 3) {
                    $transmission_failure_message = 'Αδυναμία σύνδεσης';
                } else if ($transmission_failure == 2) {
                    $transmission_failure_message = 'Επιτυχής Αποστολή';
                } else if ($transmission_failure == 4) {
                    $transmission_failure_message = 'Αδυναμία Αποστολής HTML';
                }
                $this->orders_array[$order_count]['order_id'] = $id_of_order;
                $this->orders_array[$order_count]['order_date'] = $order_paid_date;
                $this->orders_array[$order_count]['order_hour'] = $order_paid_hour;
                $this->orders_array[$order_count]['order_client'] = $user ? $user->display_name : $user_full_name;
                $this->orders_array[$order_count]['order_price'] = $order_total_price . ' ' . $currency_symbol;
                $this->orders_array[$order_count]['order_status'] = $order_status;
                $this->orders_array[$order_count]['transmission_failure'] = $transmission_failure_message;
                $this->orders_array[$order_count]['accept_48'] = $failed_48;
                $this->orders_array[$order_count]['payment_status'] = $payment_title;
                $this->orders_array[$order_count]['receipt_date'] = $receipt_date;
                $this->orders_array[$order_count]['credit_receipt_date'] = $credit_receipt_date; //CANCELLED
                $this->orders_array[$order_count]['receipt_status'] = $receipt_status_from_meta_url;
                $this->orders_array[$order_count]['receipt_id'] = $exist_receipt_id ? $exist_receipt_id[0] : '';
                $this->orders_array[$order_count]['credit_receipt_id'] = $exist_credit_id ? $exist_credit_id[0] : '';
                $order_count++;
            } elseif (is_array($primer_licenses) && in_array(2, $primer_licenses['wpModules']) && in_array(3, $primer_licenses['wpModules']) && !in_array(4, $primer_licenses['wpModules'])) {
                //$order_country = $order->get_billing_country();
                if ($order_country == 'GR' || $order_country == null) {
                    $transmission_failure = get_post_meta($id_of_order, 'transmission_failure_check', true);
                    $failed_48 = get_post_meta($id_of_order, 'failed_48', true);
                    $order_date_paid = $order->get_date_paid();
                    $order_date_created = $order->get_date_created();
                    if (!empty($order_date_paid)) {
                        $order_paid_date = $order_date_paid->format('F j, Y');
                        $order_paid_hour = $order_date_paid->format('H:i:s');
                    } else {
                        $order_paid_date = $order_date_created->format('F j, Y');
                        $order_paid_hour = $order_date_created->format('H:i:s');
                    }
                    $order_total_price = $order->get_total();
                    $user = $order->get_user();
                    $user_first_name = $order->get_billing_first_name();
                    $user_last_name = $order->get_billing_last_name();
                    $user_full_name = $user_first_name . ' ' . $user_last_name;
                    $currency = $order->get_currency();
                    $currency_symbol = get_woocommerce_currency_symbol($currency);
                    $payment_title = $order->get_payment_method_title();
                    $order_status = $order->get_status();
                    $receipt_date = '';
                    $credit_receipt_date = '';
                    $exist_credit_id = '';
                    $exist_receipt_id = get_order_from_receipt($id_of_order);
                    $exist_credit = get_post_meta($id_of_order, 'cancelled', true);
                    if (!empty($exist_credit)) {
                        $credit_receipt_date = 'CANCELLED';
                        $exist_credit_id = get_order_from_receipt($id_of_order);
                    }
                    if (!empty($exist_receipt_id)) {
                        $receipt_date = get_the_date('F j, Y', $exist_receipt_id[0]);
                    }

                    $log_for_order = get_post_meta($id_of_order, 'log_id_for_order', true);

                    $receipt_status_from_meta_url = '';
                    if (!empty($log_for_order)) {
                        $log_type = get_post_type($log_for_order);
                        if ($log_type == 'pr_log_automation') {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs_automation');
                        } else {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs');
                        }
                        $receipt_status_from_meta_url = $receipt_status_from_meta_url . '&order_log=' . $log_for_order;
                    } else {
                        $receipt_status_from_meta_url = '';
                    }
                    $transmission_failure_message = '';

                    switch ($transmission_failure) {
                        case 1:
                        case 3:
                            $transmission_failure_message = 'Αδυναμία σύνδεσης';
                            break;
                        case 2:
                            $transmission_failure_message = 'Επιτυχής Αποστολή';
                            break;
                        case 4:
                            $transmission_failure_message = 'Αδυναμία Αποστολής HTML';
                            break;
                        default:
                            // Handle the default case if needed
                            break;
                    }


                    $this->orders_array[$order_count]['order_id'] = $id_of_order;
                    $this->orders_array[$order_count]['order_date'] = $order_paid_date;
                    $this->orders_array[$order_count]['order_hour'] = $order_paid_hour;
                    $this->orders_array[$order_count]['order_client'] = $user ? $user->display_name : $user_full_name;
                    $this->orders_array[$order_count]['order_price'] = $order_total_price . ' ' . $currency_symbol;
                    $this->orders_array[$order_count]['transmission_failure'] = $transmission_failure_message;
                    $this->orders_array[$order_count]['accept_48'] = $failed_48;
                    $this->orders_array[$order_count]['order_status'] = $order_status;
                    $this->orders_array[$order_count]['payment_status'] = $payment_title;
                    $this->orders_array[$order_count]['receipt_date'] = $receipt_date;
                    $this->orders_array[$order_count]['credit_receipt_date'] = $credit_receipt_date;
                    $this->orders_array[$order_count]['receipt_status'] = /*$receipt_status_from_meta_text*/
                        $receipt_status_from_meta_url;
                    $this->orders_array[$order_count]['receipt_id'] = $exist_receipt_id ? $exist_receipt_id[0] : '';
                    $this->orders_array[$order_count]['credit_receipt_id'] = $exist_credit_id ? $exist_credit_id[0] : '';
                    $order_count++;
                }
            } elseif (is_array($primer_licenses) && in_array(2, $primer_licenses['wpModules']) && in_array(4, $primer_licenses['wpModules']) && !in_array(3, $primer_licenses['wpModules'])) {
                //$order_country = $order->get_billing_country();
                if ($order_country == 'GR' && $order_invoice_type !== 'invoice') {
                    $transmission_failure = get_post_meta($id_of_order, 'transmission_failure_check', true);
                    $failed_48 = get_post_meta($id_of_order, 'failed_48', true);
                    $order_date_paid = $order->get_date_paid();
                    $order_date_created = $order->get_date_created();
                    if (!empty($order_date_paid)) {
                        $order_paid_date = $order_date_paid->format('F j, Y');
                        $order_paid_hour = $order_date_paid->format('H:i:s');
                    } else {
                        $order_paid_date = $order_date_created->format('F j, Y');
                        $order_paid_hour = $order_date_created->format('H:i:s');
                    }
                    $order_total_price = $order->get_total();
                    $user = $order->get_user();
                    $user_first_name = $order->get_billing_first_name();
                    $user_last_name = $order->get_billing_last_name();
                    $user_full_name = $user_first_name . ' ' . $user_last_name;
                    $currency = $order->get_currency();
                    $currency_symbol = get_woocommerce_currency_symbol($currency);
                    $payment_title = $order->get_payment_method_title();
                    $order_status = $order->get_status();
                    $receipt_date = '';
                    $credit_receipt_date = '';
                    $exist_credit_id = '';
                    $exist_receipt_id = get_order_from_receipt($id_of_order);
                    $exist_credit = get_post_meta($id_of_order, 'cancelled', true);
                    if (!empty($exist_credit)) {
                        $credit_receipt_date = 'CANCELLED';
                        $exist_credit_id = get_order_from_receipt($id_of_order);
                    }
                    if (!empty($exist_receipt_id)) {
                        $receipt_date = get_the_date('F j, Y', $exist_receipt_id[0]);
                    }
                    $log_for_order = get_post_meta($id_of_order, 'log_id_for_order', true);
                    if (!empty($log_for_order)) {
                        $log_type = get_post_type($log_for_order);
                        if ($log_type == 'pr_log_automation') {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs_automation');
                        } else {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs');
                        }
                        $receipt_status_from_meta_url = $receipt_status_from_meta_url . '&order_log=' . $log_for_order;
                    } else {
                        $receipt_status_from_meta_url = '';
                    }
                    $transmission_failure_message = '';

                    switch ($transmission_failure) {
                        case 1:
                        case 3:
                            $transmission_failure_message = 'Αδυναμία σύνδεσης';
                            break;
                        case 2:
                            $transmission_failure_message = 'Επιτυχής Αποστολή';
                            break;
                        case 4:
                            $transmission_failure_message = 'Αδυναμία Αποστολής HTML';
                            break;
                        default:
                            // Handle the default case if needed
                            break;
                    }

                    $this->orders_array[$order_count]['order_id'] = $id_of_order;
                    $this->orders_array[$order_count]['order_date'] = $order_paid_date;
                    $this->orders_array[$order_count]['order_hour'] = $order_paid_hour;
                    $this->orders_array[$order_count]['order_client'] = $user ? $user->display_name : $user_full_name;
                    $this->orders_array[$order_count]['order_price'] = $order_total_price . ' ' . $currency_symbol;
                    $this->orders_array[$order_count]['order_status'] = $order_status;
                    $this->orders_array[$order_count]['transmission_failure'] = $transmission_failure_message;
                    $this->orders_array[$order_count]['accept_48'] = $failed_48;
                    $this->orders_array[$order_count]['payment_status'] = $payment_title;
                    $this->orders_array[$order_count]['receipt_date'] = $receipt_date;
                    $this->orders_array[$order_count]['credit_receipt_date'] = $credit_receipt_date;
                    $this->orders_array[$order_count]['receipt_status'] = /*$receipt_status_from_meta_text*/
                        $receipt_status_from_meta_url;
                    $this->orders_array[$order_count]['receipt_id'] = $exist_receipt_id ? $exist_receipt_id[0] : '';
                    $this->orders_array[$order_count]['credit_receipt_id'] = $exist_credit_id ? $exist_credit_id[0] : '';
                    $order_count++;
                }
            } elseif (is_array($primer_licenses) && in_array(3, $primer_licenses['wpModules']) && in_array(4, $primer_licenses['wpModules']) && !in_array(2, $primer_licenses['wpModules'])) {
                //$order_country = $order->get_billing_country();
                if (($order_country == 'GR' && $order_invoice_type !== 'receipt') || ($order_country !== 'GR')) {
                    $transmission_failure = get_post_meta($id_of_order, 'transmission_failure_check', true);
                    $failed_48 = get_post_meta($id_of_order, 'failed_48', true);
                    $order_date_paid = $order->get_date_paid();
                    $order_date_created = $order->get_date_created();
                    if (!empty($order_date_paid)) {
                        $order_paid_date = $order_date_paid->format('F j, Y');
                        $order_paid_hour = $order_date_paid->format('H:i:s');
                    } else {
                        $order_paid_date = $order_date_created->format('F j, Y');
                        $order_paid_hour = $order_date_created->format('H:i:s');
                    }
                    $order_total_price = $order->get_total();
                    $user = $order->get_user();
                    $user_first_name = $order->get_billing_first_name();
                    $user_last_name = $order->get_billing_last_name();
                    $user_full_name = $user_first_name . ' ' . $user_last_name;
                    $currency = $order->get_currency();
                    $currency_symbol = get_woocommerce_currency_symbol($currency);
                    $payment_title = $order->get_payment_method_title();
                    $order_status = $order->get_status();
                    $receipt_date = '';
                    $credit_receipt_date = '';
                    $exist_credit_id = '';
                    $exist_receipt_id = get_order_from_receipt($id_of_order);
                    $exist_credit = get_post_meta($id_of_order, 'cancelled', true);
                    if (!empty($exist_credit)) {
                        $credit_receipt_date = 'CANCELLED';
                        $exist_credit_id = get_order_from_receipt($id_of_order);
                    }
                    if (!empty($exist_receipt_id)) {
                        $receipt_date = get_the_date('F j, Y', $exist_receipt_id[0]);
                    }
                    $log_for_order = get_post_meta($id_of_order, 'log_id_for_order', true);
                    if (!empty($log_for_order)) {
                        $log_type = get_post_type($log_for_order);
                        if ($log_type == 'pr_log_automation') {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs_automation');
                        } else {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs');
                        }
                        $receipt_status_from_meta_url = $receipt_status_from_meta_url . '&order_log=' . $log_for_order;
                    } else {
                        $receipt_status_from_meta_url = '';
                    }

                    switch ($transmission_failure) {
                        case 1:
                        case 3:
                            $transmission_failure_message = 'Αδυναμία σύνδεσης';
                            break;
                        case 2:
                            $transmission_failure_message = 'Επιτυχής Αποστολή';
                            break;
                        case 4:
                            $transmission_failure_message = 'Αδυναμία Αποστολής HTML';
                            break;
                        default:
                            break;
                    }

                    $this->orders_array[$order_count]['order_id'] = $id_of_order;
                    $this->orders_array[$order_count]['order_date'] = $order_paid_date;
                    $this->orders_array[$order_count]['order_hour'] = $order_paid_hour;
                    $this->orders_array[$order_count]['order_client'] = $user ? $user->display_name : $user_full_name;
                    $this->orders_array[$order_count]['order_price'] = $order_total_price . ' ' . $currency_symbol;
                    $this->orders_array[$order_count]['order_status'] = $order_status;
                    $this->orders_array[$order_count]['transmission_failure'] = $transmission_failure_message;
                    $this->orders_array[$order_count]['accept_48'] = $failed_48;
                    $this->orders_array[$order_count]['payment_status'] = $payment_title;
                    $this->orders_array[$order_count]['receipt_date'] = $receipt_date;
                    $this->orders_array[$order_count]['credit_receipt_date'] = $credit_receipt_date;
                    $this->orders_array[$order_count]['receipt_status'] = /*$receipt_status_from_meta_text*/
                        $receipt_status_from_meta_url;
                    $this->orders_array[$order_count]['receipt_id'] = $exist_receipt_id ? $exist_receipt_id[0] : '';
                    $this->orders_array[$order_count]['credit_receipt_id'] = $exist_credit_id ? $exist_credit_id[0] : '';
                    $order_count++;
                }
            }
        }
		}
		return $this->orders_array;
	}

    /**
     *
     *
     * @param $page_number
     * @return array|mixed
     */
	public function get_users_from_orders($page_number) {
        $posts_per_page = 20;
        $order_count = 0;
        $paged = $page_number;
        $orders       = wc_get_orders( array('posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'limit' => 20));
        // Get all orders from WC_Order class
		foreach ( $orders as $order_id ) {
            $order = new WC_Order($order_id);
            $order_paid_date = null;
            $order_paid_hour = null;
            if (!empty($order->get_date_paid())) {
                $order_paid_date = date('F j, Y', $order->get_date_paid()->getTimestamp());
                $order_paid_hour = date('H:i:s', $order->get_date_paid()->getTimestamp());
            }
            $transmission_failure = get_post_meta($order_id, 'transmission_failure_check', true);
            $failed_48 = get_post_meta($order_id, 'failed_48', true);

            $order_total_price = $order->get_total();
            $user_id = $order->get_user_id();
            $user = $order->get_user();
            $currency = $order->get_currency();
            $currency_symbol = get_woocommerce_currency_symbol($currency);
            $payment_title = $order->get_payment_method_title();
            $order_status = $order->get_status();
            $receipt_status_from_meta_text = 'Not Issued';
            $receipt_status_from_meta = get_post_meta($order_id, 'receipt_status', true);
            if (!empty($receipt_status_from_meta) && $receipt_status_from_meta == 'issued') {
                $receipt_status_from_meta_text = 'Issued';
            }
            if (!empty($log_for_order)) {
                $log_type = get_post_type($log_for_order);
                if ($log_type == 'pr_log_automation') {
                    $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs_automation');
                } else {
                    $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs');
                }
                $receipt_status_from_meta_url = $receipt_status_from_meta_url . '&order_log=' . $log_for_order;
            } else {
                $receipt_status_from_meta_url = '';
            }

            $receipt_date = '';
            $credit_receipt_date = '';
            $exist_credit_id = '';
            $exist_receipt_id = get_order_from_receipt($order_id);
            $exist_credit = get_post_meta($order_id, 'cancelled', true);
            if (!empty($exist_credit)) {
                $credit_receipt_date = 'CANCELLED';
                $exist_credit_id = get_order_from_receipt($order_id);
            }
            if (!empty($exist_receipt_id)) {
                $receipt_date = get_the_date('F j, Y', $exist_receipt_id[0]);
            }
            $transmission_failure_message = '';
            if ($transmission_failure == 1 || $transmission_failure == 3) {
                $transmission_failure_message = 'Αδυναμία σύνδεσης';
            } else if ($transmission_failure == 2) {
                $transmission_failure_message = 'Επιτυχής Αποστολή';
            } else if ($transmission_failure == 4) {
                $transmission_failure_message = 'Αδυναμία Αποστολής HTML';
            }

            $this->orders_customers[$order_count]['order_id'] = $order_id;
            $this->orders_customers[$order_count]['order_date'] = $order_paid_date;
            $this->orders_customers[$order_count]['order_hour'] = $order_paid_hour;
            $this->orders_customers[$order_count]['order_client'] = $user ? $user->display_name : '';
            $this->orders_customers[$order_count]['order_client_id'] = $user_id ? $user_id : '0';
            $this->orders_customers[$order_count]['order_price'] = $order_total_price . ' ' . $currency_symbol;
            $this->orders_customers[$order_count]['order_status'] = $order_status;
            $this->orders_array[$order_count]['transmission_failure'] = $transmission_failure_message;
            $this->orders_array[$order_count]['accept_48'] = $failed_48;
            $this->orders_array[$order_count]['payment_status'] = $payment_title;
            $this->orders_array[$order_count]['receipt_date'] = $receipt_date;
            $this->orders_array[$order_count]['credit_receipt_date'] = $credit_receipt_date;
            $this->orders_array[$order_count]['receipt_status'] = /*$receipt_status_from_meta_text*/
                $receipt_status_from_meta_url;
            $this->orders_array[$order_count]['receipt_id'] = $exist_receipt_id ? $exist_receipt_id[0] : '';
            $this->orders_array[$order_count]['credit_receipt_id'] = $exist_credit_id ? $exist_credit_id[0] : '';
            $order_count++;
        }
		return $this->orders_customers;
	}


    public function get_with_params($page_number,$order_date_from, $order_date_to, $order_customer, $order_status, $order_receipt_status) {
        global $woocommerce;
        $posts_per_page = 20;
        $paged = $page_number;
        $primer_licenses = get_option('primer_licenses');
        $order_status = $_GET['primer_order_status'] ?? '';
        $search_term = trim( sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ) );
        if(is_array($order_status)) {
            array_map('sanitize_text_field', $order_status);
        }else{
            sanitize_text_field($_GET['primer_order_status']);
        }
        $order_args = array(
            'return' => 'ids',
            'limit' => 20,
            'order' => 'DESC',
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
        );

        $order_args_total = array(
            'return' => 'ids',
            'limit' => -1,
            'order' => 'DESC',
            'posts_per_page' => -1,  // Retrieve all order IDs
        );

        if($order_status != ''){
            $order_args['status'] = $order_status;
            $order_args_total['status'] = $order_status;
        }
        $order_args['numberposts'] = -1;
        if ( ! empty( $search_term ) ) {
            $order_args['post__in'] = array(intval(sanitize_text_field( wp_unslash( $_GET['s'] ))));
            $order_args_total['post__in'] = array(intval(sanitize_text_field(wp_unslash($_GET['s']))));
        }

        // receipt_status
        $order_args['meta_key'] = '_customer_user';
        $order_args['meta_value'] = sanitize_text_field($_GET['primer_order_client']);

        $order_args_total['meta_key'] = '_customer_user';
        $order_args_total['meta_value'] = sanitize_text_field($_GET['primer_order_client']);

        if (!empty($_GET['primer_receipt_status'])) {
                $order_args['meta_key'] = 'receipt_status';
                $order_args['meta_value'] = sanitize_text_field($_GET['primer_receipt_status']);

                $order_args_total['meta_key'] = 'receipt_status';
                $order_args_total['meta_value'] = sanitize_text_field($_GET['primer_receipt_status']);

        }
        $order_date_from = sanitize_text_field($_GET['order_date_from']);
        $order_date_to = sanitize_text_field($_GET['order_date_to']);
        $date_before = false;
        $date_after  = false;
        if ( ! empty( $_GET['order_date_from'] ) ) {
            $datetime    = wc_string_to_datetime( $order_date_from );
            $date_before = $datetime->date( 'Y-m-d' );
        }
        if ( ! empty( $_GET['order_date_to'] ) ) {
            $datetime   = wc_string_to_datetime( $order_date_to);
            $date_after = $datetime->date( 'Y-m-d' );
        }
        if ( $date_before && $date_after ) {
            $order_args['date_created'] = $date_before.'...'.$date_after;

            $order_args_total['date_created'] = $date_before . '...' . $date_after;

        } elseif ( $date_before ) {
            $order_args['date_created'] = '>=' . $date_before;

            $order_args_total['date_created'] = '>=' . $date_before;

        } elseif ( $date_after ) {
            $order_args['date_created'] = '<=' . $date_after;

            $order_args_total['date_created'] = '<=' . $date_after;
        }

        $query_orders = wc_get_orders($order_args);
        $orders       = $query_orders;
        $order_count = 0;

        $query_orders_total = wc_get_orders($order_args_total);
        $total_orders     = count($query_orders_total);

        foreach ( $orders as $order_id ) {
            $order = new WC_Order( $order_id );
            $order_invoice_type = get_post_meta($order_id, '_billing_invoice_type', true);
            if ((in_array(2, $primer_licenses['wpModules']))) {
                    $transmission_failure = get_post_meta($order_id, 'transmission_failure_check', true);
                    $failed_48 = get_post_meta($order_id, 'failed_48',true);

                    if (!empty($order->get_date_paid())) {
                        $order_paid_date = date( 'F j, Y', $order->get_date_paid()->getTimestamp());
                        $order_paid_hour = date( 'H:i:s', $order->get_date_paid()->getTimestamp());
                    } else {
                        $order_paid_date = date( 'F j, Y', $order->get_date_created()->getTimestamp());
                        $order_paid_hour = date( 'H:i:s', $order->get_date_created()->getTimestamp());
                    }

                    $order_total_price = $order->get_total();
                    $user_id   = $order->get_user_id();
                    $user      = $order->get_user();
                    $user_first_name = $order->get_billing_first_name();
                    $user_last_name = $order->get_billing_last_name();
                    $user_full_name = $user_first_name . ' ' . $user_last_name;
                    $currency      = $order->get_currency();
                    $currency_symbol = get_woocommerce_currency_symbol( $currency );
                    $payment_method = $order->get_payment_method();
                    $payment_title = $order->get_payment_method_title();
                    $order_status = $order->get_status();
                    $receipt_date = '';
                    $credit_receipt_date = '';
                    $exist_credit_id = '';
                    $exist_receipt_id = get_order_from_receipt($order_id);
                    $exist_credit = get_post_meta($order_id,'cancelled',true);
                    if (!empty($exist_credit)) {
                        $credit_receipt_date = 'CANCELLED';
                        $exist_credit_id = get_order_from_receipt($order_id);
                    }
                    if (!empty($exist_receipt_id)) {
                        $receipt_date = get_the_date('F j, Y', $exist_receipt_id[0]);
                    }
                    $log_for_order = get_post_meta($order_id, 'log_id_for_order', true);

                    $receipt_status_from_meta_url = '';
                    if (!empty($log_for_order)) {
                        $log_type = get_post_type($log_for_order);
                        if ($log_type == 'pr_log_automation') {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs_automation');
                        } else {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs');
                        }
                        $receipt_status_from_meta_url = $receipt_status_from_meta_url . '&order_log=' . $log_for_order;
                    } else {
                        $receipt_status_from_meta_url = '';
                    }
                    $transmission_failure_message='';
                    if($transmission_failure == 1 || $transmission_failure == 3){
                        $transmission_failure_message = 'Αδυναμία σύνδεσης';
                    }else if($transmission_failure == 2){
                        $transmission_failure_message = 'Επιτυχής Αποστολή';
                    }else if($transmission_failure == 4){
                        $transmission_failure_message = 'Αδυναμία Αποστολής HTML';
                    }

                    $this->orders_array[$order_count]['order_id'] = $order_id;
                    $this->orders_array[$order_count]['order_date'] = $order_paid_date;
                    $this->orders_array[$order_count]['order_hour'] = $order_paid_hour;
                    $this->orders_array[$order_count]['order_client'] = $user ? $user->display_name : $user_full_name;
                    $this->orders_array[$order_count]['order_price'] = $order_total_price . ' ' .$currency_symbol;
                    $this->orders_array[$order_count]['order_status'] = $order_status;
                    $this->orders_array[$order_count]['transmission_failure'] = $transmission_failure_message;
                    $this->orders_array[$order_count]['accept_48'] = $failed_48;
                    $this->orders_array[$order_count]['payment_status'] = $payment_title;
                    $this->orders_array[$order_count]['receipt_date'] = $receipt_date;
                    $this->orders_array[$order_count]['credit_receipt_date'] = $credit_receipt_date;
                    $this->orders_array[$order_count]['receipt_status'] = /*$receipt_status_from_meta_text*/ $receipt_status_from_meta_url;
                    $this->orders_array[$order_count]['receipt_id'] = $exist_receipt_id ? $exist_receipt_id[0] : '';
                    $this->orders_array[$order_count]['credit_receipt_id'] = $exist_credit_id ? $exist_credit_id[0] : '';
                $order_count++;
            }

            else if ((in_array(3, $primer_licenses['wpModules']))) {
                $order_country = $order->get_billing_country();
                if ($order_country == 'GR') {
                    $transmission_failure = get_post_meta($order_id, 'transmission_failure_check', true);
                    $failed_48 = get_post_meta($order_id, 'failed_48', true);

                    if (!empty($order->get_date_paid())) {
                        $order_paid_date = date('F j, Y', $order->get_date_paid()->getTimestamp());
                        $order_paid_hour = date('H:i:s', $order->get_date_paid()->getTimestamp());
                    } else {
                        $order_paid_date = date('F j, Y', $order->get_date_created()->getTimestamp());
                        $order_paid_hour = date('H:i:s', $order->get_date_created()->getTimestamp());
                    }

                    $order_total_price = $order->get_total();
                    $user_id = $order->get_user_id();
                    $user = $order->get_user();
                    $user_first_name = $order->get_billing_first_name();
                    $user_last_name = $order->get_billing_last_name();
                    $user_full_name = $user_first_name . ' ' . $user_last_name;
                    $currency = $order->get_currency();
                    $currency_symbol = get_woocommerce_currency_symbol($currency);
                    $payment_method = $order->get_payment_method();
                    $payment_title = $order->get_payment_method_title();
                    $order_status = $order->get_status();
                    $receipt_date = '';
                    $credit_receipt_date = '';
                    $exist_credit_id = '';
                    $exist_receipt_id = get_order_from_receipt($order_id);
                    $exist_credit = get_post_meta($order_id, 'cancelled', true);
                    if (!empty($exist_credit)) {
                        $credit_receipt_date = 'CANCELLED';
                        $exist_credit_id = get_order_from_receipt($order_id);
                    }
                    if (!empty($exist_receipt_id)) {
                        $receipt_date = get_the_date('F j, Y', $exist_receipt_id[0]);
                    }
                    $log_for_order = get_post_meta($order_id, 'log_id_for_order', true);
                    $receipt_status_from_meta_url = '';
                    if (!empty($log_for_order)) {
                        $log_type = get_post_type($log_for_order);
                        if ($log_type == 'pr_log_automation') {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs_automation');
                        } else {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs');
                        }
                        $receipt_status_from_meta_url = $receipt_status_from_meta_url . '&order_log=' . $log_for_order;
                    } else {
                        $receipt_status_from_meta_url = '';
                    }
                    $transmission_failure_message = '';
                    if ($transmission_failure == 1 || $transmission_failure == 3) {
                        $transmission_failure_message = 'Αδυναμία σύνδεσης';
                    } else if ($transmission_failure == 2) {
                        $transmission_failure_message = 'Επιτυχής Αποστολή';
                    } else if ($transmission_failure == 4) {
                        $transmission_failure_message = 'Αδυναμία Αποστολής HTML';
                    }

                    $this->orders_array[$order_count]['order_id'] = $order_id;
                    $this->orders_array[$order_count]['order_date'] = $order_paid_date;
                    $this->orders_array[$order_count]['order_hour'] = $order_paid_hour;
                    $this->orders_array[$order_count]['order_client'] = $user ? $user->display_name : $user_full_name;
                    $this->orders_array[$order_count]['order_price'] = $order_total_price . ' ' . $currency_symbol;
                    $this->orders_array[$order_count]['order_status'] = $order_status;
                    $this->orders_array[$order_count]['transmission_failure'] = $transmission_failure_message;
                    $this->orders_array[$order_count]['accept_48'] = $failed_48;
                    $this->orders_array[$order_count]['payment_status'] = $payment_title;
                    $this->orders_array[$order_count]['receipt_date'] = $receipt_date;
                    $this->orders_array[$order_count]['credit_receipt_date'] = $credit_receipt_date;
                    $this->orders_array[$order_count]['receipt_status'] = /*$receipt_status_from_meta_text*/
                        $receipt_status_from_meta_url;
                    $this->orders_array[$order_count]['receipt_id'] = $exist_receipt_id ? $exist_receipt_id[0] : '';
                    $this->orders_array[$order_count]['credit_receipt_id'] = $exist_credit_id ? $exist_credit_id[0] : '';
                    $order_count++;
                }
            }

            else if ((in_array(4, $primer_licenses['wpModules']))) {
                    $transmission_failure = get_post_meta($order_id, 'transmission_failure_check', true);
                    $failed_48 = get_post_meta($order_id, 'failed_48', true);

                    if (!empty($order->get_date_paid())) {
                        $order_paid_date = date('F j, Y', $order->get_date_paid()->getTimestamp());
                        $order_paid_hour = date('H:i:s', $order->get_date_paid()->getTimestamp());
                    } else {
                        $order_paid_date = date('F j, Y', $order->get_date_created()->getTimestamp());
                        $order_paid_hour = date('H:i:s', $order->get_date_created()->getTimestamp());
                    }

                    $order_total_price = $order->get_total();
                    $user_id = $order->get_user_id();
                    $user = $order->get_user();
                    $user_first_name = $order->get_billing_first_name();
                    $user_last_name = $order->get_billing_last_name();
                    $user_full_name = $user_first_name . ' ' . $user_last_name;
                    $currency = $order->get_currency();
                    $currency_symbol = get_woocommerce_currency_symbol($currency);
                    $payment_method = $order->get_payment_method();
                    $payment_title = $order->get_payment_method_title();
                    $order_status = $order->get_status();

                    $receipt_date = '';
                    $credit_receipt_date = '';
                    $exist_credit_id = '';
                    $exist_receipt_id = get_order_from_receipt($order_id);
                    $exist_credit = get_post_meta($order_id, 'cancelled', true);
                    if (!empty($exist_credit)) {
                        $credit_receipt_date = 'CANCELLED';
                        $exist_credit_id = get_order_from_receipt($order_id);
                    }
                    if (!empty($exist_receipt_id)) {
                        $receipt_date = get_the_date('F j, Y', $exist_receipt_id[0]);
                    }

                    $log_for_order = get_post_meta($order_id, 'log_id_for_order', true);

                    $receipt_status_from_meta_url = '';
                    if (!empty($log_for_order)) {
                        $log_type = get_post_type($log_for_order);
                        if ($log_type == 'pr_log_automation') {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs_automation');
                        } else {
                            $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs');
                        }
                        $receipt_status_from_meta_url = $receipt_status_from_meta_url . '&order_log=' . $log_for_order;
                    } else {
                        $receipt_status_from_meta_url = '';
                    }
                    $transmission_failure_message = '';
                    if ($transmission_failure == 1 || $transmission_failure == 3) {
                        $transmission_failure_message = 'Αδυναμία σύνδεσης';
                    } else if ($transmission_failure == 2) {
                        $transmission_failure_message = 'Επιτυχής Αποστολή';
                    } else if ($transmission_failure == 4) {
                        $transmission_failure_message = 'Αδυναμία Αποστολής HTML';
                    }

                    $this->orders_array[$order_count]['order_id'] = $order_id;
                    $this->orders_array[$order_count]['order_date'] = $order_paid_date;
                    $this->orders_array[$order_count]['order_hour'] = $order_paid_hour;
                    $this->orders_array[$order_count]['order_client'] = $user ? $user->display_name : $user_full_name;
                    $this->orders_array[$order_count]['order_price'] = $order_total_price . ' ' . $currency_symbol;
                    $this->orders_array[$order_count]['order_status'] = $order_status;
                    $this->orders_array[$order_count]['transmission_failure'] = $transmission_failure_message;
                    $this->orders_array[$order_count]['accept_48'] = $failed_48;
                    $this->orders_array[$order_count]['payment_status'] = $payment_title;
                    $this->orders_array[$order_count]['receipt_date'] = $receipt_date;
                    $this->orders_array[$order_count]['credit_receipt_date'] = $credit_receipt_date;
                    $this->orders_array[$order_count]['receipt_status'] = /*$receipt_status_from_meta_text*/
                        $receipt_status_from_meta_url;
                    $this->orders_array[$order_count]['receipt_id'] = $exist_receipt_id ? $exist_receipt_id[0] : '';
                    $this->orders_array[$order_count]['credit_receipt_id'] = $exist_credit_id ? $exist_credit_id[0] : '';
                    $order_count++;
            }
        }
        $get_orders_list = array(
            'orders' => $this->orders_array,
            'total_orders' => $total_orders
        );

        return $get_orders_list;
        //$this->orders_array['total']= $total_orders;
        //return $this->orders_array;
    }
}

/**
 * Handle a custom 'customvar' query var to get orders with the 'customvar' meta.
 * @param array $query - Args for WP_Query.
 * @param array $query_vars - Query vars from WC_Order_Query.
 * @return array modified $query
 */
function handle_custom_query_var( $query, $query_vars ) {
	if ( ! empty($query_vars['receipt_status']) ) {
		$query['meta_query'][] = array(
			'key' => 'receipt_status',
			'value' => esc_attr($query_vars['receipt_status'] ),
		);
	}

	return $query;
}
add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', 'handle_custom_query_var', 10, 2 );


function get_order_from_receipt($order_id) {
    $invoice_ids = array();

    $receipt_args = array(
        'posts_per_page' => -1,
        'post_type' => 'primer_receipt',
        'meta_key' => 'order_id_to_receipt',
        'meta_value' => $order_id,
    );

    $receipt_query = new WP_Query($receipt_args);

    if ($receipt_query->have_posts()):
        while ($receipt_query->have_posts()):
            $receipt_query->the_post();
            $invoice_ids[] = get_the_ID();
        endwhile;
    endif;
    wp_reset_postdata();

    return $invoice_ids;
}
