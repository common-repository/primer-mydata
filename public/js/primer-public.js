(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(document).ready(function () {
		var defaultCountry = $('#billing_country').val();
		if (defaultCountry === 'GR') {
			$('#billing_vat').prop('type', 'number');
		}
		else {
			$('#billing_vat').prop('type', 'text');
		}
		var isChecked = 1;
		console.log('edw ' + $('#billing_invoice_type_receipt').is(':checked') ? 1 : 0);
		//instant vies check when the checkout is loaded and $invoice_type is (always) receipt
		var $invoice_type = $('input[name="billing_invoice_type"]');
		//console.log($invoice_type.val());
		$("input[name='billing_invoice_type_receipt']").prop('checked', true);
		$.ajax({
			type: 'POST',
			url: primer_validation_ajax_obj.ajaxurl,
			data: {
				action: 'reset_of_the_tax_from_vies',
				isChecked: 1
			},
			success: function (response) {
				//jQuery('body').trigger('update_checkout');
				console.log(response);
				jQuery( 'body' ).trigger( 'update_checkout' );
			},
			error: function (error) {
				jQuery('body').trigger('update_checkout');
				//console.log(error);
			}
		});
		var radiobtn = document.getElementById("billing_invoice_type_receipt");
		if(radiobtn){
			radiobtn.checked = true;
		}
		checkInvoiceFieldsVisibility($invoice_type.val());
		function checkInvoiceFieldsVisibility(radio_val) {
			var required = '<abbr class="required" title="required">*</abbr>';
			var invoice_type = radio_val === 'primer_invoice';
			if (invoice_type) {
				$('.invoice_type-hide').slideDown('fast');
				$('#billing_vat_field label > .optional').remove();
				$('#billing_vat_field').find('abbr').remove();
				$('#billing_vat_field'+' label').append(required);
				$('#billing_store_field label > .optional').remove();
				$('#billing_store_field').find('abbr').remove();
				$('#billing_store_field'+' label').append(required);
				$('#billing_company_field label > .optional').remove();
				$('#billing_company_field').find('abbr').remove();
				$('#billing_company_field'+' label').append(required);
				$('#billing_doy_field label > .optional').remove();
				$('#billing_doy_field').find('abbr').remove();
				$('#billing_doy_field'+' label').append(required);
				if($('#billing_country').val() !== 'GR'){
					$('#billing_doy_field').hide();
					$('#billing_doy_field').removeClass('validate-required');
					$('#billing_doy_field').removeClass('woocommerce-validated');
					$('#billing_doy_field').removeClass('woocommerce-invalid woocommerce-invalid-required-field');
				} else {
					$('#billing_doy_field').show();
					$('#billing_doy_field' + ' label').append(required);
				}
			} else {
				$('.invoice_type-hide').slideUp('fast');
				$('#billing_company_field').find('abbr').remove();
			}
		}

		//isChecked takes value 1 for receipt and 0 for invoice
		$invoice_type.on('change', function () {
			checkInvoiceFieldsVisibility($(this).val())
			const isChecked = $('#billing_invoice_type_receipt').is(':checked') ? 1 : 0;
			if ( isChecked === 1 ) {
				$.ajax({
					type: 'POST',
					url: primer_validation_ajax_obj.ajaxurl,
					data: {
						action: 'reset_of_the_tax_from_vies',
						isChecked: 1
					},
					success: function (response) {
						console.log(response);
						jQuery('body').trigger('update_checkout');
					},
					error: function (error) {
						console.log(error);
						//jQuery('body').trigger('update_checkout');
					}
				});
			} else {
				var vatNumber = document.getElementById('billing_vat');
				if(vatNumber) {
					var billingCountry = $('#billing_country').val()
					$.ajax({
						type: 'POST',
						url: primer_validation_ajax_obj.ajaxurl,
						data: {
							action: "vat_number_validation",
							vat: vatNumber.value.toString(),
							isCountry: billingCountry,
							isChecked: isChecked
						},
						success: function (response) {
							jQuery('body').trigger('update_checkout');
							console.log(response);

						}, error: function (error) {

							//console.log(error);
						}
					});
				}
			}
		})

		$('#billing_country').on('blur paste', function () {
			const check_if_invoice = document.getElementById('billing_invoice_type_primer_invoice');
			var required = '<abbr class="required" title="required">*</abbr>';
			const vatNumber = document.getElementById('billing_vat');
			var country = $('#billing_country').val();
			if($('#billing_country').val() !== 'GR'){
				$('#billing_vat').prop('type', 'text');
				$('#billing_doy_field').hide();
				$('#billing_doy_field').removeClass('validate-required');
				$('#billing_doy_field').removeClass('woocommerce-validated');
				$('#billing_doy_field').removeClass('woocommerce-invalid woocommerce-invalid-required-field');

				checkForVies(vatNumber, country);
			} else {
				$('#billing_vat').prop('type', 'number');
				if($invoice_type.val() === 'primer_invoice' || check_if_invoice.checked) {
					$('#billing_doy_field').show();
					$('#billing_doy_field'+' label').append(required);
				}
				checkForVies(vatNumber, country);
			}
		});
		function checkForVies(vatNumber, country) {
			var isChecked = $('#billing_invoice_type_receipt').is(':checked') ? 1 : 0;
			jQuery.ajax({
				type: 'POST',
				url: primer_validation_ajax_obj.ajaxurl,
				data: {
					action: "vat_number_validation",
					vat: vatNumber.value.toString(),
					isCountry: country,
					isChecked: isChecked
				},
				success: function(response) {
					jQuery( 'body' ).trigger( 'update_checkout' );
					//console.log(response);
				}, error: function(error) {
					//console.log(error);
				}
			});
		}



		// remove tax from checkout for VIES CHECK
		$(document).on('blur paste','#billing_vat',function() {
			var $this = $(this);
			var country = $('#billing_country').val();
			var isChecked = $('#billing_invoice_type_receipt').is(':checked') ? 1 : 0;

			if (country !== 'GR') {
				// Code for countries other than Greece
				jQuery.ajax({
					type: "POST",
					url: primer_validation_ajax_obj.ajaxurl,
					data: {
						action: "vat_number_validation",
						vat: $this.val(),
						isCountry: country,
						isChecked: isChecked
					},
					success: function (response) {
						jQuery('body').trigger('update_checkout');
						console.log(response);
					},
					error: function (error) {
						console.log(error);
					}
				});
			} else {
				// Code for Greece
				if($this.val()) {
					jQuery.ajax({
						type: "post",
						url: primer_validation_ajax_obj.ajaxurl,
						data: {
							action: "primer_timologio_for_wc_aade_fill",
							vat: $this.val()
						},
						success: function (response) {
							console.log(response)
							if (response.onomasia) {
								$('#billing_company').val(response.onomasia);
							}

							if (response.doy) {
								$('#billing_doy').val(response.doy);
								$('#billing_doy').trigger('change');
							}

							if (response.postal_address) {
								$('#billing_address_1').val(response.postal_address);
							}

							if (response.postal_address_no) {
								$('#billing_address_1').val($('#billing_address_1').val() + " " + response.postal_address_no);
							}

							if (response.postal_area_description) {
								$('#billing_city').val(response.postal_area_description);
							}

							if (response.postal_zip_code) {
								$('#billing_postcode').val(response.postal_zip_code);
							}

							if (response.activities && response.activities[0]) {
								$('#billing_store').val(response.activities[0]);
							}
						}
						, error: function (error) {
							console.log(error);
						}
					});
				}
			}
		});
		$('#billing_doy').selectWoo();
		$(window).bind('beforeunload', function(){
			$("input[name='billing_invoice_type_receipt']").prop('checked',true);
		});

	})

})( jQuery );
