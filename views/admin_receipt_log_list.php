<form id="tables-receipt-log-filter" method="get">
	<!-- For plugins, we also need to ensure that the form posts back to our current page -->
	<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
	<!-- Now we can render the completed list table -->
	<div id="primer_receipt_log_table">
		<?php $this->display(); ?>
	</div>
	<div class="submit convert_orders convert_receipts convert_receipts_logs">
		<?php
        // Check if 'order_log' parameter is set and not empty
        if (isset($_GET['order_log'])) {
			if (!empty($_GET['order_log'])) {

				$log_id = $_GET['order_log'];

                $primer_license_data = get_option('primer_licenses');
                $user_vat = $primer_license_data['companyVatNumber'];
				$get_post_json = get_post_meta($log_id, 'json_send_to_api', true);
				$parent_post = get_post_meta($log_id, 'receipt_log_order_id', true);
				$html_request = get_post_meta($parent_post, 'html_body_post_fields', true);
				$order_id_from_receipt = get_post_meta($parent_post, 'order_id_from_receipt', true);
				$receipt_status = get_post_meta($order_id_from_receipt, 'receipt_status', true);
				$total_vat_number = "$user_vat";
				$invoice_uid = get_post_meta($order_id_from_receipt, 'response_invoice_uid', true);
                $invoice_mark = get_post_meta($order_id_from_receipt, 'response_invoice_mark', true);
				$file_name = '';
				$file_name_link = '';
				if (!empty($invoice_uid)) {
					$parent_link = home_url();
					$upload_dir = wp_upload_dir()['basedir'];
					$file_name_server = $upload_dir . '/exported_html_files/'.$total_vat_number.$invoice_mark.'_html.zip';
					$file_name = $parent_link . $upload_dir . '/exported_html_files/'.$total_vat_number.$invoice_mark.'_html.zip';
					if (file_exists( $file_name_server )) {
						$file_name_link = $file_name;
					} else {
						$file_name_link = '#';
					}
				}
				if (!empty($get_post_json)) {
					$json_file = $get_post_json;
					?>
					<input type="hidden" data-parent_order="<?php echo esc_attr($parent_post); ?>" value="<?php echo esc_attr($json_file); ?>">
					<button type="button" name="download_json" id="primer-download-order-json" class="button-primary" style="margin-left: 10px;"><?php _e('Download JSON', 'primer'); ?></button>
				<?php } ?>
				<?php
					if (!empty($html_request)) { ?>
						<input type="hidden" data-parent_order="<?php echo esc_attr($parent_post); ?>" value="<?php echo esc_attr($html_request); ?>">
						<button type="button" name="download_html_json" id="primer-download-html-json" class="button-primary" style="margin-left: 10px;"><?php _e('Download HTML JSON', 'primer'); ?></button>
						<?php if ($receipt_status == 'issued') { ?>
				<a href="<?php echo esc_attr($file_name_link); ?>" class="button-primary download-btn " download><?php _e('Download HTML', 'primer'); ?></a>

					<?php }
					}
				?>
			<?php } ?>
		<?php } ?>
		<!--<a href="" class="button"><?php /*_e('Save error log', 'primer'); */?></a>-->
	</div>
</form>
<?php


