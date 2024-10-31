<form id="tables-receipt-filter" method="get">
	<!-- For plugins, we also need to ensure that the form posts back to our current page -->
	<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
	<!-- Now we can render the completed list table -->
	<div id="primer_receipt_table">
		<?php $this->display(); ?>
	</div>
	<div class="submit convert_orders convert_receipts">
		<div class="send_receipts_wrap">
			<a href="<?php echo admin_url('admin.php?page=primer_receipts_logs'); ?>" target="_blank" class="button"><?php _e('Log', 'primer'); ?></a>
		</div>

        <div class="send_receipts_wrap">
            <button type="button" class="cancel_receipt button button-secondary" disabled><?php _e('Cancel Invoice', 'primer'); ?></button>
        </div>

        <div class="send_receipts_wrap">
            <button type="button" class="resend_receipt_to_customer button button-secondary" disabled><?php _e('Send to customer', 'primer'); ?></button>
        </div>
		<a href="" class="button download-btn hide" download><?php _e('Download selected receipts', 'primer'); ?></a>

		<input type="submit" class="button" id="zip_load" value="<?php _e('Download selected receipts', 'primer'); ?>" disabled>
	</div>
</form>
<?php


