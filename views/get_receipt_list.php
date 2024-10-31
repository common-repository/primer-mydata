<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }

class PrimerReceiptList {

    public $receipt_array = array();

    public $receipt_params_array = array();

    public $receipt_customers = array();

    public $receipt_date_range = array();


    public function get($page_number) {
        $posts_per_page = 20;
        $paged = $page_number;

        $receipt_args = array(
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'post_type' => 'primer_receipt',
            'post_status' => 'publish'
        );

        $receipt_query = new WP_Query($receipt_args);
        $receipt_array = array();

        if ($receipt_query->have_posts()) {
            while ($receipt_query->have_posts()) {
                $receipt_query->the_post();

                $receipt_status_text = '';
                $receipt_status = get_post_meta(get_the_ID(), 'receipt_status', true);
                switch ($receipt_status) {
                    case 'issued':
                        $receipt_status_text = 'Issued';
                        break;
                    case 'not_issued':
                        $receipt_status_text = 'Not Issued';
                        break;
                }

                $receipt_in_log = '';
                $receipt_log_status = get_post_meta(get_the_ID(), 'exist_error_log', true);
                $receipt_in_log = __('Log', 'primer');

                $order_from_invoice = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
                $invoice_client = get_post_meta(get_the_ID(), 'receipt_client', true);

                if (!empty($order_from_invoice)) {
                    $total_order = wc_get_order($order_from_invoice);
                    $user_first_name = get_post_meta($order_from_invoice, '_billing_first_name', true);
                    $user_last_name = get_post_meta($order_from_invoice, '_billing_last_name', true);
                    $user_full_name = $user_first_name . ' ' . $user_last_name;
                    if (empty($invoice_client)) {
                        $invoice_client = $user_full_name;
                    }
                }

                $credit_receipt = '';
                $cancelled_receipt = '';
                $log_for_order = get_post_meta(get_the_ID(), 'log_id_for_order', true);
                $is_credit_receipt = get_post_meta(get_the_ID(), 'credit_receipt', true);
                if (!empty($is_credit_receipt)) {
                    $log_for_order = get_post_meta(get_the_ID(), 'credit_log_id_for_order', true);
                    $credit_receipt = 'yes';
                }
                $is_cancelled_receipt = get_post_meta(get_the_ID(), 'cancelled', true);
                if (!empty($is_cancelled_receipt)) {
                    $cancelled_receipt = 'yes';
                }

                $receipt_status_from_meta_url = '';
                if (!empty($log_for_order)) {
                    $log_type = get_post_type($log_for_order);
                    if ($log_type == 'pr_log_automation') {
                        $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs_automation');
                    } else {
                        $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs');
                    }
                    $receipt_status_from_meta_url .= '&order_log=' . $log_for_order;
                }

                $receipt_number = get_post_meta(get_the_ID(), '_primer_receipt_number', true);

                $receipt_array[] = array(
                    'receipt_id' => get_the_ID(),
                    'receipt_date' => get_the_date(),
                    'receipt_hour' => get_the_time(),
                    'receipt_client' => $invoice_client,
                    //'receipt_product' => get_post_meta(get_the_ID(), 'receipt_product', true),
                    'receipt_price' => get_post_meta(get_the_ID(), 'receipt_price', true),
                    'receipt_status' => $receipt_status_text,
                    'receipt_error_status' => $receipt_status_from_meta_url,
                    'credit_receipt' => $credit_receipt,
                    'cancelled_receipt' => $cancelled_receipt,
                );
            }
        }
        wp_reset_postdata();

        return $receipt_array;
    }


    public function get_users_from_receipts() {
        $receipt_args = array(
            'posts_per_page' => -1,
            'post_type' => 'primer_receipt',
            'post_status' => 'publish',
            'fields' => 'ids',
        );

        $receipt_query = get_posts($receipt_args);
        $receipt_customers = array();

        foreach ($receipt_query as $receipt_id) {
            $user_display_name = get_post_meta($receipt_id, 'receipt_client_id', true);
            $order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);

            $customer_full_name = get_post_meta($receipt_id, 'receipt_client', true);
            if (empty($customer_full_name) && !empty($order_id)) {
                $order = wc_get_order($order_id);
                $order_user_first_name = $order->get_billing_first_name();
                $order_user_last_name = $order->get_billing_last_name();
                $customer_full_name = $order_user_first_name . ' ' . $order_user_last_name;
            }

            $user_id = !empty($user_display_name) ? $user_display_name : 0;
            $user_data = get_user_by('ID', $user_id);

            $receipt_customers[] = array(
                'receipt_client' => $customer_full_name,
                'receipt_client_id' => $user_id,
            );
        }

        return $receipt_customers;
    }


    public function get_dates_from_receipts() {
        $receipt_args = array(
            'posts_per_page' => -1,
            'post_type' => 'primer_receipt',
            'post_status' => 'publish',
            'fields' => 'ids',
        );

        $receipt_query = get_posts($receipt_args);
        $receipt_date_range = array();

        foreach ($receipt_query as $receipt_id) {
            $receipt_date_range[] = strtotime(get_the_date('F j, Y H:i:s', $receipt_id));
        }

        return $receipt_date_range;
    }


    public function get_with_params($page_number, $receipt_date_from, $receipt_date_to, $receipt_customer, $receipt_status, $receipt_type) {
        $receipt_status = isset($_GET['primer_receipt_status']) ? sanitize_text_field($_GET['primer_receipt_status']) : '';
        $receipt_type = isset($_GET['primer_receipt_type']) ? sanitize_text_field($_GET['primer_receipt_type']) : '';
        $receipt_customer = isset($_GET['primer_receipt_client']) ? sanitize_text_field($_GET['primer_receipt_client']) : '';
        $receipt_date_from = isset($_GET['receipt_date_from']) ? sanitize_text_field($_GET['receipt_date_from']) : '';
        $receipt_date_to = isset($_GET['receipt_date_to']) ? sanitize_text_field($_GET['receipt_date_to']) : '';
        $posts_per_page = 20;
        $paged = $page_number;
        $search_term = trim(sanitize_text_field(wp_unslash($_GET['s'] ?? '')));
        $meta_values = array();

        $receipt_args = array(
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'limit' => 20,
            'post_type' => 'primer_receipt',
            'post_status' => 'publish',
        );

        $receipt_args_total = array(
            'posts_per_page' => -1,
            'limit' => -1,
            'post_type' => 'primer_receipt',
            'post_status' => 'publish'
        );


        if (!empty($receipt_status)) {
            $meta_values['receipt_status'][] = $receipt_status;
        }

        if (!empty($receipt_type)) {
            $meta_values['receipt_type'][] = $receipt_type;
        }

        if (!empty($receipt_customer)) {
            $meta_values['receipt_client'][] = $receipt_customer;
        }

        if (!empty($meta_values)) {
            $receipt_args['meta_query']['relation'] = 'AND';
            $receipt_args_total['meta_query']['relation'] = 'AND';
            foreach ($meta_values as $key => $meta_value) {
                $receipt_args['meta_query'][] = array(
                    'key' => $key,
                    'value' => $meta_value,
                    'compare' => 'IN',
                );
                $receipt_args_total['meta_query'][] = array(
                    'key' => $key,
                    'value' => $meta_value,
                    'compare' => 'IN',
                );
            }
        }

        if (!empty($receipt_date_from) || !empty($receipt_date_to)) {
            $receipt_args['date_query']['relation'] = 'AND';
            $receipt_args_total['date_query']['relation'] = 'AND';
            $receipt_args['date_query'][] = array(
                'after' => $receipt_date_from,
                'before' => $receipt_date_to,
                'compare' => 'BETWEEN',
                'inclusive' => true,
            );
            $receipt_args_total['date_query'][] = array(
                'after' => $receipt_date_from,
                'before' => $receipt_date_to,
                'compare' => 'BETWEEN',
                'inclusive' => true,
            );
        }
        $i = 0;
        if ( ! empty( $search_term ) ) {
            // $receipt_args['order_id_to_receipt'] = array(intval(sanitize_text_field( wp_unslash( $_GET['s'] ))));
            $receipt_args['meta_query'][$i + 1]['relation'] = 'OR';
            $receipt_args['meta_query'][$i + 1][] = [
                'key' => 'order_id_to_receipt',
                'value' => array(intval(sanitize_text_field( wp_unslash( $_GET['s'] )))),
                'compare' => 'IN'];
            $receipt_args['meta_query'][$i + 1][] = [
                'key' => '_primer_receipt_number',
                'value' => array(intval(sanitize_text_field( wp_unslash( $_GET['s'] )))),
                'compare' => 'IN'];

        }

        if (!empty($search_term)) {
            $receipt_args_total['meta_query'][$i + 1]['relation'] = 'OR';
            $receipt_args_total['meta_query'][$i + 1][] = array(
                'key' => 'order_id_to_receipt',
                'value' => array(intval(sanitize_text_field(wp_unslash($_GET['s'])))),
                'compare' => 'IN',
            );
            $receipt_args_total['meta_query'][$i + 1][] = array(
                'key' => '_primer_receipt_number',
                'value' => array(intval(sanitize_text_field(wp_unslash($_GET['s'])))),
                'compare' => 'IN',
            );
        }

        $receipt_args['fields'] = 'ids';
        $receipt_query = get_posts($receipt_args);
        $receipt_params_array = array();

        $receipt_args_total['fields'] = 'ids';
        $receipt_query_total = get_posts($receipt_args_total);

        $total_receipt = count($receipt_query_total);



        $receipt_params_array = array();
        foreach ($receipt_query as $receipt_id) {
            $receipt_status = get_post_meta($receipt_id, 'receipt_status', true);
            $receipt_status_text = '';

            switch ($receipt_status) {
                case 'issued':
                    $receipt_status_text = 'Issued';
                    break;
                case 'not_issued':
                    $receipt_status_text = 'Not Issued';
                    break;
            }

            $order_from_invoice = get_post_meta($receipt_id, 'order_id_to_receipt', true);
            $invoice_client = get_post_meta($receipt_id, 'receipt_client', true);
            $user_first_name = '';
            $user_last_name = '';

            if (!empty($total_order)) {
                $total_order = wc_get_order($order_from_invoice);
                $user_first_name = $total_order->get_billing_first_name();
                $user_last_name = $total_order->get_billing_last_name();
            }

            $user_full_name = $user_first_name . ' ' . $user_last_name;

            if (empty($invoice_client)) {
                $invoice_client = $user_full_name;
            }
            $order_id = get_post_meta($receipt_id,'order_id_to_receipt', true);
            $log_id = get_post_meta($order_id,'log_id_for_order', true);



            $credit_receipt = '';
            $cancelled_receipt = '';
            $log_for_order = get_post_meta(get_the_ID(), 'log_id_for_order', true);
            $is_credit_receipt = get_post_meta(get_the_ID(), 'credit_receipt', true);
            if (!empty($is_credit_receipt)) {
                $log_id = get_post_meta(get_the_ID(), 'credit_log_id_for_order', true);
                $credit_receipt = 'yes';
            }
            $is_cancelled_receipt = get_post_meta(get_the_ID(), 'cancelled', true);
            if (!empty($is_cancelled_receipt)) {
                $cancelled_receipt = 'yes';
            }



            $receipt_status_from_meta_url = '';
            if (!empty($log_id)) {
                $log_type = get_post_type($log_id);
                if ($log_type == 'pr_log_automation') {
                    $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs_automation');
                } else {
                    $receipt_status_from_meta_url = admin_url('admin.php?page=primer_receipts_logs');
                }
                $receipt_status_from_meta_url .= '&order_log=' . $log_id;
            }

            $receipt_params_array[] = array(
                'receipt_id' => $receipt_id,
                'receipt_date' => get_the_date('', $receipt_id),
                'receipt_hour' => get_the_time('', $receipt_id),
                'receipt_client' => $invoice_client,
                //'receipt_product' => get_post_meta($receipt_id, 'receipt_product', true),
                'receipt_price' => get_post_meta($receipt_id, 'receipt_price', true),
                'receipt_status' => $receipt_status_text,
                'receipt_error_status' => $receipt_status_from_meta_url,
                'credit_receipt' => $credit_receipt,
                'cancelled_receipt' => $cancelled_receipt,
            );
        }
        $receipt_params_array ['totals']['totals'] = $total_receipt ;
        return $receipt_params_array;
    }

}
