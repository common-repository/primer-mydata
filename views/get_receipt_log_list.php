<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }

class PrimerReceiptLogList {
	public $receipt_log_array = array();
	public $receipt_log_params_array = array();
	public $receipt_log_customers = array();

	public function get() {
		$order_log = isset($_GET['order_log']) ? sanitize_text_field($_GET['order_log']) : '';

		$receipt_log_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt_log',
		);

		if (!empty($order_log)) {
			$receipt_log_args['post__in'] = array($order_log);
		}

		$receipt_log_query = new WP_Query( $receipt_log_args );
		$receipt_log_count = 0;

		if ($receipt_log_query->have_posts()):
			while ($receipt_log_query->have_posts()):
				$receipt_log_query->the_post();
                $invoice_no = "" ;
				$receipt_log_status_text = '';
				$receipt_log_status = get_post_meta(get_the_ID(), 'receipt_log_status', true);
				switch ($receipt_log_status) {
					case 'issued':
						$receipt_log_status_text = 'Yes';
						break;
					case 'not_issued':
						$receipt_log_status_text = 'No';
						break;
					default:
						$receipt_log_status_text = 'No';
				}

				$receipt_log_email_status_text = '';
				$receipt_log_email_status = get_post_meta(get_the_ID(), 'receipt_log_email', true);
				switch ($receipt_log_email_status) {
					case 'sent':
						$receipt_log_email_status_text = 'Yes';
						break;
					case 'not_sent':
						$receipt_log_email_status_text = 'No';
						break;
					default:
						$receipt_log_email_status_text = 'No';
				}

				$receipt_log_email_error = get_post_meta(get_the_ID(), 'receipt_log_email_error', true);

				$receipt_log_error = get_post_meta(get_the_ID(), 'receipt_log_error', true);
				if (is_array($receipt_log_error)) {
					$receipt_log_error = json_encode($receipt_log_error);
				}

				$invoice_log_id = get_post_meta(get_the_ID(), 'receipt_log_invoice_id', true);
				$invoice_log_date = get_post_meta(get_the_ID(), 'receipt_log_invoice_date', true);

				$log_order_id = get_post_meta(get_the_ID(), 'receipt_log_order_id', true);
				global $wpdb, $woocommerce;
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

				$order_from_invoice_log = get_post_meta(get_the_ID(), 'receipt_log_order_id', true);

				$invoice_log_client = get_post_meta(get_the_ID(), 'receipt_log_client', true);

				$total_order = wc_get_order( $order_from_invoice_log );
				$total_order_data = [];
				if (is_a($total_order, 'WC_Order')) {
					$total_order_data = $total_order->get_data();
				}

				$user_first_name = isset($total_order_data['billing']) ? $total_order_data['billing']['first_name'] : '';
				$user_last_name = isset($total_order_data['billing']['last_name']) ? $total_order_data['billing']['last_name'] : '';

				$user_full_name = $user_first_name . ' ' . $user_last_name;

				if (empty($invoice_log_client)) {
					$invoice_log_client = $user_full_name;
				}

                $receipt_log_invoice_id= get_post_meta( get_the_ID(), 'receipt_log_invoice_id', true );
                if ( get_post_meta($receipt_log_invoice_id , '_primer_receipt_series', true) == "EMPTY" )
                {
                    $invoice_no = get_post_meta($receipt_log_invoice_id , '_primer_receipt_number', true);
                }
                else
                {
                    $invoice_no = get_post_meta($receipt_log_invoice_id , '_primer_receipt_series', true) . " " . get_post_meta($receipt_log_invoice_id , '_primer_receipt_number', true);
                }
				$this->receipt_log_array[$receipt_log_count]['receipt_log_order_id'] = get_post_meta(get_the_ID(), 'receipt_log_order_id', true);
				$this->receipt_log_array[$receipt_log_count]['receipt_log_order_date'] = get_post_meta(get_the_ID(), 'receipt_log_order_date', true);
				$this->receipt_log_array[$receipt_log_count]['receipt_log_invoice_id'] = $invoice_no;
                //$this->receipt_log_array[$receipt_log_count]['receipt_log_invoice_id'] = $invoice_log_id;
				$this->receipt_log_array[$receipt_log_count]['receipt_log_invoice_date'] = $invoice_log_date;
				$this->receipt_log_array[$receipt_log_count]['receipt_log_client'] = $invoice_log_client;
				$this->receipt_log_array[$receipt_log_count]['receipt_log_status'] = $receipt_log_status_text;
				$this->receipt_log_array[$receipt_log_count]['receipt_log_email'] = $receipt_log_email_status_text;
				$this->receipt_log_array[$receipt_log_count]['receipt_log_error'] = $receipt_log_error;
				$this->receipt_log_array[$receipt_log_count]['receipt_log_email_error'] = $receipt_log_email_error;
				$receipt_log_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_log_array;
	}

	public function get_with_params($receipt_log_error, $receipt_log_issue) {

		$meta_values = array();

		$receipt_log_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt_log',
		);

		if (!empty($receipt_log_issue)) {
			$meta_values['receipt_log_total_status'][] = 'only_issued';
		}
		if (!empty($receipt_log_error)) {
			$meta_values['receipt_log_total_status'][] = 'only_errors';
		}

		if (!empty($meta_values)) {
			$receipt_log_args['meta_query']['relation'] = 'AND';
			$i = 0;
			foreach ( $meta_values as $key => $meta_value ) {
				$i++;
				$receipt_log_args['meta_query'][$i]['key'] = $key;
				$receipt_log_args['meta_query'][$i]['value'] = $meta_value;
			}
		}

		$receipt_log_query = new WP_Query( $receipt_log_args );
		$receipt_count = 0;

		if ($receipt_log_query->have_posts()):
			while ($receipt_log_query->have_posts()):
				$receipt_log_query->the_post();

				$receipt_log_status_text = '';
				$receipt_log_status = get_post_meta(get_the_ID(), 'receipt_log_status', true);
				switch ($receipt_log_status) {
					case 'issued':
						$receipt_log_status_text = 'Yes';
						break;
					case 'not_issued':
						$receipt_log_status_text = 'No';
						break;
					default:
						$receipt_log_status_text = 'No';
				}

				$receipt_log_email_status_text = '';
				$receipt_log_email_status = get_post_meta(get_the_ID(), 'receipt_log_email', true);
				switch ($receipt_log_email_status) {
					case 'sent':
						$receipt_log_email_status_text = 'Yes';
						break;
					case 'not_sent':
						$receipt_log_email_status_text = 'No';
						break;
					default:
						$receipt_log_email_status_text = 'No';
				}

				$invoice_log_id = get_post_meta(get_the_ID(), 'receipt_log_invoice_id', true);
				$invoice_log_date = get_post_meta(get_the_ID(), 'receipt_log_invoice_date', true);

				$log_order_id = get_post_meta(get_the_ID(), 'receipt_log_order_id', true);
				global $wpdb;
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


				$order_from_invoice_log = get_post_meta(get_the_ID(), 'receipt_log_order_id', true);

				$invoice_log_client = get_post_meta(get_the_ID(), 'receipt_log_client', true);
                $receipt_log_error = get_post_meta(get_the_ID(), 'receipt_log_error', true);
                if (is_array($receipt_log_error)) {
                    $receipt_log_error = json_encode($receipt_log_error);
                }

				$total_order = wc_get_order( $order_from_invoice_log );
				//$user_first_name = $total_order->get_billing_first_name();
				//$user_last_name = $total_order->get_billing_last_name();

				//$user_full_name = $user_first_name . ' ' . $user_last_name;

				if (empty($invoice_log_client)) {
					//$invoice_log_client = $user_full_name;
				}


				$receipt_log_email_error = get_post_meta(get_the_ID(), 'receipt_log_email_error', true);

				$this->receipt_log_params_array[$receipt_count]['receipt_log_order_id'] = get_post_meta(get_the_ID(), 'receipt_log_order_id', true);
				$this->receipt_log_params_array[$receipt_count]['receipt_log_order_date'] = get_post_meta(get_the_ID(), 'receipt_log_order_date', true);

				$this->receipt_log_params_array[$receipt_count]['receipt_log_invoice_id'] = $invoice_log_id;
				$this->receipt_log_params_array[$receipt_count]['receipt_log_invoice_date'] = $invoice_log_date;

				$this->receipt_log_params_array[$receipt_count]['receipt_log_client'] = $invoice_log_client;
				$this->receipt_log_params_array[$receipt_count]['receipt_log_status'] = $receipt_log_status_text;
				$this->receipt_log_params_array[$receipt_count]['receipt_log_email'] = $receipt_log_email_status_text;
				$this->receipt_log_params_array[$receipt_count]['receipt_log_error'] = $receipt_log_error;
				$this->receipt_log_params_array[$receipt_count]['receipt_log_email_error'] = $receipt_log_email_error;
				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_log_params_array;
	}
}
