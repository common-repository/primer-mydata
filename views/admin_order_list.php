<?php
$primer_license_data = get_option('primer_licenses');
if (!is_array($primer_license_data) || !array_key_exists('mistake_license', $primer_license_data)) { ?>
	<form id="tables-filter" method="post">
		<div class="cmb2-wrap form-table"><div id="cmb2-metabox-primer_emails" class="cmb2-metabox cmb-field-list"><div class="cmb-row cmb-type-title cmb2-id-title-email-smtp-settings" data-fieldtype="title">
				</div><div class="cmb-row cmb-type-title cmb2-id-general-settings-disable disable_functionality" data-fieldtype="title">

					<div class="cmb-td">
						<h3 class="cmb2-metabox-title" id="general-settings-disable" data-hash="1j6g34ljfp8o">Activate this plugin with proper credentials</h3>
						<p class="cmb2-metabox-description">For more information you can go to www.primer.gr.</p>

					</div>
				</div></div></div>
	</form>
<?php } else {
	if($primer_license_data['mistake_license'] == 'fail') { ?>
		<form id="tables-filter" method="post">
			<div class="cmb2-wrap form-table"><div id="cmb2-metabox-primer_emails" class="cmb2-metabox cmb-field-list"><div class="cmb-row cmb-type-title cmb2-id-title-email-smtp-settings" data-fieldtype="title">
					</div><div class="cmb-row cmb-type-title cmb2-id-general-settings-disable disable_functionality" data-fieldtype="title">

						<div class="cmb-td">
							<h3 class="cmb2-metabox-title" id="general-settings-disable" data-hash="1j6g34ljfp8o">You need an active MyData service subscription to start issuing receipts.</h3>
							<p class="cmb2-metabox-description">You can go to www.primer.gr to get a free MyData subscription. </p>

						</div>
					</div></div></div>
		</form>
	<?php } else {
		if (!empty($primer_license_data['wpModules']) && in_array(1, $primer_license_data['wpModules'])) { ?>
			<form id="tables-filter" method="get">
				<!-- For plugins, we also need to ensure that the form posts back to our current page -->
				<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
				<!-- Now we can render the completed list table -->
				<div id="primer_order_table">
					<?php
					//		wp_nonce_field( 'ajax-order-list-nonce', '_ajax_order_list_nonce' );
					$this->display();
					?>
				</div>
				<div class="submit convert_orders">
					<div class="send_receipts_wrap">
						<a href="<?php echo admin_url('admin.php?page=primer_receipts_logs'); ?>" target="_blank" class="button"><?php _e('Log', 'primer'); ?></a>
					</div>

					<input type="hidden" name="order_nonce" value="<?php echo wp_create_nonce('order_nonce'); ?>">
					<input type="hidden" name="action" value="convert_select_orders">
					<input type="submit" class="submit_convert_orders" value="<?php _e('Issue Receipts for selected orders', 'primer'); ?>" disabled>
				</div>
			</form>
		<?php } else { ?>
			<form id="tables-filter" method="post">
				<div class="cmb2-wrap form-table"><div id="cmb2-metabox-primer_emails" class="cmb2-metabox cmb-field-list"><div class="cmb-row cmb-type-title cmb2-id-title-email-smtp-settings" data-fieldtype="title">
						</div><div class="cmb-row cmb-type-title cmb2-id-general-settings-disable disable_functionality" data-fieldtype="title">

							<div class="cmb-td">
								<h3 class="cmb2-metabox-title" id="general-settings-disable" data-hash="1j6g34ljfp8o">Activate this plugin with proper credentials</h3>
								<p class="cmb2-metabox-description">For more information you can go to www.primer.gr.</p>

							</div>
						</div></div></div>
			</form>
		<?php } ?>
	<?php }
}
?>
<?php

