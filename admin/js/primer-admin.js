
(function( $ ) {

	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
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

		function create_form(inputs) {
			var form = document.createElement('form');
			form.setAttribute("method", "post");
			form.setAttribute("id", "email-test-form-primer");
			form.setAttribute("style", "display:none");
			// form.setAttribute("action", "primer_smtp_settings");
			$(inputs).each(function (i, el) {
				el = el.cloneNode();
				form.append(el)
			})
			var h = document.createElement('input');
			h.setAttribute("type", "hidden");
			h.setAttribute("name", "action");
			h.setAttribute("value", "primer_smtp_settings");
			form.append(h);

			var s = document.createElement('input');
			s.setAttribute("type", "submit");
			s.setAttribute("id", "test-email-form-submit");
			s.setAttribute("value", "Submit");
			form.append(s);
			$('#primer_emails').before($(form));
			// $(form).submit();
			$('#test-email-form-submit').trigger('click');
		}
		$('.send_tested_email').on('click', function () {
			$('.send_tested_email').attr('disabled', true);
			var sibling_divs = $(this).prevAll($('.cmb-row.cmb-type-text'));
			var email_fields = sibling_divs.find('input');
			create_form(email_fields);
		});

		function popupOpenClose(popup) {
			if ($('.popup_wrapper').length == 0) {
				$(popup).wrapInner("<div class='popup_wrapper'></div>")
			}
			$(popup).show();

			$(popup).click(function (e) {
				if (e.target == this) {
					if ($(popup).is(':visible')) {
						$(popup).hide();
						$(popup).remove();
					}
				}
			})

		}

		$(document).on('submit', '#email-test-form-primer', function (e) {
			e.preventDefault();

			var data = $('#email-test-form-primer').serialize();
			$.ajax({
				url: primer_ajax_obj.ajax_url,
				data: data+'&_ajax_nonce='+primer_ajax_obj.nonce,
				method: "POST",
				success: function (data) {
					if (data)
						$('#primer_emails').append(data);
						popupOpenClose('.primer_popup');
						$('.send_tested_email').removeAttr('disabled');
					}
				,
				error: function(xhr, status, error) {
					console.log(error)
					$('#email-test-form-primer').remove();
				},
				complete: function () {
					$('#email-test-form-primer').remove();
				}
			})
		})

		$(".button.save_order").on('click', function (e) {
			var save_btn_val = $(".button.save_order").val();
			var confirmation;

			var confirm_text = '';

			var line_items = $('#order_line_items');

			if (line_items.children().length <= 0) {
				confirm_text += 'Product item, ';
			}

			var exist_taxes = true;

			var invoice_required = true;

			var country_required = true;

			var tax_column = line_items.find('td.line_tax');
			var tax_column_item = tax_column.find('.view');
			var check_tax_items = true;
			tax_column_item.each(function (i, el) {
				let tax_column_item_text = $(el).text();
				let tax_trim_text = tax_column_item_text.trim();
				if (tax_trim_text == "–") {
					check_tax_items = false;
				}
			})
			var tax_column_item_text = tax_column_item.text();
			var tax_trim_text = tax_column_item_text.trim();


			if (tax_column.length <= 0 || check_tax_items == false) {
				confirm_text += 'Tax value, ';
				exist_taxes = false;
			}

			var select_invoice_type = $('.wc-radios input[name="get_invoice_type"]:checked').val();
			var edit_address_wrap = $('.edit_address');

			var first_name_label = edit_address_wrap.find('._billing_first_name_field label').text();
			var first_name = edit_address_wrap.find('input[name="_billing_first_name"]').val();
			if (first_name == '') {
				confirm_text += first_name_label + ', '
			}

			var last_name_label = edit_address_wrap.find('._billing_last_name_field label').text();
			var last_name = edit_address_wrap.find('input[name="_billing_last_name"]').val();
			if (last_name == '') {
				confirm_text += last_name_label + ', '
			}

			var country_label = edit_address_wrap.find('._billing_country_field label').text();
			var country = edit_address_wrap.find('select[name="_billing_country"]').val();
			if (country == '') {
				confirm_text += country_label + ', '
				country_required = false
			}

			var address_1_label = edit_address_wrap.find('._billing_address_1_field label').text();
			var address_1 = edit_address_wrap.find('input[name="_billing_address_1"]').val();
			if (address_1 == '') {
				confirm_text += address_1_label + ', '
			}

			var city_label = edit_address_wrap.find('._billing_city_field label').text();
			var city = edit_address_wrap.find('input[name="_billing_city"]').val();
			if (city == '') {
				confirm_text += city_label + ', '
			}

			var postcode_label = edit_address_wrap.find('._billing_postcode_field label').text();
			var postcode = edit_address_wrap.find('input[name="_billing_postcode"]').val();
			if (postcode == '') {
				confirm_text += postcode_label + ', '
			}

			var phone_label = edit_address_wrap.find('._billing_phone_field label').text();
			var phone = edit_address_wrap.find('input[name="_billing_phone"]').val();
			if (phone == '') {
				confirm_text += phone_label + ', '
			}

			var vat_label = edit_address_wrap.find('._billing_vat_field label').text();
			var vat = edit_address_wrap.find('input[name="_billing_vat"]').val();


			var store_label = edit_address_wrap.find('._billing_store_field label').text();
			var store = edit_address_wrap.find('input[name="_billing_store"]').val();


			var doy_label = edit_address_wrap.find('._billing_doy_field label').text();
			var doy = edit_address_wrap.find('input[name="_billing_doy"]').val();


			var company_label = edit_address_wrap.find('._billing_company_field label').text();
			var company = edit_address_wrap.find('input[name="_billing_company"]').val();

			if (select_invoice_type == 'invoice') {
				if (vat == '') {
					confirm_text += vat_label + ', '
					invoice_required = false
				}
				if (store == '') {
					confirm_text += store_label + ', '
					invoice_required = false
				}
				if (doy == '') {
					confirm_text += doy_label + ', '
					invoice_required = false
				}

				if (company == '') {
					confirm_text += company_label + ' '
					invoice_required = false
				}
			}



		});


		var getUrlParameter = function getUrlParameter(sParam) {
			var sPageURL = window.location.search.substring(1),
				sURLVariables = sPageURL.split('&'),
				sParameterName,
				i;

			for (i = 0; i < sURLVariables.length; i++) {
				sParameterName = sURLVariables[i].split('=');

				if (sParameterName[0] === sParam) {
					return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
				}
			}
			return false;
		};

		if (getUrlParameter('page') === 'primer_export'){
			var vat_amount_per_rate = document.getElementById('export_select_total_vat_rate_amount').value;
			if(vat_amount_per_rate !== null && vat_amount_per_rate !== ''){
				$(`#cmb2-metabox-primer_export .cmb2-id-export-leave-blank-row`).hide();
			}else{
				$(`#cmb2-metabox-primer_export .cmb2-id-export-leave-blank-row`).show();
			}
			$('#cmb2-metabox-primer_export select[name^="export_select"]').on('change', function () {
				var vat_amount_per_rate = document.getElementById('export_select_total_vat_rate_amount').value;
				console.log(vat_amount_per_rate);
				if(vat_amount_per_rate !== null && vat_amount_per_rate !== ''){
					$(`#cmb2-metabox-primer_export .cmb2-id-export-leave-blank-row`).hide();
				}else{
					$(`#cmb2-metabox-primer_export .cmb2-id-export-leave-blank-row`).show();
				}
			});
			var export_invoice_per_line = document.getElementById('export_only_invoice_details').checked;
			if(export_invoice_per_line === true){
				$(`#cmb2-metabox-primer_export .cmb2-id-export-select-vat-amount`).hide();
				$(`#export_select_vat_amount`).find('option:eq(0)').prop('selected', true);
				$(`#cmb2-metabox-primer_export .cmb2-id-export-select-product-name`).hide();
				$(`#export_select_product_name`).find('option:eq(0)').prop('selected', true);
				$(`#cmb2-metabox-primer_export .cmb2-id-export-select-product-quantity`).hide();
				$(`#export_select_product_quantity`).find('option:eq(0)').prop('selected', true);
				$(`#cmb2-metabox-primer_export .cmb2-id-export-select-total-amount-per-product`).hide();
				$(`#export_select_total_amount_per_product`).find('option:eq(0)').prop('selected', true);
				$(`#cmb2-metabox-primer_export .cmb2-id-export-select-net-amount-per-product`).hide();
				$(`#export_select_net_amount_per_product`).find('option:eq(0)').prop('selected', true);
				$(`#cmb2-metabox-primer_export .cmb2-id-export-select-total-vat-rate-amount`).show();
			}else{
				$(`#cmb2-metabox-primer_export .cmb2-id-export-select-vat-amount`).show();
				$(`#cmb2-metabox-primer_export .cmb2-id-export-select-product-name`).show();
				$(`#cmb2-metabox-primer_export .cmb2-id-export-select-product-quantity`).show();
				$(`#cmb2-metabox-primer_export .cmb2-id-export-select-total-amount-per-product`).show();
				$(`#cmb2-metabox-primer_export .cmb2-id-export-select-net-amount-per-product`).show();
				$(`#cmb2-metabox-primer_export .cmb2-id-export-select-total-vat-rate-amount`).hide();
			}
			$('#export_only_invoice_details').change(function () {
				var export_invoice_per_line = document.getElementById('export_only_invoice_details').checked;
				if(export_invoice_per_line === true){
					$(`#cmb2-metabox-primer_export .cmb2-id-export-select-vat-amount`).hide();
					$(`#export_select_vat_amount`).find('option:eq(0)').prop('selected', true);
					$(`#cmb2-metabox-primer_export .cmb2-id-export-select-product-name`).hide();
					$(`#export_select_product_name`).find('option:eq(0)').prop('selected', true);
					$(`#cmb2-metabox-primer_export .cmb2-id-export-select-product-quantity`).hide();
					$(`#export_select_product_quantity`).find('option:eq(0)').prop('selected', true);
					$(`#cmb2-metabox-primer_export .cmb2-id-export-select-total-amount-per-product`).hide();
					$(`#export_select_total_amount_per_product`).find('option:eq(0)').prop('selected', true);
					$(`#cmb2-metabox-primer_export .cmb2-id-export-select-net-amount-per-product`).hide();
					$(`#export_select_net_amount_per_product`).find('option:eq(0)').prop('selected', true);
					$(`#cmb2-metabox-primer_export .cmb2-id-export-select-total-vat-rate-amount`).show();
				}else{
					$(`#cmb2-metabox-primer_export .cmb2-id-export-select-vat-amount`).show();
					$(`#cmb2-metabox-primer_export .cmb2-id-export-select-product-name`).show();
					$(`#cmb2-metabox-primer_export .cmb2-id-export-select-product-quantity`).show();
					$(`#cmb2-metabox-primer_export .cmb2-id-export-select-total-amount-per-product`).show();
					$(`#cmb2-metabox-primer_export .cmb2-id-export-select-net-amount-per-product`).show();
					$(`#cmb2-metabox-primer_export .cmb2-id-export-select-total-vat-rate-amount`).hide();
				}
			})

		}
		//different numbering according to the api selection
		if (getUrlParameter('tab') === 'mydata' || (getUrlParameter('page') === 'primer_settings' && getUrlParameter('tab') !== 'automation' && getUrlParameter('tab') !== 'emails')) {
			//$('#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gr-series').hide();
			//series_div.style.display='none';
			$('#username_validation').keyup(function(){
				$(this).val($(this).val().toUpperCase());
			});
			var series_selection_gr = document.getElementById("invoice_numbering_gr_series");
			var check_series_gr = series_selection_gr.value;
			var series_selection_gi = document.getElementById("invoice_numbering_gi_series");
			var check_series_gi = series_selection_gi.value;
			var series_selection_within = document.getElementById("invoice_numbering_within_series");
			var check_series_within = series_selection_within.value;
			var series_selection_outside = document.getElementById("invoice_numbering_outside_series");
			var check_series_outside = series_selection_outside.value;
			var series_selection_credit_receipt = document.getElementById("credit_receipt_series");
			var check_series_credit_receipt = series_selection_credit_receipt.value;
			var series_selection_credit_invoice = document.getElementById("credit_invoice_series");
			var check_series_credit_invoice = series_selection_credit_invoice.value;
			var series_selection_gr_test_api = document.getElementById("invoice_numbering_gr_test_api_series");
			var check_series_gr_test_api = series_selection_gr_test_api.value;
			var series_selection_gi_test_api = document.getElementById("invoice_numbering_gi_test_api_series");
			var check_series_gi_test_api = series_selection_gi_test_api.value;
			var series_selection_within_test_api = document.getElementById("invoice_numbering_within_test_api_series");
			var check_series_within_test_api = series_selection_within_test_api.value;
			var series_selection_outside_test_api = document.getElementById("invoice_numbering_outside_test_api_series");
			var check_series_outside_test_api = series_selection_outside_test_api.value;
			var series_selection_credit_receipt_test_api = document.getElementById("credit_receipt_test_api_series");
			var check_series_credit_receipt_test_api = series_selection_credit_receipt.value;
			var series_selection_credit_invoice_test_api = document.getElementById("credit_invoice_test_api_series");
			var check_series_credit_invoice_test_api = series_selection_credit_invoice.value;
			var greekalphabet =[
				'EMPTY',
				'A',
				'B',
				'C',
				'D',
				'E',
				'Z',
				'H',
				'Q',
				'I',
				'K',
				'L',
				'M',
				'N',
				'J',
				'O',
				'P',
				'R',
				'S',
				'T',
				'Y',
				'F',
				'X',
				'W',
				'V',
			]
			var api_selection = document.getElementById("mydata_api");
			var check_api = api_selection.options[api_selection.selectedIndex].text;
				greekalphabet.forEach(element => {
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gr-${element}`).hide();
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gi-${element}`).hide();
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-within-${element}`).hide();
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-outside-${element}`).hide();
					$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-receipt-${element}`).hide();
					$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-invoice-${element}`).hide();
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gr-${element}-test-api`).hide();
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gi-${element}-test-api`).hide();
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-within-${element}-test-api`).hide();
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-outside-${element}-test-api`).hide();
					$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-receipt-${element}-test-api`).hide();
					$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-invoice-${element}-test-api`).hide();
				});
			if(check_api === 'Production API' || check_api === 'API Παραγωγής') {
				$('#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gr-test-api-series').hide();
				$('#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gi-test-api-series').hide();
				$('#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-within-test-api-series').hide();
				$('#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-outside-test-api-series').hide();
				$('#cmb2-metabox-primer_mydata .cmb2-id-credit-invoice-test-api-series').hide();
				$('#cmb2-metabox-primer_mydata .cmb2-id-credit-receipt-test-api-series').hide();
				var last_series_gr = check_series_gr;
				$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gr-${check_series_gr}`).show();
				$('#invoice_numbering_gr_series').change(function () {
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gr-${last_series_gr}`).hide();
					var series_selection_gr_change = document.getElementById("invoice_numbering_gr_series");
					var check_series_gr_change = series_selection_gr_change.value;
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gr-${check_series_gr_change}`).show();
					last_series_gr = check_series_gr_change;
				});
				$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gi-${check_series_gi}`).show();

				var last_series_gi = check_series_gi;
				$('#invoice_numbering_gi_series').change(function () {
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gi-${last_series_gi}`).hide();
					var series_selection_gi_change = document.getElementById("invoice_numbering_gi_series");
					var check_series_gi_change = series_selection_gi_change.value;
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gi-${check_series_gi_change}`).show();
					last_series_gi = check_series_gi_change;
				});
				$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-within-${check_series_within}`).show();

				var last_series_within = check_series_within;
				$('#invoice_numbering_within_series').change(function () {
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-within-${last_series_within}`).hide();
					var series_selection_within_change = document.getElementById("invoice_numbering_within_series");
					var check_series_within_change = series_selection_within_change.value;
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-within-${check_series_within_change}`).show();
					last_series_within = check_series_within_change;
				});
				$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-outside-${check_series_outside}`).show();
				var last_series_outside = check_series_outside;
				$('#invoice_numbering_outside_series').change(function () {
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-outside-${last_series_outside}`).hide();
					var series_selection_outside_change = document.getElementById("invoice_numbering_outside_series");
					var check_series_outside_change = series_selection_outside_change.value;
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-outside-${check_series_outside_change}`).show();
					last_series_outside = check_series_outside_change;
				});
				$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-receipt-${check_series_credit_receipt}`).show();

				var last_series_credit_receipt = check_series_credit_receipt;
				$('#credit_receipt_series').change(function () {
					$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-receipt-${last_series_credit_receipt}`).hide();
					var series_selection_credit_receipt_change = document.getElementById("credit_receipt_series");
					var check_series_credit_receipt_change = series_selection_credit_receipt_change.value;
					$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-receipt-${check_series_credit_receipt_change}`).show();
					last_series_credit_receipt = check_series_credit_receipt_change;
				});

				$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-invoice-${check_series_credit_invoice}`).show();

				var last_series_credit_invoice = check_series_credit_invoice;
				$('#credit_invoice_series').change(function () {
					$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-invoice-${last_series_credit_invoice}`).hide();
					var series_selection_credit_invoice_change = document.getElementById("credit_invoice_series");
					var check_series_credit_invoice_change = series_selection_credit_invoice_change.value;
					$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-invoice-${check_series_credit_invoice_change}`).show();
					last_series_credit_invoice = check_series_credit_invoice_change;
				});
			}else{
				$('#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gr-series').hide();
				$('#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gi-series').hide();
				$('#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-within-series').hide();
				$('#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-outside-series').hide();
				$('#cmb2-metabox-primer_mydata .cmb2-id-credit-receipt-series').hide();
				$('#cmb2-metabox-primer_mydata .cmb2-id-credit-invoice-series').hide();
				var last_series_gr_test_api = check_series_gr_test_api;
				$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gr-${check_series_gr_test_api}-test-api`).show();
				$('#invoice_numbering_gr_test_api_series').change(function () {
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gr-${last_series_gr_test_api}-test-api`).hide();
					var series_selection_gr_test_api_change = document.getElementById("invoice_numbering_gr_test_api_series");
					var check_series_gr_test_api_change = series_selection_gr_test_api_change.value;
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gr-${check_series_gr_test_api_change}-test-api`).show();
					last_series_gr_test_api = check_series_gr_test_api_change;
				});
				$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gi-${check_series_gi_test_api}-test-api`).show();
				var last_series_gi_test_api = check_series_gi_test_api;
				$('#invoice_numbering_gi_test_api_series').change(function () {
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gi-${last_series_gi_test_api}-test-api`).hide();
					var series_selection_gi_test_api_change = document.getElementById("invoice_numbering_gi_test_api_series");
					var check_series_gi_test_api_change = series_selection_gi_test_api_change.value;
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-gi-${check_series_gi_test_api_change}-test-api`).show();
					last_series_gi_test_api = check_series_gi_test_api_change;
				});
				$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-within-${check_series_within_test_api}-test-api`).show();
				var last_series_within_test_api = check_series_within_test_api;
				$('#invoice_numbering_within_test_api_series').change(function () {
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-within-${last_series_within_test_api}-test-api`).hide();
					var series_selection_within_test_api_change = document.getElementById("invoice_numbering_within_test_api_series");
					var check_series_within_test_api_change = series_selection_within_test_api_change.value;
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-within-${check_series_within_test_api_change}-test-api`).show();
					last_series_within_test_api = check_series_within_test_api_change;

				});
				$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-outside-${check_series_outside_test_api}-test-api`).show();
				var last_series_outside_test_api = check_series_outside_test_api;
				$('#invoice_numbering_outside_test_api_series').change(function () {
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-outside-${last_series_outside_test_api}-test-api`).hide();
					var series_selection_outside_test_api_change = document.getElementById("invoice_numbering_outside_test_api_series");
					var check_series_outside_test_api_change = series_selection_outside_test_api_change.value;
					$(`#cmb2-metabox-primer_mydata .cmb2-id-invoice-numbering-outside-${check_series_outside_test_api_change}-test-api`).show();
					last_series_outside_test_api = check_series_outside_test_api_change;
				});

				$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-receipt-${check_series_credit_receipt_test_api}-test-api`).show();

				var last_series_credit_receipt_test_api = check_series_credit_receipt_test_api;
				$('#credit_receipt_test_api_series').change(function () {
					$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-receipt-${last_series_credit_receipt_test_api}-test-api`).hide();
					var series_selection_credit_receipt_test_api_change = document.getElementById("credit_receipt_test_api_series");
					var check_series_credit_receipt_test_api_change = series_selection_credit_receipt_test_api_change.value;
					$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-receipt-${check_series_credit_receipt_test_api_change}-test-api`).show();
					last_series_credit_receipt_test_api = check_series_credit_receipt_test_api_change;
				});

				$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-invoice-${check_series_credit_invoice_test_api}-test-api`).show();

				var last_series_credit_invoice_test_api = check_series_credit_invoice_test_api;
				$('#credit_invoice_test_api_series').change(function () {
					$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-invoice-${last_series_credit_invoice_test_api}-test-api`).hide();
					var series_selection_credit_invoice_test_api_change = document.getElementById("credit_invoice_test_api_series");
					var check_series_credit_invoice_test_api_change = series_selection_credit_invoice_test_api_change.value;
					$(`#cmb2-metabox-primer_mydata .cmb2-id-credit-invoice-${check_series_credit_invoice_test_api_change}-test-api`).show();
					last_series_credit_invoice_test_api = check_series_credit_invoice_test_api_change;
				});
			}
		}
		if (getUrlParameter('tab') == 'automation') {
			var val_args = {};
			$('#primer_automation').on('change', function (el) {
				var element = $(el.target);
				var conditional_selects = $('select[id*="_receipt_order_states"]');
				if (conditional_selects.length && element.is('select')) {
					for(let i = 0; i < conditional_selects.length; i++) {
						val_args[i] = $(conditional_selects[i]).val();
					}
				}
				var selectsVal = Object.values(val_args);
				let result = selectsVal.some((element, index) => {return selectsVal.indexOf(element) !== index});
				if (result) {
					var popup_data = '<div class="primer_popup popup_error"><h3>Duplicate values are not accepted. Please select a different option</h3></div>';
					$('#primer_automation').append(popup_data)
					popupOpenClose('.primer_popup');
				}

				var send_admin_check = $('input[name="send_email_to_admin"]');
				if (send_admin_check.length && send_admin_check.prop('checked')) {
					var suc_check = $('input[name="send_successful_log"]');
					var fail_check = $('input[name="send_failed_log"]');
					if ((suc_check.prop('checked') === false) && (fail_check.prop('checked') === false)) {
						var popup_data_check = '<div class="primer_popup popup_error"><h3>Send email to admin is active. Please select one option from “send successful receipts log” or “send failed receipts log” to continue</h3></div>';
						$('#primer_automation').append(popup_data_check)
						popupOpenClose('.primer_popup');
					}
				}
			});

			$('#primer_automation').on('submit', function (event) {
				var send_admin_check = $('input[name="send_email_to_admin"]');
				if (send_admin_check.length && send_admin_check.prop('checked')) {
					var suc_check = $('input[name="send_successful_log"]');
					var fail_check = $('input[name="send_failed_log"]');
					if ((suc_check.prop('checked') === false) && (fail_check.prop('checked') === false)) {
						var popup_data_check = '<div class="primer_popup popup_error"><h3>Send email to admin is active. Please select one option from “send successful receipts log” or “send failed receipts log” to continue</h3></div>';
						$('#primer_automation').append(popup_data_check)
						popupOpenClose('.primer_popup');
						event.preventDefault();
						return false;
					}
				}

			});
			$(document).on('input', 'input[name="admin_email"]', function () {
				this.value = $.trim(this.value);
			})
		}

		$('#primer_get_system_info').on('click', function (e) {
			$(this).attr('disabled', true);
			e.preventDefault();
			var data = {
				'_ajax_nonce': primer_ajax_obj.nonce,
				'action': 'primer_system_settings',
			}
			$.ajax({
				url: primer_ajax_obj.ajax_url,
				data: data,
				method: "POST",
				timeout: 60000,
				beforeSend: function () {
					$('form#primer_licenses').css({'opacity': '0.5'});
				},
				success: function (response) {
					var getResponse = JSON.parse(response);
					$('.cmb2-id-system-check').append($(getResponse.response_wrap));
					popupOpenClose('.primer_popup');
					$('form#primer_licenses').css({'opacity': '1'});
					$('#primer_get_system_info').attr('disabled', false);
				},
				error: function(xhr, textStatus, errorThrown) {
					if (textStatus == 'timeout') {
						alert("Process timeout");
						$('form#primer_licenses').css({'opacity': '1'});
						$('#primer_get_system_info').attr('disabled', false);
					}
				}
			})
		});

		$('#cron_export_run').on('click', function (e) {
			var btn_text = $(this).text();
			e.preventDefault();

			var data = $('form#primer_export').serialize();

			$.ajax({
				url: primer_ajax_obj.ajax_url,
				data: data + '&action=export_invoice_to_report&_ajax_nonce='+primer_ajax_obj.nonce,
				method: "POST",
				beforeSend: function () {
					$('#cron_export_run').text('Loading...');
					$('#cron_export_run').addClass('disabled');
				},
				success: function (response) {
					$('#cron_export_run').text(btn_text);
					$('#cron_export_run').removeClass('disabled');
					var last = response.charAt(response.length - 1);
					console.log(response.toString());
					var response_string = response.split("{").pop();
					//console.log(response_string)
					var string = "{";
					response = string.concat(response_string);
					if(last === '0') {
						response.substring(0, response.length - 2);
						console.log(response.toString());
					}
					var response_data = JSON.parse(response)
					var downloadLink = document.createElement("a");
					/*var fileData = ['\ufeff' + response_data.file];
					var blobObject = new Blob(fileData, {
						type: "text/csv;charset=utf-8;"
					});
					var url = URL.createObjectURL(blobObject);*/
					downloadLink.href = response_data.file;
					downloadLink.download = response_data.file_name;

					document.body.appendChild(downloadLink);
					downloadLink.click();
					document.body.removeChild(downloadLink);

				},
				error: function(xhr, status, error) {
					var message_div_error = "<div class=\"primer_popup popup_error\"><h3>Something wrong!</h3></div>";
					$('#primer_export').append(message_div_error);
					console.log(error);
					popupOpenClose('.primer_popup');
				},
				complete: function () {
					var message_div = "<div class=\"primer_popup popup_success\"><h3>Export file created successfully!</h3></div>";
					$('#primer_export').append(message_div);
					popupOpenClose('.primer_popup');
				}
			})
		});

		$('#cron-execute-cron-task-now').on('click', function (e) {
			e.preventDefault();
			$(this).text('Loading...');

			var data = {
				'_ajax_nonce': primer_ajax_obj.nonce,
				'action': 'primer_fire_cron',
			};
			$.post( ajaxurl, data, function (response) {
				if (response) {
					alert('Cron was launched');
					document.location.reload();
				}
			} )
		});

		(function () {
			$('#primer_licenses #get_license_type').toggleClass('disabled');
			$('#primer_licenses #license_key').on('keyup', function () {
				$('#primer_licenses #get_license_type').removeClass('disabled');
				if ($('#primer_licenses #license_key').val() == '') {
					$('#primer_licenses #get_license_type').addClass('disabled');
				}
			})

			var disabled_title = $('#primer_automation #disable-title');
			if (disabled_title.length) {
				$('#primer_automation input[name="submit-cmb"]').hide();
			} else {
				$('#primer_automation input[name="submit-cmb"]').show();
			}
		})()

		$('#primer_licenses #get_license_type').on('click', function (e) {
			e.preventDefault();
			var license_key = $('#primer_licenses #license_key').val();
			var username = $('#primer_licenses #license_user').val();
			var password = $('#primer_licenses #license_password').val();

			var data = {
				'licenseKey': license_key,
				'username': username,
				'password': password,
				'_ajax_nonce': primer_ajax_obj.nonce,
				'action': 'primer_insert_license',
			}

			$.ajax({
				url: primer_ajax_obj.ajax_url,
				method: "POST",
				dataType: "JSON",
				data: data,
				beforeSend: function(){
					$('form#primer_licenses').css({'opacity': '0.5'});
					$('#primer_licenses #get_license_type').addClass('disabled');
				},
				success: function (response) {
					console.log(response);
					if (response.check_message) {
						if(response.messagephoto) {
							alert("Το προϊόν ενεργοποιήθηκε επιτυχώς." + response.messagephoto);
						}else{
							alert("Το προϊόν ενεργοποιήθηκε επιτυχώς.");
						}
						document.location.reload();
					} else {
						alert(response.message);
						document.location.reload();
					}
					setTimeout(function () {
						$('form#primer_licenses').css({'opacity': '1'});
					}, 1500);
				}
			})

		});
		//start of company activation modal
		$('#primer_licenses #first_time').on('click', function (e) {
			const content= document.createElement("div");
			content.setAttribute("class","title");
			content.setAttribute("id","terms");
			content.setAttribute("height","400px;");
			content.setAttribute("style", "text-align:justify;");
			e.preventDefault();
			var username = $('#primer_licenses #license_user').val();
			var password = $('#primer_licenses #license_password').val();
			var data = {
				'username': username,
				'password': password,
				'action': 'first_time_act',
			}
			$.ajax({
				url: ajaxurl,
				method: "POST",
				dataType: "JSON",
				data: data,

				success:
					function (response) {
					console.log(response.url_for_txt);
						console.log(response.server_name);
						console.log(response.message);
					if ((response.message === "The Product is activated, please proceed with sending invoices") || (response.message === "Company's data exists, please proceed with product activation") || (response.message.includes("Gateway"))){
						$.ajax({
							//get the terms of use
							url : response.url_for_txt,
							dataType: "text",
							success : function (data) {
								content.innerHTML=data;
							},
							error:function(data){
								console.log(data);
								alert("fail servlet");
							}
						});
						//create the modal
						var myDocument = document.getElementById("modal_first_time");
						if(!myDocument) {
							const newDiv = document.createElement("div");
							newDiv.setAttribute("id", "modal_first_time");
							newDiv.setAttribute("class", "primer_popup");
							newDiv.setAttribute("hidden", true);
							const newContent = document.createTextNode("TERMS OF USE");
							const tit= document.createElement("h3");
							tit.setAttribute("class","title");
							tit.innerHTML = response.terms;
							const facilities_title = document.createElement("h3");
							facilities_title.innerHTML = "ΕΓΚΑΤΑΣΤΑΣΕΙΣ";
							facilities_title.setAttribute("class", "title");
							facilities_title.style.display = "none";
							const facilities_info = document.createElement("div");
							facilities_info.innerHTML="<br>Παρακαλώ επιλέξτε αν θέλετε το plugin να εκδίδει παραστατικά από το κεντρικό ή από υποκατάστημα. Με την επιλογή Χρήση Υποκαταστήματος θα σας εμφανιστούν τα διαθέσιμα υποκαταστήματα εάν τα έχετε ξαναδηλώσει στην Primer. Εναλλακτικά για να δηλώσετε μια νέα εγκατάσταση συμπληρώστε τα πεδία στην 'Εισαγωγή νέου υποκαταστήματος' και πατήστε συνέχεια.<br> <b style='color: #c92020;'>ΠΡΟΣΟΧΗ! Με την αλλαγή εγκατάστασης από αυτήν που ήδη χρησιμοποιείτε, η αρίθμηση των παραστατικών στην καρτέλα MyData Settings θα αρχικοποιηθεί σε σειρές EMPTY και αρίθμηση 1 </b> ";
							facilities_info.setAttribute("class","mid_detail_step3 left-align");
							facilities_info.style.textAlign = "left";
							facilities_info.style.display = "none";
							// Add margin-bottom to facilities_info
							facilities_info.style.marginBottom = "20px";
							const radioContainer = document.createElement("div");
							radioContainer.style.textAlign = "left";

							// Create the first radio button
							const radioOption1 = document.createElement("input");
							radioOption1.setAttribute("type", "radio");
							radioOption1.setAttribute("name", "radioGroup");
							radioOption1.setAttribute("value", "option1");
							radioOption1.setAttribute("id", "option1");
							radioOption1.setAttribute("checked", "checked");
							radioOption1.setAttribute("class", "subsidiary-radio");
							radioOption1.style.appearance = "none";
							radioOption1.style.width = "1.25rem"; // Set width as needed
							radioOption1.style.height = "1.25rem"; // Set height as needed
							radioOption1.style.borderRadius = "0"; // Make it square
							radioOption1.style.border = "2px solid #333"; // Border color

							// Create a label for the first radio button
							const labelOption1 = document.createElement("label");
							labelOption1.setAttribute("for", "option1");
							labelOption1.textContent = "Χρήση Κεντρικού για έκδοση παραστατικών";
							labelOption1.style.fontWeight = "bold";

							// Create the second radio button
							const radioOption2 = document.createElement("input");
							radioOption2.setAttribute("type", "radio");
							radioOption2.setAttribute("name", "radioGroup");
							radioOption2.setAttribute("value", "option2");
							radioOption2.setAttribute("id", "option2");
							radioOption2.setAttribute("class", "subsidiary-radio");
							radioOption2.style.appearance = "none";
							radioOption2.style.width = "1.25rem"; // Set width as needed
							radioOption2.style.height = "1.25rem"; // Set height as needed
							radioOption2.style.borderRadius = "0"; // Make it square
							radioOption2.style.border = "2px solid #333"; // Border color

							// Create a label for the second radio button
							const labelOption2 = document.createElement("label");
							labelOption2.setAttribute("for", "option2");
							labelOption2.textContent = "Χρήση Υποκαταστήματος για έκδοση παραστατικών";
							labelOption2.style.fontWeight = "bold";

							const spacingDiv = document.createElement("div");
							spacingDiv.style.height = "15px";

							// Append radio buttons and labels to the container
							radioContainer.appendChild(radioOption1);
							radioContainer.appendChild(labelOption1);
							radioContainer.appendChild(spacingDiv);
							radioContainer.appendChild(radioOption2);
							radioContainer.appendChild(labelOption2);

							// Hide the radio container initially
							radioContainer.style.display = "none";

							//Κουμπί συνέχειας

							const insertSubsidiaryTitle = document.createElement("h3")
							insertSubsidiaryTitle.setAttribute("class", "title");
							insertSubsidiaryTitle.innerHTML = "Εισαγωγή νέου υποκαταστήματος";
							insertSubsidiaryTitle.style.display = "none";
							insertSubsidiaryTitle.style.textAlign = "center";

							const errorMessageSubsidiary = document.createElement("div");
							errorMessageSubsidiary.setAttribute("id", "error_message_sub"); // Set an ID for styling or manipulation
							errorMessageSubsidiary.style.color = "red";
							errorMessageSubsidiary.innerHTML = "";


							const ctnBtn = document.createElement("button");
							ctnBtn.setAttribute("class", "button-primary");
							ctnBtn.setAttribute("id", "ctnBtn");
							ctnBtn.style.margin = '100spx auto 0 auto'; //Με το υποκαταστημα ισως χρειαστει να αλλαξω το 100px σε μικροτερο
							ctnBtn.innerHTML = "Συνέχεια";
							ctnBtn.style.display = "none";
							ctnBtn.style.width = "calc(100% - 20px)";

							//Κουμπί ακύρωσης
							const cnlBtn = document.createElement("button");
							cnlBtn.setAttribute("class", "button-danger");
							cnlBtn.setAttribute("id", "cnlBtn");
							cnlBtn.style.margin = '10px auto 0 auto'; //
							cnlBtn.innerHTML = "Ακύρωση";
							cnlBtn.style.display = "none";
							cnlBtn.style.width = "calc(100% - 20px)";
							cnlBtn.style.marginBottom = "0";

							const activationTile = document.createElement("h3");
							activationTile.innerHTML = "ΕΝΕΡΓΟΠΟΙΗΣΗ";
							activationTile.setAttribute("class", "title");
							activationTile.style.display = "none";

							const activationTileInfo = document.createElement("div");
							activationTileInfo.innerHTML="Βεβαιωθείτε ότι οι παρακάτω λεπτομέρειες είναι σωστές: <br><br>";
							activationTileInfo.style.display = "none";

							//Στοιχεια εταιρείας
							const serialNumber = document.createElement("span");
							serialNumber.style.textAlign = "left";
							//serialNumber.innerHTML = "<b>Σειριακός αριθμός: </b>";
							serialNumber.style.display = "none";

							const companyName = document.createElement("span");
							companyName.style.textAlign = "left";
							//companyName.innerHTML = "<br><b>Επωνυμία Εταιρείας: </b>";
							companyName.style.display = "none";

							const companySmallName = document.createElement("span");
							companySmallName.style.textAlign = "left";
							//companySmallName.innerHTML = "<br><b>Διακριτικός Τίτλος: </b>";
							companySmallName.style.display = "none";

							const companyAddress = document.createElement("span");
							companyAddress.style.textAlign = "left";
							//companyAddress.innerHTML = "<br><b>Διεύθυνση: </b>";
							companyAddress.style.display = "none"

							const companyCity = document.createElement("span");
							companyCity.style.textAlign = "left";
							//companyCity.innerHTML = "<br><b>Πόλη: </b>";
							companyCity.style.display = "none";

							const companyTk = document.createElement("span");
							companyTk.style.textAlign = "left";
							//companyTk.innerHTML = "<br><b>Ταχυδρομικός Κώδικας: </b>";
							companyTk.style.display = "none";

							const companyVat = document.createElement("span");
							companyVat.style.textAlign = "left";
							//companyVat.innerHTML = "<br><b>Α.Φ.Μ: </b>";
							companyVat.style.display = "none";

							const companyDoy = document.createElement("span");
							companyDoy.style.textAlign = "left";
							//companyDoy.innerHTML = "<br><b>Δ.Ο.Υ: </b>";
							companyDoy.style.display = "none";

							const gemhNumber = document.createElement("span");
							gemhNumber.style.textAlign = "left";
							//gemhNumber.innerHTML = "<br><b>Αριθμός ΓΕΜΗ: </b>";
							gemhNumber.style.display = "none";

							const companyPhoneNumber = document.createElement("span");
							companyPhoneNumber.style.textAlign = "left";
							//companyPhoneNumber.innerHTML = "<br><b>Τηλέφωνο: </b>";
							companyPhoneNumber.style.display = "none";

							const companyEmail = document.createElement("span");
							companyEmail.style.textAlign = "left";
							//companyEmail.innerHTML = "<br><b>Email: </b>";
							companyEmail.style.display = "none";

							const companyActivity = document.createElement("span");
							companyActivity.style.textAlign = "left";
							//companyActivity.innerHTML = "<br><b>Δραστηριότητα: </b>";
							companyActivity.style.display = "none";

							const representativeName = document.createElement("span");
							representativeName.style.textAlign = "left";
							//representativeName.innerHTML = "<br><b>Όνομα Νόμιμου Εκπροσώπου: </b>";
							representativeName.style.display = "none";

							const representativeSurname = document.createElement("span");
							representativeSurname.style.textAlign = "left";
							//representativeSurname.innerHTML = "<br><b>Επώνυμο Νόμιμου Εκπροσώπου: </b>";
							representativeSurname.style.display = "none";

							const representativeVatNumber = document.createElement("span");
							representativeVatNumber.style.textAlign = "left";
							//representativeVatNumber.innerHTML = "<br><b>Α.Φ.Μ Νόμιμου Εκπροσώπου: </b>";
							representativeVatNumber.style.display = "none";

							const companyMobilePhoneNumber = document.createElement("span");
							companyMobilePhoneNumber.style.textAlign = "left";
							companyMobilePhoneNumber.innerHTML = "<br><b>Κινητό ενεργοποίησης: </b>";
							companyMobilePhoneNumber.style.display = "none";

							const selectedFacility = document.createElement("span");
							selectedFacility.style.textAlign = "left";
							selectedFacility.innerHTML = "<br><b>Επιλεγμένη Εγκατάσταση: </b>Κεντρικό";
							selectedFacility.style.display = "none";

							const selectedSubsidiary = document.createElement("h3");
							selectedSubsidiary.innerHTML = "ΕΠΙΛΕΓΜΕΝΟ ΥΠΟΚΑΤΑΣΤΗΜΑ";
							selectedSubsidiary.setAttribute("class", "title");
							selectedSubsidiary.style.display = "none";

							const subsidiaryBrandId= document.createElement("span");
							subsidiaryBrandId.style.textAlign = "left";
							subsidiaryBrandId.innerHTML = "<br><b>Αριθμός Εγκατάστασης στο Μητρώο του Taxis: </b>";
							subsidiaryBrandId.style.display = "none";

							const subsidiaryCity= document.createElement("span");
							subsidiaryCity.style.textAlign = "left";
							subsidiaryCity.innerHTML = "<br><b>Πόλη: </b>";
							subsidiaryCity.style.display = "none";

							const subsidiaryStreet= document.createElement("span");
							subsidiaryStreet.style.textAlign = "left";
							subsidiaryStreet.innerHTML = "<br><b>Οδός: </b>";
							subsidiaryStreet.style.display = "none";

							const subsidiaryNumber= document.createElement("span");
							subsidiaryNumber.style.textAlign = "left";
							subsidiaryNumber.innerHTML = "<br><b>Αριθμός: </b>";
							subsidiaryNumber.style.display = "none";

							const subsidiaryΤΚ = document.createElement("span");
							subsidiaryΤΚ.style.textAlign = "left";
							subsidiaryΤΚ.innerHTML = "<br><b>Ταχυδρομικός Κώδικας: </b>";
							subsidiaryΤΚ.style.display = "none";

							const subsidiaryDoy = document.createElement("span");
							subsidiaryDoy.style.textAlign = "left";
							subsidiaryDoy.innerHTML = "<br><b>Δ.Ο.Υ: </b>";
							subsidiaryDoy.style.display = "none";

							const subsidiaryPhone = document.createElement("span");
							subsidiaryPhone.style.textAlign = "left";
							subsidiaryPhone.innerHTML = "<br><b>Τηλέφωνο: </b>";
							subsidiaryPhone.style.display = "none";

							const lastComfirmation = document.createElement("div");
							lastComfirmation.style.margin = '30px auto 0 auto';
							lastComfirmation.innerHTML = "Εάν είστε βέβαιοι ότι τα παραπάνω στοιχεία είναι σωστά, πατήστε 'Ολοκλήρωση' <br><br>";
							lastComfirmation.style.display = "none";

							const completeBtn = document.createElement("button");
							completeBtn.setAttribute("class", "button-primary");
							completeBtn.setAttribute("id", "completeBtn");
							completeBtn.style.margin = '25px auto 0 auto';
							completeBtn.innerHTML = "Ολοκλήρωση";
							completeBtn.style.display = "none";
							completeBtn.style.width = "calc(100% - 20px)";

							const backBtn = document.createElement("button");
							backBtn.setAttribute("class", "button-danger");
							backBtn.setAttribute("id", "backBtn");
							backBtn.style.margin = '10px auto 0 auto';
							backBtn.innerHTML = "Πίσω";
							backBtn.style.display = "none";
							backBtn.style.width = "calc(100% - 20px)";

							const h4ElementsArray = [];
							const radioElementsArray = [];
							const labelElementsArray = [];
							const formElementsArray = [];

							const btnact= document.createElement("button");
							btnact.setAttribute("class","button-primary");
							btnact.setAttribute("id","accept_btn");
							btnact.onclick= function accept() {
								var license_key = $('#primer_licenses #license_key').val();
								var username = $('#primer_licenses #license_user').val();
								var password = $('#primer_licenses #license_password').val();
								var check_reset = false;
								//var answer = confirm("Θέλετε να γίνει επαναφορά της αρίθμησης των παραστατικών;");
								//	if(answer){
								//		check_reset = true;
								//	}



								var pluginVersion = $('input[name="plugin_package"]').val();
								console.log(pluginVersion);
								var data = {
									'licenseKey': license_key,
									'username': username,
									'password': password,
									'check_reset': check_reset,
									'action': 'primer_insert_license',
								}
								if (pluginVersion != 'Primer Plugin Gold Edition') {
									$.ajax({
										url: ajaxurl,
										method: "POST",
										dataType: "JSON",
										data: data,
										beforeSend: function () {
											$('form#primer_licenses').css({'opacity': '0.5'});
											$('#accept_btn').addClass('disabled');
											$('#accept_btn').text('Σε Εξέλιξη');

											
										},
										success: function (response) {
											console.log(response);
											console.log(response.kind);
											if (response.check_message === true) {
												if (response.mistake_domain !== true) {
													if (response.messagephoto) {
														alert("Η ενεργοποίηση πραγματοποιήθηκε με επιτυχία. Παρακαλώ ελέγξτε την αρίθμηση των παραστατικών στη καρτέλα MyData Settings πριν προχωρήσετε σε έκδοση." + response.messagephoto);
													} else {
														alert("Η ενεργοποίηση πραγματοποιήθηκε με επιτυχία. Παρακαλώ ελέγξτε την αρίθμηση των παραστατικών στη καρτέλα MyData Settings πριν προχωρήσετε σε έκδοση.");
													}
												} else {
													alert("Η επαλήθευση του domain name σας απέτυχε!Παρακαλώ ξαναπροσπαθήστε.");
												}
												document.location.reload();
											} else {
												alert(response.message);
												document.location.reload();
											}
											setTimeout(function () {
												$('form#primer_licenses').css({'opacity': '1'});
											}, 1500);
										},
										error: function (jqXHR, textStatus, errorThrown) {
											console.error("AJAX Error:", textStatus, errorThrown);
											// Handle the error here, e.g., show an alert or perform additional actions
										}
									})
								} else {

									$.ajax({
										url: ajaxurl,
										method: "POST",
										dataType: "JSON",
										data: data,
										beforeSend: function () {
											$('form#primer_licenses').css({'opacity': '0.5'});
											$('#accept_btn').addClass('disabled');
											$('#accept_btn').text('Σε Εξέλιξη');
										},
										success: function (response) {
											//console.log(response.kind)
											//console.log(response)
											const messageObject = JSON.parse(response.message);
											const subsidiariesData = messageObject.subsidiaries;
											// console.log('edw');
											//console.log(subsidiariesData);
											let numberOfSubsidiaries;
											if (subsidiariesData == null) {
												//console.log('einai null');
												numberOfSubsidiaries = 0;
											} else {
												//console.log('den einai null');
												numberOfSubsidiaries = subsidiariesData.length;
											}
											// console.log('numberOfSubsidiaries edw');
											// console.log(numberOfSubsidiaries);
											const radioSubsidiaryDefault = document.createElement("input");
											radioSubsidiaryDefault.setAttribute("type", "radio");
											radioSubsidiaryDefault.setAttribute("name", "subsidiaryRadioGroup");
											radioSubsidiaryDefault.setAttribute("value", `subsidiary_${numberOfSubsidiaries + 1}_radio`);
											radioSubsidiaryDefault.setAttribute("id", `option${numberOfSubsidiaries + 1}`);
											radioSubsidiaryDefault.setAttribute("class", "subsidiary-radio");
											radioSubsidiaryDefault.style.display = "inline-block";
											radioSubsidiaryDefault.style.display = "none";
											radioSubsidiaryDefault.style.width = "1.25rem";
											radioSubsidiaryDefault.style.height = "1.25rem";
											radioSubsidiaryDefault.style.borderRadius = "0";
											radioSubsidiaryDefault.style.border = "2px solid #333";

											const labelSubsidiaryDefault = document.createElement("label");
											labelSubsidiaryDefault.setAttribute("for", `subsidiary_${numberOfSubsidiaries + 1}_radio`);
											labelSubsidiaryDefault.textContent = "Χρήση υποκαταστήματος για την έκδοση παραστατικών";
											labelSubsidiaryDefault.style.fontWeight = "bold";
											labelSubsidiaryDefault.style.display = "inline-block";
											labelSubsidiaryDefault.style.display = "none";
											labelSubsidiaryDefault.style.marginLeft = "5px";

											const subsidiaryFormDefault = document.createElement("form");
											subsidiaryFormDefault.style.display = "none"; // Initially hide the form
											const formFields = ["branchId", "city", "street", "number", "tk", "doy", "phoneNumber"];
											const fieldLabels = ["Αριθμός Εγκατάστασης στο Μητρώο του Taxis: ", "Πόλη: ", "Οδός: ", "Αριθμός: ", "Ταχυδρομικός Κώδικας: ", "ΔΟΥ: ", "Τηλέφωνο: "];
											formFields.forEach((field, i) => {
												const label = document.createElement("label");
												label.textContent = fieldLabels[i];
												label.style.display = "inline-block"; // Set label to inline-block
												label.style.width = "300px"; // Set a fixed width for the labels (adjust as needed)
												label.style.fontFamily = "monospace"; // Use monospaced font for better alignment

												const input = document.createElement("input");
												input.setAttribute("type", "text");
												input.setAttribute("name", `${field}_${numberOfSubsidiaries + 1}`);
												input.style.display = "inline-block"; // Set input to inline-block
												input.style.width = "350px"; // Set a fixed width for the inputs (adjust as needed)
												input.style.fontFamily = "monospace"; // Use monospaced font for better alignment
												subsidiaryFormDefault.appendChild(label);
												subsidiaryFormDefault.appendChild(input);
												subsidiaryFormDefault.appendChild(document.createElement("br")); // Line break for spacing
											});
											let subsidiaryHeading;
											if (subsidiariesData && numberOfSubsidiaries > 0) { //Ελεγχος αν εχει δηλωσει υποκαταστηματα
												subsidiariesData.forEach((subsidiary, index) => {
													subsidiaryHeading = document.createElement("h3");
													const subsidiaryId = `subsidiary_${index + 1}`;
													subsidiaryHeading.setAttribute("id", subsidiaryId);
													subsidiaryHeading.innerHTML = `Υποκατάστημα ${index + 1}`;
													subsidiaryHeading.style.display = "none";
													subsidiaryHeading.style.textAlign = "center";
													h4ElementsArray.push(subsidiaryHeading);

													// Add new radio button after each subsidiary title
													let radioSubsidiary = document.createElement("input");
													radioSubsidiary.setAttribute("type", "radio");
													radioSubsidiary.setAttribute("name", "subsidiaryRadioGroup");
													radioSubsidiary.setAttribute("value", `subsidiary_${index + 1}_radio`);
													radioSubsidiary.setAttribute("id", `option${index + 1}`);
													radioSubsidiary.setAttribute("class", "subsidiary-radio");
													radioSubsidiary.style.display = "inline-block"; // Set to "inline-block" if needed
													radioSubsidiary.style.display = "none";
													radioSubsidiary.style.width = "1.25rem";
													radioSubsidiary.style.height = "1.25rem";
													radioSubsidiary.style.borderRadius = "0";
													radioSubsidiary.style.border = "2px solid #333";
													radioElementsArray.push(radioSubsidiary);

													let labelSubsidiary = document.createElement("label");
													labelSubsidiary.setAttribute("for", `subsidiary_${index + 1}_radio`);
													labelSubsidiary.textContent = "Χρήση υποκαταστήματος για την έκδοση παραστατικών";
													labelSubsidiary.style.fontWeight = "bold";
													labelSubsidiary.style.display = "inline-block"; // Set to "inline-block" if needed
													labelSubsidiary.style.display = "none"
													labelSubsidiary.style.marginLeft = "5px"; // Adjust the margin as needed
													labelElementsArray.push(labelSubsidiary);

													let subsidiaryForm = document.createElement("form");
													subsidiaryForm.style.display = "none"; // Initially hide the form
													let formFields = ["branchId", "city", "street", "number", "tk", "doy", "phoneNumber"];
													let fieldLabels = ["Αριθμός Εγκατάστασης στο Μητρώο του Taxis: ", "Πόλη: ", "Οδός: ", "Αριθμός: ", "Ταχυδρομικός Κώδικας: ", "ΔΟΥ: ", "Τηλέφωνο: "];
													formFields.forEach((field, i) => {
														const label = document.createElement("label");
														label.textContent = fieldLabels[i];
														label.style.display = "inline-block"; // Set label to inline-block
														label.style.width = "300px"; // Set a fixed width for the labels (adjust as needed)
														label.style.fontFamily = "monospace"; // Use monospaced font for better alignment

														const input = document.createElement("input");
														input.setAttribute("type", "text");
														input.setAttribute("name", `${field}_${index + 1}`);
														input.value = subsidiary[field];
														input.readOnly = true; // Set input to read-only
														input.style.display = "inline-block"; // Set input to inline-block
														input.style.width = "350px"; // Set a fixed width for the inputs (adjust as needed)
														input.style.fontFamily = "monospace"; // Use monospaced font for better alignment
														subsidiaryForm.appendChild(label);
														subsidiaryForm.appendChild(input);
														subsidiaryForm.appendChild(document.createElement("br")); // Line break for spacing
													});

													formElementsArray.push(subsidiaryForm);
													//labelSubsidiary.style.display = "none";

													// Append new radio button and label after each subsidiary title
													radioContainer.appendChild(subsidiaryHeading);
													radioContainer.appendChild(radioSubsidiary);
													radioContainer.appendChild(labelSubsidiary);
													radioContainer.appendChild(subsidiaryForm);
												});

												const radioButtons = document.querySelectorAll('input[name="subsidiaryRadioGroup"]');
												radioButtons.forEach((radioButton, index) => {
													radioButton.classList.add('subsidiary-radio');  // Add a common class
												});
											}
											radioContainer.appendChild(insertSubsidiaryTitle);
											radioContainer.appendChild(radioSubsidiaryDefault);
											radioContainer.appendChild(labelSubsidiaryDefault);
											radioContainer.appendChild(subsidiaryFormDefault);




											radioOption2.addEventListener("change", function () {
												// Toggle display based on checked state
												h4ElementsArray.forEach((subsidiaryHeading) => {
													subsidiaryHeading.style.display = this.checked ? "block" : "none";
												});
												radioElementsArray.forEach((radioSubsidiary) => {
													radioSubsidiary.style.display = this.checked ? "inline-block" : "none";
												});
												labelElementsArray.forEach((labelSubsidiary) => {
													labelSubsidiary.style.display = this.checked ? "inline-block" : "none";
												});
												formElementsArray.forEach((form) => {
													form.style.display = this.checked ? "block" : "none";
												});
												ctnBtn.style.margin = '30px auto 0 auto';
												insertSubsidiaryTitle.style.display = this.checked ? "block" : "none";
												radioSubsidiaryDefault.style.display = this.checked ? "inline-block" : "none";
												labelSubsidiaryDefault.style.display = this.checked ? "inline-block" : "none";
												subsidiaryFormDefault.style.display = this.checked ? "block" : "none";
											});

											radioOption1.addEventListener("change", function () {
												// Always set to "none" when option1 is checked
												h4ElementsArray.forEach((subsidiaryHeading) => {
													subsidiaryHeading.style.display = "none";
												});
												radioElementsArray.forEach((radioSubsidiary) => {
													radioSubsidiary.style.display = "none";
												});
												labelElementsArray.forEach((labelSubsidiary) => {
													labelSubsidiary.style.display = "none";
												});
												formElementsArray.forEach((form) => {
													form.style.display = "none";
												});
												ctnBtn.style.margin = '100px auto 0 auto';
												insertSubsidiaryTitle.style.display = "none";
												radioSubsidiaryDefault.style.display = "none";
												labelSubsidiaryDefault.style.display = "none";
												subsidiaryFormDefault.style.display = "none";
												errorMessageSubsidiary.innerHTML = "";
											});




											content.setAttribute("hidden", true);
											tit.style.display = "none";
											btnact.style.display = "none";
											divider2.style.display = "none";
											btncl.style.display = "none";
											facilities_title.style.display = "block";
											facilities_info.style.display = "block";
											radioContainer.style.display = "block";
											ctnBtn.style.margin = '100px auto 0 auto';
											ctnBtn.style.display = "block";
											cnlBtn.style.display = "block";

											var subsidiaryBranchIdValue = $('#subsidiaryBranchId').val();
											if (subsidiaryBranchIdValue === '' || isNaN(subsidiaryBranchIdValue)) {
												radioOption1.setAttribute("checked", "checked");
											} else {
												radioOption2.setAttribute("checked", "checked");
												var matchingInput = $('input[name="branchId_' + subsidiaryBranchIdValue + '"]');

												//insertSubsidiaryTitle.style.display = "block";

												//$('input[name="subsidiaryRadioGroup"][value="subsidiary_' + subsidiaryBranchIdValue + '_radio"]').prop('checked', true);
												for (var index = 0; index < numberOfSubsidiaries; index++) {
													h4ElementsArray[index].style.display = "block";
													radioElementsArray[index].style.display = "inline-block";
													labelElementsArray[index].style.display = "inline-block";
													formElementsArray[index].style.display = "block";
												}

												insertSubsidiaryTitle.style.display = "block";
												radioSubsidiaryDefault.style.display = "inline-block" ;
												labelSubsidiaryDefault.style.display = "inline-block";
												subsidiaryFormDefault.style.display = "block" ;
												// Check if the input is found
												if (matchingInput.length > 0) {
													// Get the value of the found input
													var branchIdValue = matchingInput.val();
													var radioToCheck = $(`input[name="subsidiaryRadioGroup"][value="subsidiary_${subsidiaryBranchIdValue}_radio"]`);
													if (radioToCheck.length > 0) {
														radioToCheck.prop('checked', true);
													} else {
														console.log("No matching radio button found for subsidiary_" + subsidiaryBranchIdValue);
													}
													console.log("Value of branchId_" + subsidiaryBranchIdValue + ":", branchIdValue);
												} else {
													console.log("No matching input found for branchId_" + subsidiaryBranchIdValue);
												}
											}
											console.log('edw blepw to branch poy exoume');
											console.log(subsidiaryBranchIdValue);

											ctnBtn.onclick = function productAct() {
												const radioButtons = document.querySelectorAll('input[name="subsidiaryRadioGroup"]');
												let noneChecked = true;
												radioButtons.forEach((radioButton) => {
													if (radioButton.checked) {
														noneChecked = false;
													}
												});
												const lastRadioButton = radioButtons[radioButtons.length - 1];
												let allFilled = true;
												if (
													$('input[name="branchId_' + radioButtons.length + '"]').val() === "" ||
													$('input[name="city_' + radioButtons.length + '"]').val() === "" ||
													$('input[name="street_' + radioButtons.length + '"]').val() === "" ||
													$('input[name="number_' + radioButtons.length + '"]').val() === "" ||
													$('input[name="tk_' + radioButtons.length + '"]').val() === "" ||
													$('input[name="doy_' + radioButtons.length + '"]').val() === "" ||
													$('input[name="phoneNumber_' + radioButtons.length + '"]').val() === ""
												) {
													allFilled = false;
												}

												if (radioOption2.checked && noneChecked) {
													errorMessageSubsidiary.innerHTML = "Παρακαλώ επιλέξτε υποκατάστημα";
												} else if (radioOption2.checked && lastRadioButton.checked && !allFilled) {
													errorMessageSubsidiary.innerHTML = "Παρακαλώ συμπληρώστε όλα τα πεδία του νέου υποκαταστήματος"
												} else if (radioOption2.checked && lastRadioButton.checked && allFilled) {
													errorMessageSubsidiary.innerHTML = "";
													serialNumber.innerHTML = "<b>Σειριακός αριθμός: </b>" + messageObject.serialNumber;
													companyName.innerHTML = "<br><b>Επωνυμία Εταιρείας: </b>" + messageObject.companyName;
													companySmallName.innerHTML = "<br><b>Διακριτικός Τίτλος: </b>" + messageObject.companySmallName;
													companyAddress.innerHTML = "<br><b>Διεύθυνση: </b>" + messageObject.companyAddress;
													companyCity.innerHTML = "<br><b>Πόλη: </b>" + messageObject.companyCity;
													companyTk.innerHTML = "<br><b>Ταχυδρομικός Κώδικας: </b>" + messageObject.companyTk;
													companyVat.innerHTML = "<br><b>Α.Φ.Μ: </b>" + messageObject.companyVatNumber;
													companyDoy.innerHTML = "<br><b>Δ.Ο.Υ: </b>" + messageObject.companyDoy;
													gemhNumber.innerHTML = "<br><b>Αριθμός ΓΕΜΗ: </b>" + messageObject.gemh;
													companyPhoneNumber.innerHTML = "<br><b>Τηλέφωνο: </b>" + messageObject.companyPhoneNumber;
													companyEmail.innerHTML = "<br><b>Email: </b>" + messageObject.companyEmail;
													companyActivity.innerHTML = "<br><b>Δραστηριότητα: </b>" + messageObject.companyActivity;
													representativeName.innerHTML = "<br><b>Όνομα Νόμιμου Εκπροσώπου: </b>" + messageObject.representativeName;
													representativeSurname.innerHTML = "<br><b>Επώνυμο Νόμιμου Εκπροσώπου: </b>" + messageObject.representativeSurname;
													representativeVatNumber.innerHTML = "<br><b>Α.Φ.Μ Νόμιμου Εκπροσώπου: </b>" + messageObject.representativeVatNumber;
													if (messageObject.companyMobilePhoneNumber !== null) {
														companyMobilePhoneNumber.innerHTML = "<br><b>Κινητό ενεργοποίησης: </b>" + messageObject.companyMobilePhoneNumber;
													} else {
														companyMobilePhoneNumber.innerHTML += "";  // Set to an empty string if null
													}
													facilities_title.style.display = "none";
													facilities_info.style.display = "none";
													radioContainer.style.display = "none";
													ctnBtn.style.display = "none";
													cnlBtn.style.display = "none";
													activationTile.style.display = "block";
													activationTileInfo.style.display = "block";
													serialNumber.style.display = "block";
													companyName.style.display = "block";
													companySmallName.style.display = "block";
													companyAddress.style.display = "block";
													companyCity.style.display = "block";
													companyTk.style.display = "block";
													companyVat.style.display = "block";
													companyDoy.style.display = "block";
													gemhNumber.style.display = "block";
													companyPhoneNumber.style.display = "block";
													companyEmail.style.display = "block";
													companyActivity.style.display = "block";
													representativeName.style.display = "block";
													representativeSurname.style.display = "block";
													representativeVatNumber.style.display = "block";
													companyMobilePhoneNumber.style.display = "block";
													if (radioOption1.checked) {
														selectedFacility.style.display = "block";
														lastComfirmation.style.display = "block"
														completeBtn.style.display = "block";
														backBtn.style.display = "block";
													} else {
														selectedSubsidiary.style.display = "block";
														subsidiaryBrandId.style.display = "block";
														subsidiaryCity.style.display = "block";
														subsidiaryStreet.style.display = "block";
														subsidiaryNumber.style.display = "block";
														subsidiaryΤΚ.style.display = "block";
														subsidiaryDoy.style.display = "block";
														subsidiaryPhone.style.display = "block";
														lastComfirmation.style.display = "block"
														completeBtn.style.display = "block";
														backBtn.style.display = "block";
														let selectedIndex;
														radioButtons.forEach((radioButton, index) => {
															if (radioButton.checked) {
																selectedIndex = index + 1;
																//console.log(selectedIndex);
															}
														});
														const branchIdValue = $(`input[name="branchId_${selectedIndex}"]`).val();
														subsidiaryBrandId.innerHTML = `<br><b>Αριθμός Εγκατάστασης στο Μητρώο του Taxis: </b>${$('input[name="branchId_' + selectedIndex + '"]').val().trim()}`;
														subsidiaryCity.innerHTML = `<br><b>Πόλη: </b>${$('input[name="city_' + selectedIndex + '"]').val().trim()}`;
														subsidiaryStreet.innerHTML = `<br><b>Οδός: </b>${$('input[name="street_' + selectedIndex + '"]').val().trim()}`;
														subsidiaryNumber.innerHTML = `<br><b>Αριθμός: </b>${$('input[name="number_' + selectedIndex + '"]').val().trim()}`;
														subsidiaryΤΚ.innerHTML = `<br><b>Ταχυδρομικός Κώδικας: </b>${$('input[name="tk_' + selectedIndex + '"]').val().trim()}`;
														subsidiaryDoy.innerHTML = `<br><b>Δ.Ο.Υ: </b>${$('input[name="doy_' + selectedIndex + '"]').val().trim()}`;
														subsidiaryPhone.innerHTML = `<br><b>Τηλέφωνο: </b>${$('input[name="phoneNumber_' + selectedIndex + '"]').val().trim()}`;

														completeBtn.onclick = function completeAct() {
															var subsidiaryData = {
																"branchId": $(`input[name="branchId_${selectedIndex}"]`).val().trim(),
																"phoneNumber": $(`input[name="phoneNumber_${selectedIndex}"]`).val().trim(),
																"street": $(`input[name="street_${selectedIndex}"]`).val().trim(),
																"number": $(`input[name="number_${selectedIndex}"]`).val().trim(),
																"tk": $(`input[name="tk_${selectedIndex}"]`).val().trim(),
																"doy": $(`input[name="doy_${selectedIndex}"]`).val().trim(),
																"city": $(`input[name="city_${selectedIndex}"]`).val().trim()
															};

															var subsidiaries = [subsidiaryData];

															console.log(subsidiaryData);
															console.log(subsidiaries);
															//companyActivation neo upokatastima

															var username = $('#primer_licenses #license_user').val();
															var password = $('#primer_licenses #license_password').val();
															var company_name = messageObject.companyName;
															var small_name = messageObject.companySmallName;
															var activity = messageObject.companyActivity;
															var c_phone = messageObject.companyPhoneNumber;
															var c_vat = messageObject.companyVatNumber;
															var gemh = messageObject.gemh;
															var cp_adress = messageObject.companyAddress;
															var rname = messageObject.representativeName;
															var rsname = messageObject.representativeSurname;
															var rvat = messageObject.representativeVatNumber;
															var cp_city = messageObject.companyCity;
															var cp_email = messageObject.companyEmail;
															var cp_tk = messageObject.companyTk;
															var doy = messageObject.companyDoy;
															var subsidiariesJSON = JSON.stringify(subsidiaries);

															var data = {
																'username': username,
																'password': password,
																'company_name': company_name,
																'small_name': small_name,
																'activity': activity,
																'c_phone': c_phone,
																'c_vat': c_vat,
																'gemh': gemh,
																'cp_adress': cp_adress,
																'r_name': rname,
																'r_sname': rsname,
																'r_vat': rvat,
																'cp_city': cp_city,
																'cp_email': cp_email,
																'cp_tk': cp_tk,
																'doy': doy,
																'subsidiaries': subsidiariesJSON,
																'action': 'company_activation_call'
															}

															console.log(data);
															console.log('AJAX Data:', data);

															$.ajax({
																// First AJAX call
																url: ajaxurl,
																method: "POST",
																dataType: "JSON",
																data: data,
																beforeSend: function () {
																	$('#completeBtn').addClass('disabled');
																	$('#completeBtn').text('Σε Εξέλιξη');
																}
															})
																.then(function(response) {
																	// Handle the response of the first AJAX call
																	// ...

																	// Return a promise for the second AJAX call
																	return $.ajax({
																		// Second AJAX call
																		url: ajaxurl,
																		method: "POST",
																		dataType: "JSON",
																		data: data,
																		beforeSend: function () {
																			$('#completeBtn').addClass('disabled');
																			$('#completeBtn').text('Σε Εξέλιξη');
																		}
																	});
																})
																.then(function(response) {
																	// Handle the response of the second AJAX call
																	// ...

																	// Prepare data for the third AJAX call
																	var license_key = $('#primer_licenses #license_key').val();
																	var username = $('#primer_licenses #license_user').val();
																	var password = $('#primer_licenses #license_password').val();
																	var check_reset = false;

																	var data = {
																		'licenseKey': license_key,
																		'username': username,
																		'password': password,
																		'check_reset': check_reset,
																		'action': 'primer_insert_license',
																	};

																	// Return a promise for the third AJAX call
																	return $.ajax({
																		url: primer_ajax_obj.ajax_url,
																		method: "POST",
																		dataType: "JSON",
																		data: data,
																		beforeSend: function () {
																			$('#completeBtn').addClass('disabled');
																			$('#completeBtn').text('Σε Εξέλιξη');
																		}
																	});
																})
																.then(function(response) {
																	// Handle the response of the third AJAX call
																	// ...

																	// The third AJAX call is done, now initiate the fourth AJAX call
																	var branchID = $(`input[name="branchId_${selectedIndex}"]`).val().trim();

																	return $.ajax({
																		url: ajaxurl,
																		method: "POST",
																		dataType: "JSON",
																		data: {
																			action: 'change_subsidiary',
																			branchID: branchID,
																		},
																	});
																})
																.then(function(response) {
																	// Handle the response of the fourth AJAX call
																	// This block will be executed after the fourth AJAX call completes
																	console.log(response);
																	if (response.success) {
																		console.log('Success:', response.message);
																		alert(response.message);
																		location.reload();
																	} else {
																		console.error('Error:', response.message);
																		alert('Error: ' + response.message);
																	}
																})
																.catch(function(error) {
																	// Handle errors in the promise chain
																	console.error('An error occurred in the promise chain:', error);
																})
																.finally(function() {
																	// This block will be executed regardless of success or failure
																	$('#completeBtn').removeClass('disabled');
																	$('#completeBtn').text('Ολοκληρώθηκε');
																});

															// var data = {
															// 	'licenseKey': license_key,
															// 	'username': username,
															// 	'password': password,
															// 	'check_reset': check_reset,
															// 	'action': 'primer_insert_license',
															// }
															//
															// $.ajax({
															// 	url: primer_ajax_obj.ajax_url,
															// 	method: "POST",
															// 	dataType: "JSON",
															// 	data: data,
															// 	success: function (response) {
															// 		console.log(response);
															// 		if (response.check_message) {
															// 			if(response.messagephoto) {
															// 				alert("Το προϊόν ενεργοποιήθηκε επιτυχώς." + response.messagephoto);
															// 			}else{
															//
															// 				alert("Το προϊόν ενεργοποιήθηκε επιτυχώς.");
															// 			}
															// 			document.location.reload();
															// 		} else {
															// 			alert(response.message);
															// 			document.location.reload();
															// 		}
															// 		setTimeout(function () {
															// 			$('form#primer_licenses').css({'opacity': '1'});
															// 		}, 1500);
															// 	}
															// })
														}
													}
												} else {
													//αλλαγη υποκαταστηματος απο τα ήδη δηλωμένα στην primer
													if (radioOption1.checked) {
														var branchID = 0;
													} else {
														const selectedIndex = $('input[name="subsidiaryRadioGroup"]:checked').index('input[name="subsidiaryRadioGroup"]') + 1;
														var branchID = $('input[name="branchId_' + selectedIndex + '"]').val().trim();
													}

													$.ajax({
														url: ajaxurl,
														method: "POST",
														dataType: "JSON",
														data: {
															action: 'change_subsidiary',
															branchID: branchID,
														},
														beforeSend: function () {
															$('form#primer_licenses').css({'opacity': '0.5'});
															$('#ctnBtn').addClass('disabled');
															$('#ctnBtn').text('Σε Εξέλιξη');
														},
														success: function (response) {
															console.log(response);
															//console.log(response?.what)
															if (response.success) {
																// Success
																alert(response.message);
																location.reload(); // Reload the page
																return;
															} else {
																// Error
																console.error('Error:', response.message);
																alert(response.message);
																location.reload();
																return;
															}
														},
														error: function (jqXHR, textStatus, errorThrown) {
															// Handle the AJAX request error
															console.error('AJAX Error:', textStatus, errorThrown);
															alert('AJAX Error: ' + textStatus); // Show a generic error message
														},
													});

												}
											}
											backBtn.onclick = function goBack() {
												facilities_title.style.display = "block";
												facilities_info.style.display = "block";
												radioContainer.style.display = "block";
												ctnBtn.style.display = "block";
												cnlBtn.style.display = "block";

												activationTile.style.display = "none";
												activationTileInfo.style.display = "none";
												serialNumber.style.display = "none";
												serialNumber.innerHTML = "";
												companyName.style.display = "none";
												companyName.innerHTML = "";
												companySmallName.style.display = "none";
												companySmallName.innerHTML = "";
												companyAddress.style.display = "none";
												companyAddress.innerHTML = "";
												companyCity.style.display = "none";
												companyCity.innerHTML = "";
												companyTk.style.display = "none";
												companyTk.innerHTML = "";
												companyVat.style.display = "none";
												companyVat.innerHTML = "";
												companyDoy.style.display = "none";
												companyDoy.innerHTML = "";
												gemhNumber.style.display = "none";
												gemhNumber.innerHTML = "";
												companyPhoneNumber.style.display = "none";
												companyPhoneNumber.innerHTML = "";
												companyEmail.style.display = "none";
												companyEmail.innerHTML = "";
												companyActivity.style.display = "none";
												companyActivity.innerHTML = "";
												representativeName.style.display = "none";
												representativeName.innerHTML = "";
												representativeSurname.style.display = "none";
												representativeSurname.innerHTML = "";
												representativeVatNumber.style.display = "none";
												representativeVatNumber.innerHTML = "";
												companyMobilePhoneNumber.style.display = "none";
												companyMobilePhoneNumber.innerHTML = "";
												selectedFacility.style.display = "none";
												lastComfirmation.style.display = "none"
												completeBtn.style.display = "none";
												backBtn.style.display = "none";
												selectedSubsidiary.style.display = "none";
												subsidiaryBrandId.style.display = "none";
												subsidiaryCity.style.display = "none";
												subsidiaryStreet.style.display = "none";
												subsidiaryNumber.style.display = "none";
												subsidiaryΤΚ.style.display = "none";
												subsidiaryDoy.style.display = "none";
												subsidiaryPhone.style.display = "none";
												completeBtn.style.display = "none";
												backBtn.style.display = "none";
											}

										},
										error: function () {
											alert("Πρόβλημα σύνδεσης. Παρακαλώ δοκιμάστε αργότερα");
											document.location.reload();
										}

									})

									cnlBtn.onclick = function cancelAct() {
										document.location.reload();
									}

								}
							}
							btnact.innerHTML = "ΑΠΟΔΟΧΗ";
							const btncl= document.createElement("button");
							btncl.setAttribute("class","button-danger");
							btncl.setAttribute("id","decline_btn");
							btncl.innerHTML = "ΑΠΟΡΡΙΨΗ";
							btncl.onclick= function closemod(){
								newDiv.style.display="none";
							}
							const BR = document.createElement("br");
							const divider2=document.createElement("div");
							divider2.setAttribute("class","divider");

							const headingElement = document.createElement("h3");
							headingElement.textContent = "Εισάγετε τον Α.Φ.Μ της εταιρείας σας";
							headingElement.style.display = "none";
							const afmText = document.createElement("p");
							afmText.textContent = "Εισάγετε το Α.Φ.Μ της εταιρείας σας παρακάτω όπως δηλώνεται στην ΑΑΔΕ για να πραγματοποιηθεί ο έλεγχος εγκυρότητας Α.Φ.Μ .";
							afmText.style.display = "none";
							const afmInputDiv = document.createElement("div");
							afmInputDiv.setAttribute("class", "vat-input");
							afmInputDiv.style.display = "none";
							afmInputDiv.style.textAlign = "center";
							afmInputDiv.style.width = "100%";
							afmInputDiv.style.marginTop = "150px";

							const afmInputLabel = document.createElement("strong");
							afmInputLabel.innerHTML = "Α.Φ.Μ Εταιρεία: ";
							//afmInputLabel.insertAdjacentHTML('afterend', '<br>');


							const afmInput = document.createElement("input");
							afmInput.setAttribute("type", "text");
							afmInput.setAttribute("oninput", "this.value = this.value.replace(/\\D/g, '');");
							afmInput.setAttribute('maxlength', "9");
							afmInput.setAttribute("name", "vat_company_number");
							afmInput.setAttribute("id", "vat_company_number");
							afmInput.setAttribute("class", "cmb2-text-medium");
							afmInputDiv.style.margin = "0 auto";
							afmInput.style.width = "calc(100% - 20px)";
							afmInput.style.margin = "0 10px";

							const continueBtn = document.createElement("button");
							continueBtn.setAttribute("class", "button-primary");
							continueBtn.setAttribute("id", "continue_btn");
							continueBtn.style.margin = '10px auto 0 auto'; //
							continueBtn.innerHTML = "Έλεγχος και Συνέχεια";
							continueBtn.style.display = "none";
							continueBtn.style.width = "calc(100% - 20px)";

							const cancelBtn = document.createElement("button");
							cancelBtn.setAttribute("class", "button-danger");
							cancelBtn.setAttribute("id", "cancel_btn");
							cancelBtn.style.margin = '10px auto 0 auto'; //
							cancelBtn.innerHTML = "Ακύρωση";
							cancelBtn.style.display = "none";
							cancelBtn.style.width = "calc(100% - 20px)";


// Add the label, input, and button to the same vat-input div
							afmInputDiv.appendChild(afmInputLabel);
							afmInputDiv.appendChild(afmInput);
							afmInputDiv.appendChild(continueBtn);
							afmInputDiv.appendChild(cancelBtn);


							//newDiv.appendChild(btncl);
							//newDiv.appendChild(afmInputDiv);

							const errorMessage = document.createElement("div");
							errorMessage.setAttribute("id", "error_message"); // Set an ID for styling or manipulation
							errorMessage.style.color = "red";

							newDiv.appendChild(tit);
							newDiv.appendChild(BR);
							newDiv.appendChild(content);
							//newDiv.appendChild(BR);
							newDiv.appendChild(btnact);
							newDiv.appendChild(divider2);
							newDiv.appendChild(btncl);
							newDiv.appendChild(facilities_title);
							newDiv.appendChild(facilities_info);
							newDiv.appendChild(radioContainer);
							newDiv.appendChild(headingElement);
							//newDiv.appendChild(insertSubsidiaryTitle);
							newDiv.appendChild(errorMessageSubsidiary);
							newDiv.appendChild(ctnBtn);
							newDiv.appendChild(cnlBtn);
							newDiv.appendChild(activationTile);
							newDiv.appendChild(activationTileInfo);
							newDiv.appendChild(serialNumber);
							newDiv.appendChild(companyName);
							newDiv.appendChild(companySmallName);
							newDiv.appendChild(companyAddress);
							newDiv.appendChild(companyCity);
							newDiv.appendChild(companyTk);
							newDiv.appendChild(companyVat);
							newDiv.appendChild(companyDoy);
							newDiv.appendChild(gemhNumber);
							newDiv.appendChild(companyPhoneNumber);
							newDiv.appendChild(companyEmail);
							newDiv.appendChild(companyActivity);
							newDiv.appendChild(representativeName);
							newDiv.appendChild(representativeSurname);
							newDiv.appendChild(representativeVatNumber);
							newDiv.appendChild(companyMobilePhoneNumber);
							newDiv.appendChild(selectedSubsidiary);
							newDiv.appendChild(subsidiaryBrandId);
							newDiv.appendChild(subsidiaryCity);
							newDiv.appendChild(subsidiaryStreet);
							newDiv.appendChild(subsidiaryNumber);
							newDiv.appendChild(subsidiaryΤΚ);
							newDiv.appendChild(subsidiaryDoy);
							newDiv.appendChild(subsidiaryPhone);
							newDiv.appendChild(selectedFacility);
							newDiv.appendChild(lastComfirmation);
							newDiv.appendChild(completeBtn);
							newDiv.appendChild(backBtn);
							newDiv.appendChild(afmText);
							newDiv.appendChild(afmInputDiv);
							newDiv.appendChild(continueBtn);
							newDiv.appendChild(cancelBtn);
							newDiv.appendChild(errorMessage);

							const wpwrap = document.getElementById("wpwrap");
							document.body.insertBefore(newDiv, wpwrap);
						}
						popupOpenClose('.primer_popup');
						//document.location.reload();
					}else if (response.message === "Credentials are required to access this resource.") {
						alert(response.message);
						document.location.reload();
					}else {
					//	var myDocument = document.getElementById("modal_first_time");
						const content = document.createElement("div");
						content.setAttribute("class", "title_terms");
						content.setAttribute("style", "text-align:justify;");
						$.ajax({
							url : response.url_for_txt,
							dataType: "text",
							success : function (data) {
								content.innerHTML=data;
							},
							error:function(data){
								console.log(data);
								alert("fail servlet");
							}
						});
						if (!myDocument) {
							const newDiv = document.createElement("div");
							newDiv.setAttribute("id", "modal_first_time");
							newDiv.setAttribute("class", "primer_popup");
							newDiv.setAttribute("hidden", true);
							const newContent = document.createTextNode("TERMS OF USE");
							const tit = document.createElement("h3");
							tit.setAttribute("class", "title");
							tit.innerHTML = response.terms;
							const btnact = document.createElement("button");
							btnact.setAttribute("class", "button-primary");
							btnact.setAttribute("id", "accept_btn");
							btnact.innerHTML = "ΑΠΟΔΟΧΗ";
							const btncl = document.createElement("button");
							btncl.setAttribute("class", "button-danger");
							btncl.setAttribute("id", "decline_btn");
							btncl.innerHTML = "ΑΠΟΡΡΙΨΗ";
							btncl.onclick = function closemodcp() {
								newDiv.style.display = "none";
							}


							const headingElement = document.createElement("h3");
							headingElement.textContent = "Εισάγετε τον Α.Φ.Μ της εταιρείας σας";
							headingElement.style.display = "none";
							const afmText = document.createElement("p");
							afmText.textContent = "Εισάγετε το Α.Φ.Μ της εταιρείας σας παρακάτω, όπως δηλώνεται στην ΑΑΔΕ για να πραγματοποιηθεί ο έλεγχος εγκυρότητας Α.Φ.Μ .";
							afmText.style.display = "none";
							const afmInputDiv = document.createElement("div");
							afmInputDiv.setAttribute("class", "vat-input");
							afmInputDiv.style.display = "none";
							afmInputDiv.style.textAlign = "center";
							afmInputDiv.style.width = "100%";
							afmInputDiv.style.marginTop = "150px";

							const afmInputLabel = document.createElement("strong");
							afmInputLabel.innerHTML = "ΑΦΜ Εταιρείας:";

							const afmInput = document.createElement("input");
							afmInput.setAttribute("type", "text");
							afmInput.setAttribute("oninput", "this.value = this.value.replace(/\\D/g, '');");
							afmInput.setAttribute('maxlength', "9");
							afmInput.setAttribute("name", "vat_company_number");
							afmInput.setAttribute("id", "vat_company_number");
							afmInput.setAttribute("class", "cmb2-text-medium");
							afmInput.style.width = "calc(100% - 20px)";
							afmInput.style.margin = "0 10px";

							const continueBtn = document.createElement("button");
							continueBtn.setAttribute("class", "button-primary");
							continueBtn.setAttribute("id", "continue_btn");
							continueBtn.style.margin = '10px auto 0 auto'; //
							continueBtn.innerHTML = "Έλεγχος και Συνέχεια";
							continueBtn.style.display = "none";
							continueBtn.style.width = "calc(100% - 20px)";


							const cancelBtn = document.createElement("button");
							cancelBtn.setAttribute("class", "button-danger");
							cancelBtn.setAttribute("id", "cancel_btn");
							cancelBtn.style.margin = '10px auto 0 auto'; //
							cancelBtn.innerHTML = "Ακύρωση";
							cancelBtn.style.display = "none";
							cancelBtn.style.width = "calc(100% - 20px)";





// Add the label, input, and button to the same vat-input div
							afmInputDiv.appendChild(afmInputLabel);
							afmInputDiv.appendChild(afmInput);
							afmInputDiv.appendChild(continueBtn);
							afmInputDiv.appendChild(cancelBtn);

							//newDiv.appendChild(btncl);
							//newDiv.appendChild(afmInputDiv);

							const errorMessage = document.createElement("div");
							errorMessage.setAttribute("id", "error_message"); // Set an ID for styling or manipulation
							errorMessage.style.color = "red";
							//newDiv.appendChild(errorMessage);
							document.body.appendChild(newDiv);

// ... (continue with the rest of your code)
							cancelBtn.onclick= function closemodal(){
								newDiv.style.display="none";

							}
							continueBtn.addEventListener("click", function() {
								// Assuming you have the vatNumber value
								const vatNumber = afmInput.value;
								// Your AJAX request using jQuery
								$.ajax({
									url: ajaxurl, // Replace with your actual URL
									method: "POST",
									dataType: "JSON",
									data: {
										action: 'company_vat_call', // Replace with your actual WordPress AJAX action
										vatNumber: vatNumber,
									},
									success: function(data) {

										// Check if the response has an 'Error' property
										if (data && data.Error) {
											// Display the error message
											errorMessage.innerHTML = data.Error;
										} else {
											// Clear any previous error messages
											errorMessage.innerHTML = "";
											headingElement.style.display = "none";
											afmText.style.display = "none";
											afmInputDiv.style.display = "none"; // Set display to block when "ΑΠΟΔΟΧΗ" is clicked
											continueBtn.style.display = "none";
											cancelBtn.style.display = "none";
											errorMessage.style.display = "none";
											cp_title.style.display = "block";
											cp_info.style.display = "block";
											cp_form.style.display = "block";
											//btnclo.setAttribute("hidden","false");
											content.setAttribute("hidden", true);
											$("#cp_name").val(data.name.trim()).prop('readonly', true).attr('title', data.name);
											$("#s_name").val(data.smallName.trim()).prop('readonly', true).attr('title', data.smallName);
											$("#activity").val(data.activity.trim()).prop('readonly', true).attr('title', data.activity);
											$("#adress").val(data.address.trim()).prop('readonly', true).attr('title', data.address);
											$("#vat_number").val(data.vatNumber.trim()).prop('readonly', true).attr('title', data.vatNumber);
											$("#city").val(data.city.trim()).prop('readonly', true).attr('title', data.city);
											$("#cptk").val(data.tk.trim()).prop('readonly', true).attr('title', data.tk);
											$("#doy").val(data.doy.trim()).prop('readonly', true).attr('title', data.doy);
											// const divider = document.querySelector(".divider");
											// const brElement = document.querySelector("br");
											//
											// if (divider) {
											// 	divider.parentNode.removeChild(divider);
											// }
											//
											// if (brElement) {
											// 	brElement.parentNode.removeChild(brElement);
											// }
											const primerPopup = document.querySelector('.primer_popup > div');
											primerPopup.style.maxWidth = '700px';
										}
									},
									error: function(data) {
										console.log(data);
										alert("fail servlet");
									}
								});
							});




							const BR = document.createElement("br");
							const cp_title = document.createElement("h3");
							cp_title.innerHTML = "Company Activation";
							cp_title.setAttribute("class", "title");
							cp_title.style.display = "none";
							const cp_info = document.createElement("span");
							cp_info.setAttribute("hidden", true);
							cp_info.innerHTML = "Αυτή είναι η πρώτη φορά που ενεργοποιείται ο σειριακός αριθμός του προϊόντος.<br>Συμπληρώστε με προσοχή τα στοιχεία της επιχείρησης σας όπως αναγράφονται στην ιστοσελίδα του taxisnet<br>(όπως είναι δηλωμένα στην αρμόδια εφορία). <br><br>";
							const cp_form = document.createElement("form");
							cp_form.setAttribute("name", "myform");
							cp_form.setAttribute("id", "myform");
							cp_form.setAttribute("method", "POST");
							cp_form.style.display = "none";
							const cpnamediv= document.createElement("div");
							cpnamediv.setAttribute("class","left_detail");
							const cpname = document.createElement("label");
							cpname.innerHTML = "Επωνυμία Εταιρείας:";
							cpname.setAttribute("class","cp_name");
							const cp_name = document.createElement("input");
							cp_name.setAttribute("type", "text");
							cp_name.setAttribute("required", "");
							cp_name.setAttribute("maxlength", "100");
							cp_name.setAttribute("name", "cp_name");
							cp_name.setAttribute("id", "cp_name");
							cp_name.setAttribute("class", "cmb2-text-medium");
							cpnamediv.appendChild(cpname);
							cpnamediv.appendChild(cp_name);
							cp_form.appendChild(cpnamediv);
							const cpsnamediv= document.createElement("div");
							cpsnamediv.setAttribute("class","right_detail");
							const sname = document.createElement("label");
							sname.innerHTML = "Διακριτικός Τίτλος:";
							sname.setAttribute("class","cp_sname");
							const s_name = document.createElement("input");
							s_name.setAttribute("type", "text");
							sname.setAttribute("style","padding-right: 30px;");
							s_name.setAttribute("maxlength", "70");
							s_name.setAttribute("name", "s_name");
							s_name.setAttribute("id", "s_name");
							s_name.setAttribute("class", "cmb2-text-medium");
							cpsnamediv.appendChild(sname);
							cpsnamediv.appendChild(s_name);
							cp_form.appendChild(cpsnamediv);
							const cpactivitydiv= document.createElement("div");
							cpactivitydiv.setAttribute("class","left_detail");
							const cpactivity = document.createElement("label");
							cpactivity.innerHTML = "Δραστηριότητα:";
							cpactivity.setAttribute("class","company_activity");
							cpactivity.setAttribute("style","padding-right: 30px;");
							const cp_activity = document.createElement("input");
							cp_activity.setAttribute("type", "text");
							cp_activity.setAttribute("name", "activity");
							cp_activity.setAttribute("required", "");
							cp_activity.setAttribute("maxlength", "70");
							cp_activity.setAttribute("id", "activity");
							cp_activity.setAttribute("class", "cmb2-text-medium");
							cpactivitydiv.appendChild(cpactivity);
							cpactivitydiv.appendChild(cp_activity);
							cp_form.appendChild(cpactivitydiv);
							const cpadressdiv= document.createElement("div");
							cpadressdiv.setAttribute("class","right_detail");
							const cpadress = document.createElement("label");
							cpadress.innerHTML = "Διεύθυνση:";
							cpadress.setAttribute("class","cp_adress");
							cpadress.setAttribute("style","padding-right: 90px;");
							const cp_adress = document.createElement("input");
							cp_adress.setAttribute("type", "text");
							cp_adress.setAttribute("required", "");
							cp_adress.setAttribute("maxlength", "70");
							cp_adress.setAttribute("name", "adress");
							cp_adress.setAttribute("id", "adress");
							cp_adress.setAttribute("class", "cmb2-text-medium");
							cpadressdiv.appendChild(cpadress);
							cpadressdiv.appendChild(cp_adress);
							cp_form.appendChild(cpadressdiv);
							const cpvatdiv= document.createElement("div");
							cpvatdiv.setAttribute("class","left_detail");
							const cpvat = document.createElement("label");
							cpvat.innerHTML = "ΑΦΜ:";
							cpvat.setAttribute("class","company_activity");
							cpvat.setAttribute("style","padding-right: 108px;");
							const cp_vat = document.createElement("input");
							cp_vat.setAttribute("type", "text");
							cp_vat.setAttribute("pattern", "^[0-9]*$");
							cp_vat.setAttribute("required", "");
							cp_vat.setAttribute("maxlength", "9");
							cp_vat.setAttribute("minlength", "9");
							cp_vat.setAttribute("name", "vat_number");
							cp_vat.setAttribute("id", "vat_number");
							cp_vat.setAttribute("class", "cmb2-text-medium");
							cpvatdiv.appendChild(cpvat);
							cpvatdiv.appendChild(cp_vat);
							cp_form.appendChild(cpvatdiv);
							const cpgemhdiv= document.createElement("div");
							cpgemhdiv.setAttribute("class","right_detail");
							const cpgemh = document.createElement("label");
							cpgemh.innerHTML = "ΓΕΜΗ:";
							cpgemh.setAttribute("class","company_activity");
							cpgemh.setAttribute("style","padding-right: 123px;");
							const cp_gemh = document.createElement("input");
							cp_gemh.setAttribute("type", "text");
							cp_gemh.setAttribute("pattern", "^[0-9]*$");
							cp_gemh.setAttribute("name", "gemh");
							cp_gemh.setAttribute("maxlength", "14");
							cp_gemh.setAttribute("id", "gemh");
							cp_gemh.setAttribute("class", "cmb2-text-medium");
							cpgemhdiv.appendChild(cpgemh);
							cpgemhdiv.appendChild(cp_gemh);
							cp_form.appendChild(cpgemhdiv);
							const cpphonediv= document.createElement("div");
							cpphonediv.setAttribute("class","left_detail");
							const cpphone = document.createElement("label");
							cpphone.innerHTML = "Τηλέφωνο:";
							cpphone.setAttribute("class","company_activity");
							cpphone.setAttribute("style","padding-right: 71px;");
							const cp_phone = document.createElement("input");
							cp_phone.setAttribute("type", "text");
							cp_phone.setAttribute("required", "");
							cp_phone.setAttribute("pattern", "^[0-9\+\.\s]+$");
							cp_phone.setAttribute("maxlength", "13");
							cp_phone.setAttribute("name", "phone");
							cp_phone.setAttribute("id", "phone");
							cp_phone.setAttribute("class", "cmb2-text-medium");
							cpphonediv.appendChild(cpphone);
							cpphonediv.appendChild(cp_phone);
							cp_form.appendChild(cpphonediv);
							const cpemaildiv= document.createElement("div");
							cpemaildiv.setAttribute("class","right_detail");
							const cpemail = document.createElement("label");
							cpemail.innerHTML = "Email Επιχείρησης:";
							cpemail.setAttribute("class","company_activity");
							cpemail.setAttribute("style","padding-right: 35px;");
							const cp_email = document.createElement("input");
							cp_email.setAttribute("type", "email");
							cp_email.setAttribute("required", "");
							cp_email.setAttribute("maxlength", "40");
							cp_email.setAttribute("name", "email");
							cp_email.setAttribute("id", "email");
							cp_email.setAttribute("class", "cmb2-text-medium");
							cp_email.setAttribute("pattern", "[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$");
							cpemaildiv.appendChild(cpemail);
							cpemaildiv.appendChild(cp_email);
							cp_form.appendChild(cpemaildiv);
							const cpcitydiv= document.createElement("div");
							cpcitydiv.setAttribute("class","left_detail");
							const cpcity = document.createElement("label");
							cpcity.innerHTML = "Πόλη-Περιοχή:";
							cpcity.setAttribute("class","company_activity");
							cpcity.setAttribute("style","padding-right: 41px;");
							const cp_city = document.createElement("input");
							cp_city.setAttribute("type", "text");
							cp_city.setAttribute("required", "");
							cp_city.setAttribute("maxlength", "40");
							cp_city.setAttribute("name", "city");
							cp_city.setAttribute("id", "city");
							cp_city.setAttribute("class", "cmb2-text-medium");
							cpcitydiv.appendChild(cpcity);
							cpcitydiv.appendChild(cp_city);
							cp_form.appendChild(cpcitydiv);
							const cptkdiv= document.createElement("div");
							cptkdiv.setAttribute("class","right_detail");
							const cptk = document.createElement("label");
							cptk.innerHTML = "Ταχυδρομικός Κώδικας:";
							cptk.setAttribute("class","company_activity");
							const cp_tk = document.createElement("input");
							cp_tk.setAttribute("type", "text");
							cp_tk.setAttribute("required", "");
							cp_tk.setAttribute("maxlength", "8");
							cp_tk.setAttribute("name", "cptk");
							cp_tk.setAttribute("id", "cptk");
							cp_tk.setAttribute("class", "cmb2-text-medium");
							cptkdiv.appendChild(cptk);
							cptkdiv.appendChild(cp_tk);
							cp_form.appendChild(cptkdiv);
							const cpdoydiv= document.createElement("div");
							cpdoydiv.setAttribute("class","left_detail");
							const cpdoy = document.createElement("label");
							cpdoy.innerHTML = "ΔΟΥ:";
							cpdoy.setAttribute("class","company_activity");
							cpdoy.setAttribute("style","padding-right: 114px;");
							const cp_doy = document.createElement("input");
							cp_doy.setAttribute("type", "text");
							cp_doy.setAttribute("required", "");
							cp_doy.setAttribute("maxlength", "40");
							cp_doy.setAttribute("name", "doy");
							cp_doy.setAttribute("id", "doy");
							cp_doy.setAttribute("class", "cmb2-text-medium");
							cpdoydiv.appendChild(cpdoy);
							cpdoydiv.appendChild(cp_doy);
							cp_form.appendChild(cpdoydiv);
							const step_3_message_1 = document.createElement("div")
							step_3_message_1.innerHTML="<br>ΠΡΟΣΟΧΗ! Τα παραπάνω στοιχεία είναι τα στοιχεία του εκδότη και λάθος συμπλήρωση τους μπορεί να οδηγήσει σε φορολογικές παραβάσεις.<br>";
							step_3_message_1.setAttribute("class","mid_detail_step3");
							cp_form.appendChild(step_3_message_1);
							const step_3_buttons = document.createElement("div");
							step_3_buttons.setAttribute("class","mid_detail_step2");
							const step_3_continue = document.createElement("button");
							step_3_continue.innerHTML="Συνέχεια";
							step_3_continue.setAttribute("class","button-primary");
							step_3_continue.onclick= function step_3_continue(){
								//flag
								// const trimmedPhoneValue = cp_phone.value.trim();
								// const trimmedGemhValue = cp_gemh.value.trim();
								// const trimmedEmailValue = cp_email.value.trim();
								if (cp_phone) cp_phone.value = cp_phone.value.trim();
								if (cp_gemh) cp_gemh.value = cp_gemh.value.trim();
								if (cp_email) cp_email.value = cp_email.value.trim();

								var company_name = $('#cp_name').val();
								var small_name = $('#s_name').val();
								var activity = $('#activity').val();
								var c_phone = $('#phone').val();
								var c_vat = $('#vat_number').val();
								var gemh = $('#gemh').val();
								var cp_adress = $('#adress').val();
								var cp_city = $('#city').val();
								var cp_email = $('#email').val();
								var cp_tk = $('#cptk').val();
								var cp_doy =$('#doy').val();
								var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
								if(company_name && cp_doy && cp_adress && activity && c_phone && c_vat && cp_city && cp_email && cp_tk && cp_email.match(emailRegex)){
								cp_info.style.display="none";
								cpnamediv.style.display="none";
								cpsnamediv.style.display="none";
								cpactivitydiv.style.display="none";
								cpgemhdiv.style.display="none";
								cpvatdiv.style.display="none";
								cpphonediv.style.display="none";
								cpadressdiv.style.display="none";
								cpcitydiv.style.display="none";
								cpemaildiv.style.display="none";
								cptkdiv.style.display="none";
								cpdoydiv.style.display="none";
								step_3_buttons.style.display="none";
								step_3_message_1.style.display="none";
								message_step3.style.display="block";
								rnamediv.style.display="block";
								function removeDisplayProperty(element) {
									$(element).css('display', '');
									// Remove "display: none" from labels with class "company_activity"
									$(".company_activity").css('display', '');
								}
								removeDisplayProperty("#r_name");
								removeDisplayProperty("#rname");
								removeDisplayProperty("#rsnamediv");
								removeDisplayProperty("#rsname");
								removeDisplayProperty("#rs_name");
								removeDisplayProperty("#r_vat");
								removeDisplayProperty("#rvat");
								removeDisplayProperty("#step_3_message1");
								removeDisplayProperty("#buttonsdiv");
								step_3_message1.style.display="block";
								buttonsdiv.style.display="block";}
							}
							const step_3_cancel= document.createElement("button");
							step_3_cancel.innerHTML="Πίσω";
							step_3_cancel.setAttribute("class","button-danger");
							step_3_cancel.setAttribute("id","backBtn1");

							step_3_cancel.onclick= function step_3_cancel(){
								//Πίσω 1
								$('#myform').on('submit', function(e) {
									e.preventDefault();
								});
								headingElement.style.display = "block";
								afmText.style.display = "block";
								afmInputDiv.style.display = "block"; // Set display to block when "ΑΠΟΔΟΧΗ" is clicked
								continueBtn.style.display = "block";
								cancelBtn.style.display = "block";
								errorMessage.style.display = "block"

								//btnclo.setAttribute("hidden",true);
								content.setAttribute("hidden", true);
								cp_title.style.display = "none";
								cp_info.style.display = "none";
								cp_form.style.display = "none";

								const primerPopup = document.querySelector('.primer_popup > div');
								primerPopup.style.maxWidth = '375px';
							}
							const divider1=document.createElement("div");
							divider1.setAttribute("class","divider");
							step_3_buttons.appendChild(step_3_continue);
							step_3_buttons.appendChild(divider1);
							step_3_buttons.appendChild(step_3_cancel);
							cp_form.appendChild(step_3_buttons);
							const message_step3= document.createElement("span");
							message_step3.innerHTML="Εισάγετε το όνομα και το επίθετο του νόμιμου εκπροσώπου της επιχείρησης που δηλώσατε στην προηγούμενη οθόνη (επιχείρηση εκδότης) όπως αναγράφονται στην ταυτότητα του, όπως επίσης το ΑΦΜ του νόμιμου εκπροσώπου. Αν πρόκειται για ατομική επιχείρηση τότε συμπληρώνετε τα στοιχεία του ιδιοκτήτη.<br><br> ";
							message_step3.style.display="none";
							cp_form.appendChild(message_step3);
							const rnamediv =document.createElement("div");
							rnamediv.setAttribute("class","left_detail");
							rnamediv.style.display="none";
							const r_name = document.createElement("label");
							r_name.innerHTML = "Όνομα Εκπροσώπου:";
							r_name.setAttribute("class","company_activity");
							r_name.setAttribute("style","padding-right: 42px;");
							const rname = document.createElement("input");
							rname.setAttribute("type", "text");
							rname.setAttribute("maxlength", "25");
							rname.setAttribute("name", "rname");
							rname.setAttribute("id", "rname");
							rname.setAttribute("required", "true");
							rname.setAttribute("class", "cmb2-text-medium");
							rnamediv.appendChild(r_name);
							rnamediv.appendChild(rname);
							cp_form.appendChild(rnamediv);
							const rsnamediv =document.createElement("div");
							rsnamediv.setAttribute("class","left_detail");
							rsnamediv.style.display="none";
							const rs_name = document.createElement("label");
							rs_name.innerHTML = "<br>Επώνυμο Εκπροσώπου:";
							rs_name.setAttribute("class","company_activity");
							rs_name.setAttribute("style","padding-right: 23px;");
							const rsname = document.createElement("input");
							rsname.setAttribute("type", "text");
							rsname.setAttribute("maxlength", "25");
							rsname.setAttribute("required", "true");
							rsname.setAttribute("name", "rsname");
							rsname.setAttribute("id", "rsname");
							rsname.setAttribute("class", "cmb2-text-medium");
							rnamediv.appendChild(rs_name);
							rnamediv.appendChild(rsname);
							cp_form.appendChild(rsnamediv);
							const rvatdiv =document.createElement("div");
							rvatdiv.setAttribute("class","left_detail");
							rvatdiv.style.display="none";
							const r_vat = document.createElement("label");
							r_vat.innerHTML = "<br>ΑΦΜ Εκπροσώπου:";
							r_vat.setAttribute("class","company_activity");
							r_vat.setAttribute("style","padding-right: 54px;");
							const rvat = document.createElement("input");
							rvat.setAttribute("type", "text");
							rvat.setAttribute("required", "true");
							rvat.setAttribute("pattern", "^([0-9+]*$)");
							rvat.setAttribute("oninput", "this.value = this.value.replace(/\\D/g, '');");
							rvat.setAttribute('maxlength', "9");
							rvat.setAttribute("name", "rvat");
							rvat.setAttribute("id", "rvat");
							rvat.setAttribute("class", "cmb2-text-medium");
							rnamediv.appendChild(r_vat);
							rnamediv.appendChild(rvat);
							cp_form.appendChild(rvatdiv);
							const step_3_message1 = document.createElement("span");
							step_3_message1.innerHTML="Τα ως άνω στοιχεία είναι απαραίτητα για την σύμβαση MyData μεταξύ της επιχείρησης εκδότη και του παρόχου Primer Software.<br><br>";
							step_3_message1.style.display="none";
							const step_2 = document.createElement("div");
							step_2.style.display = "none";
							const cp_details=document.createElement("h3");
							cp_details.innerHTML = "\n" +
								"Έχετε δηλώσει τα παράκατω στοιχεία:";
							const detail_cp_name=document.createElement("span");
							detail_cp_name.setAttribute("class","detail_cp");
							detail_cp_name.innerHTML="Επωνυμία Εταιρείας:";
							const detail_cp_sname=document.createElement("span");
							detail_cp_sname.setAttribute("class","detail_cp");
							detail_cp_sname.innerHTML="<br>Διακριτικός Τίτλος:";
							const detail_cpvat=document.createElement("span");
							detail_cpvat.setAttribute("class","detail_cp");
							detail_cpvat.innerHTML="<br>ΑΦΜ:";
							const detail_cpadress=document.createElement("span");
							detail_cpadress.setAttribute("class","detail_cp");
							detail_cpadress.innerHTML="<br>Διεύθυνση:";
							const detail_cpactivity=document.createElement("span");
							detail_cpactivity.setAttribute("class","detail_cp");
							detail_cpactivity.innerHTML="<br>Δραστηριότητα:";
							const detail_cpphone=document.createElement("span");
							detail_cpphone.setAttribute("class","detail_cp");
							detail_cpphone.innerHTML="<br>Τηλέφωνο:";
							const detail_cpgemh=document.createElement("span");
							detail_cpgemh.setAttribute("class","detail_cp");
							detail_cpgemh.innerHTML="<br>ΓΕΜΗ:";
							const detail_email=document.createElement("span");
							detail_email.setAttribute("class","detail_cp");
							detail_email.innerHTML="<br>Email Επιχείρησης:";
							const detail_city=document.createElement("span");
							detail_city.setAttribute("class","detail_cp");
							detail_city.innerHTML="<br>Πόλη-Περιοχή:";
							const detail_tk=document.createElement("span");
							detail_tk.setAttribute("class","detail_cp");
							detail_tk.innerHTML="<br>Ταχυδρομικός Κώδικας:";
							const detail_doy=document.createElement("span");
							detail_doy.setAttribute("class","detail_cp");
							detail_doy.innerHTML="<br>ΔΟΥ:";
							const detail_r_name=document.createElement("span");
							detail_r_name.setAttribute("class","detail_cp");
							detail_r_name.innerHTML="<br>Όνομα Εκπρόσωπου:";
							const detail_r_sname=document.createElement("span");
							detail_r_sname.setAttribute("class","detail_cp");
							detail_r_sname.innerHTML="<br>Επίθετο Εκπροσώπου:";
							const detail_r_vat=document.createElement("span");
							detail_r_vat.setAttribute("class","detail_cp");
							detail_r_vat.innerHTML="<br>ΑΦΜ Εκπροσώπου:";
							const detail_cp_name_value= document.createElement("span");
							detail_cp_name_value.innerHTML = "";
							const detail_cp_small_name_value= document.createElement("span");
							detail_cp_small_name_value.innerHTML = "";
							const detail_cp_vat= document.createElement("span");
							detail_cp_vat.innerHTML = "";
							const detail_cp_adress= document.createElement("span");
							detail_cp_adress.innerHTML = "";
							const detail_cp_activity= document.createElement("span");
							detail_cp_activity.innerHTML = "";
							const detail_cp_phone= document.createElement("span");
							detail_cp_phone.innerHTML = "";
							const detail_cp_gemh= document.createElement("span");
							detail_cp_gemh.innerHTML = "";
							const detail_cp_email= document.createElement("span");
							detail_cp_email.innerHTML = "";
							const detail_cp_doy= document.createElement("span");
							detail_cp_doy.innerHTML = "";
							const detail_cp_city= document.createElement("span");
							detail_cp_city.innerHTML = "";
							const detail_cp_tk= document.createElement("span");
							detail_cp_tk.innerHTML = "";
							const detail_rname= document.createElement("span");
							detail_rname.innerHTML = "";
							const detail_rsname= document.createElement("span");
							detail_rsname.innerHTML = "";
							const detail_rvat= document.createElement("span");
							detail_rvat.innerHTML = "";
							const detail_message= document.createElement("span");
							detail_message.innerHTML = "<br><br>Είστε σίγουρος/η οτι θέλετε να συνεχίσετε την ενεργοποίηση; Η ενεργοποίηση θα πραγματοποιηθεί για την τωρινή ip διεύθυνση της ιστοσελίδας.<br>";
							const cp_activation= document.createElement("button");
							cp_activation.innerHTML = "Συνέχεια";
							cp_activation.setAttribute("class","button-primary");
							cp_activation.setAttribute("id","button_activation");
							const divider4=document.createElement("div");
							divider4.setAttribute("class","divider");
							const close_activation = document.createElement("button");
							close_activation.setAttribute("class", "button-danger");
							close_activation.setAttribute("id", "backBtn3");
							close_activation.innerHTML = "Πίσω";
							close_activation.onclick= function closemodal_activation(){
								$('#myform').on('submit', function(e) {
									e.preventDefault();
								});
								btnclo.style.display='block';
								cp_form.style.display='block';
								cp_info.style.display='block';
								cp_title.style.display='block';

								step_2.style.display='none';
								$(backBtn2).css('display', '');
								cp_info.style.display='none';
								//newDiv.style.display="none";
								//document.location.reload();
							}
							step_2.appendChild(cp_details);
							step_2.appendChild(detail_cp_name);
							step_2.appendChild(detail_cp_name_value);
							step_2.appendChild(detail_cp_sname);
							step_2.appendChild(detail_cp_small_name_value);
							step_2.appendChild(detail_cpvat);
							step_2.appendChild(detail_cp_vat);
							step_2.appendChild(detail_cpadress);
							step_2.appendChild(detail_cp_adress);
							step_2.appendChild(detail_cpactivity);
							step_2.appendChild(detail_cp_activity);
							step_2.appendChild(detail_cpphone);
							step_2.appendChild(detail_cp_phone);
							step_2.appendChild(detail_cpgemh);
							step_2.appendChild(detail_cp_gemh);
							step_2.appendChild(detail_email);
							step_2.appendChild(detail_cp_email);
							step_2.appendChild(detail_city);
							step_2.appendChild(detail_cp_city);
							step_2.appendChild(detail_tk);
							step_2.appendChild(detail_cp_tk);
							step_2.appendChild(detail_doy);
							step_2.appendChild(detail_cp_doy);
							step_2.appendChild(detail_r_name);
							step_2.appendChild(detail_rname);
							step_2.appendChild(detail_r_sname);
							step_2.appendChild(detail_rsname);
							step_2.appendChild(detail_r_vat);
							step_2.appendChild(detail_rvat);
							step_2.appendChild(detail_message);
							step_2.appendChild(cp_activation);
							step_2.appendChild(divider4);
							step_2.appendChild(close_activation);
							const buttonsdiv = document.createElement("div");
							buttonsdiv.setAttribute("class","mid_detail");
							buttonsdiv.style.display="none";
							const cp_continue = document.createElement("button");
							//cp_continue.setAttribute("type", "submit");
							cp_continue.setAttribute("value", "Καταχώρηση")
							cp_continue.onclick = function doit(){
								if (rname) rname.value = rname.value.trim();
								if (rsname) rsname.value = rsname.value.trim();
								if (rvat) rvat.value = rvat.value.trim();
								$('#myform').on('submit', function(e) {
									e.preventDefault();
									$.ajax({
										url : $(this).attr('action') || window.location.pathname,
										type: "POST",
										data: $(this).serialize(),
										beforeSend: function(){
											$('form#primer_licenses').css({'opacity': '0.5'});

										},
										success: function (data) {
											detail_cp_name_value.innerHTML = $('#cp_name').val();
											detail_cp_small_name_value.innerHTML = $('#s_name').val();
											detail_cp_activity.innerHTML = $('#activity').val();
											detail_cp_phone.innerHTML = $('#phone').val();
											detail_cp_vat.innerHTML = $('#vat_number').val();
											detail_cp_gemh.innerHTML = $('#gemh').val();
											detail_cp_adress.innerHTML=$('#adress').val();
											detail_rname.innerHTML=$('#rname').val();
											detail_rsname.innerHTML=$('#rsname').val();
											detail_rvat.innerHTML=$('#rvat').val();
											detail_cp_email.innerHTML=$('#email').val();
											detail_cp_city.innerHTML=$('#city').val();
											detail_cp_tk.innerHTML=$('#cptk').val();
											detail_cp_doy.innerHTML=$('#doy').val();
											btnclo.style.display='none';
											cp_form.style.display='none';
											cp_info.style.display='none';
											cp_title.style.display='none';
											step_2.style.display = 'block';
											cp_activation.onclick = function company_activation(){
												var company_name = $('#cp_name').val();
												var small_name = $('#s_name').val();
												if(small_name === ""){
													small_name = "empty";
												}
												var activity = $('#activity').val();
												var c_phone = $('#phone').val();
												var c_vat = $('#vat_number').val();
												var gemh = $('#gemh').val();
												if(gemh === ""){
													gemh = "empty";
												}
												var cp_adress = $('#adress').val();
												var cp_city = $('#city').val();
												var cp_email = $('#email').val();
												var cp_tk = $('#cptk').val();
												var rname=$('#rname').val();
												var rsname=$('#rsname').val();
												var rvat=$('#rvat').val();
												var doy = $('#doy').val();
												var username = $('#primer_licenses #license_user').val();
												var password = $('#primer_licenses #license_password').val();


												var data = {
													'username': username,
													'password': password,
													'company_name': company_name,
													'small_name': small_name,
													'activity': activity,
													'c_phone': c_phone,
													'c_vat': c_vat,
													'gemh': gemh,
													'cp_adress': cp_adress,
													'r_name': rname,
													'r_sname': rsname,
													'r_vat': rvat,
													'cp_city':cp_city,
													'cp_email':cp_email,
													'cp_tk':cp_tk,
													'doy':doy,
													'action' : 'company_activation_call'
												}

												$.ajax({
													url: ajaxurl,
													method: "POST",
													dataType: "JSON",
													data: data,
													beforeSend: function(){
														$('form#primer_licenses').css({'opacity': '0.5'});
														$('#primer_licenses #get_license_type').addClass('disabled');
														$('#button_activation').addClass('disabled');
														$('#button_activation').text('Σε Εξέλιξη');
													},
													success: function (response) {
														console.log(response);
														if( (response.message==="Company added to user") ||  (response.message==="Company's data added, please proceed with product activation")){
															var license_key = $('#primer_licenses #license_key').val();
															var username = $('#primer_licenses #license_user').val();
															var password = $('#primer_licenses #license_password').val();
															var check_reset = true;
															var data = {
																'licenseKey': license_key,
																'username': username,
																'password': password,
																'check_reset': check_reset,
																'action': 'primer_insert_license',
															}

															$.ajax({
																url: ajaxurl,
																method: "POST",
																dataType: "JSON",
																data: data,
																beforeSend: function(){
																	$('form#primer_licenses').css({'opacity': '0.5'});
																	$('#primer_licenses #get_license_type').addClass('disabled');
																	$('#button_activation').addClass('disabled');
																	$('#button_activation').text('Σε Εξέλιξη');
																},
																success: function (response) {
																	console.log(response);
																	if (response.check_message === true) {
																		step_2.style.display = 'none';
																		step_4.style.display ="block";

																	} else {
																		alert(response.message);
																		document.location.reload();
																	}
																	setTimeout(function () {
																		$('form#primer_licenses').css({'opacity': '1'});
																	}, 1500);
																}
															})
														} else {
															alert(response.message);
															document.location.reload();
														}
														setTimeout(function () {
															$('form#primer_licenses').css({'opacity': '1'});
														}, 1500);
													}
												})
											}

										},
										error: function (jXHR, textStatus, errorThrown) {
											alert(errorThrown);
										}
									});
								});
							}
							cp_continue.innerHTML="Καταχώρηση";
							cp_continue.setAttribute("name", "cp_name1");
							cp_continue.setAttribute("class", "button-primary");
							const break_line= document.createElement("br");
							const btnclo = document.createElement("button");
							btnclo.setAttribute("class", "button-danger");
							btnclo.setAttribute("id", "backBtn2");
							//btnclo.setAttribute("hidden", "true");
							btnclo.innerHTML = "Πίσω";

							btnclo.onclick= function goBack(){
								$('#myform').on('submit', function(e) {
									e.preventDefault();
								});

								$("#rname, #rsname, #rvat").val("");
								//cp_title.style.display = "block";
								message_step3.style.display = "none"
								cp_info.style.display = "block";
								//cp_form.style.display = "none";


								cpnamediv.style.display="block";
								cpsnamediv.style.display="block";
								cpactivitydiv.style.display="block";
								cpgemhdiv.style.display="block";
								cpvatdiv.style.display="block";
								cpphonediv.style.display="block";
								cpadressdiv.style.display="block";
								cpcitydiv.style.display="block";
								cpemaildiv.style.display="block";
								cptkdiv.style.display="block";
								cpdoydiv.style.display="block";
								step_3_buttons.style.display="block";
								step_3_message_1.style.display="block";

								// rnamediv.style.display="none";
								r_name.style.display="none";
								rname.style.display="none";

								// rsnamediv.style.display="none";
								rs_name.style.display="none";
								rsname.style.display="none";

								// rvatdiv.style.display="none";
								r_vat.style.display="none";
								rvat.style.display="none";
								//
								//

								//
								// step_3_message1.style.display="none";

								buttonsdiv.style.display="none";
								// newDiv.style.display="none";
								// document.location.reload();
							}

							//Argotera tha balw koumpi gia to Afm (sunexeia) kai apla tha allaksw to btnact.onclick se newKoumpi.Onclick.
							btnact.onclick = function step_1() {
								headingElement.style.display = "block";
								afmText.style.display = "block";
								afmInputDiv.style.display = "block"; // Set display to block when "ΑΠΟΔΟΧΗ" is clicked
								continueBtn.style.display = "block";
								cancelBtn.style.display = "block";
								//btnclo.setAttribute("hidden",true);
								content.setAttribute("hidden", true);
								tit.style.display = "none";
								btnact.style.display = "none";
								btncl.style.display = "none";

								const primerPopup = document.querySelector('.primer_popup > div');
								primerPopup.style.maxWidth = '375px';
								removeElement(".divider");
								// removeElement("br");
								const titleTermsDiv = document.querySelector('.title_terms');
								const brElement = titleTermsDiv.nextElementSibling;

								if (brElement && brElement.tagName === 'BR') {
									brElement.parentNode.removeChild(brElement);
								}
							}
							function removeElement(selector) {
								const element = document.querySelector(selector);
								if (element) {
									element.parentNode.removeChild(element);
								}
							}
							const divider=document.createElement("div");
							divider.setAttribute("class","divider");
							buttonsdiv.appendChild(step_3_message1);
							buttonsdiv.appendChild(cp_continue);
							buttonsdiv.appendChild(divider);
							buttonsdiv.appendChild(btnclo);
							cp_form.appendChild(buttonsdiv);
							const divider3=document.createElement("div");
							divider3.setAttribute("class","divider");
							newDiv.appendChild(tit);
							newDiv.appendChild(BR);
							newDiv.appendChild(content);
							newDiv.appendChild(BR);
							newDiv.appendChild(btnact);
							newDiv.appendChild(divider3);
							newDiv.appendChild(btncl);
							newDiv.appendChild(headingElement);
							newDiv.appendChild(afmText);
							newDiv.appendChild(afmInputDiv);
							newDiv.appendChild(continueBtn);
							newDiv.appendChild(cancelBtn);
							newDiv.appendChild(errorMessage);
							newDiv.appendChild(cp_title);
							newDiv.appendChild(cp_info);
							newDiv.appendChild(cp_form);
							newDiv.appendChild(step_2);
							const step_4 = document.createElement("div");
							step_4.style.display = "none";
							const last_message = document.createElement("span");
							last_message.innerHTML = "Η ενεργοποίηση πραγματοποιήθηκε με επιτυχία!\n" +
								"Αν δεν έχετε πραγματοποιήσει κατά το παρελθόν την πρώτη ενεργοποίηση θα πρέπει να προχωρήσετε στην πιστοποίηση των στοιχείων σας. Η διαδικασία πραγματοποιείται μία φορά και είναι υποχρεωτική για να ολοκληρωθεί η διασύνδεση MyData.\n" +
								"\n" +
								"Μπορείτε να βρείτε οδηγίες για την ολοκλήρωση της πιστοποίησης στον <a href='https://primer.gr/wp-content/uploads/2021/11/manual_egkatastasi_plugin-1-2.pdf'>σύνδεσμο</a> </a><br><br>"
							const last_button=document.createElement("button");
							last_button.setAttribute("class","button-secondary");
							last_button.innerHTML = "OK";
							last_button.onclick= function closem(){
								newDiv.style.display="none";
								document.location.reload();
							}
							step_4.appendChild(last_message);
							step_4.appendChild(last_button);
							newDiv.appendChild(step_4);
							const wpwrap = document.getElementById("wpwrap");
							document.body.insertBefore(newDiv, wpwrap);
							//alert(response.message);
							//document.location.reload();
						}
						popupOpenClose('.primer_popup');
					}
					setTimeout(function () {
						$('form#primer_licenses').css({'opacity': '1'});
					}, 1500);
				}
			})
		});
		//end of company activation modal


		$('#primer_licenses #get_license_remaining , #primer_mydata #get_license_remaining').on('click', function (e) {
			e.preventDefault();

			var data = {
				'_ajax_nonce': primer_ajax_obj.nonce,
				'action': 'primer_license_remaining',
			}

			$.ajax({
				url: primer_ajax_obj.ajax_url,
				method: "POST",
				data: data,
				dataType: "JSON",
				beforeSend: function(){
					$('#primer_licenses #get_license_remaining, #primer_mydata #get_license_remaining').addClass('disabled');
				},
				success: function (response) {
					console.log(response);
					if (response.success) {
						$('#primer_licenses #remaining_invoices, #primer_mydata #remaining_mydata_invoices').val(response.remaining);
						document.location.reload();
					}
				},
				error: function (jqXHR, exception) {
					console.log('status ', jqXHR.status);
					console.log('response ', jqXHR.responseText);
				}
			})

		});

		$('.email-wildcard').on('click', function(e) {
			e.preventDefault();
			var wildcard = $(this).data('wildcard');
			var editor_id = 'quote_available_content_ifr';
			var editor = $('#' + editor_id);
			if (editor.length) {
				editor = editor[0].contentWindow;
				editor.focus();
				var currentRange = editor.getSelection().getRangeAt(0);
				var html = document.createElement('div');
				html.innerHTML = wildcard;
				currentRange.insertNode(html);
			} else {
				var textarea = document.getElementById('quote_available_content');
				var startPos = textarea.selectionStart;
				var endPos = textarea.selectionEnd;
				textarea.value = textarea.value.substring(0, startPos) + wildcard + textarea.value.substring(endPos, textarea.value.length);
			}
		});
		//function handleEmailWildcardClick() {
		//	$(document).on('click', '.email-wildcard', function(e) {
		//		e.preventDefault();
		//		var wildcard = $(this).data('wildcard');
		//		var editor_id = 'quote_available_content';
		//		if (typeof tinyMCE !== 'undefined' && tinyMCE.get(editor_id)) {
		//			tinyMCE.get(editor_id).execCommand('mceInsertContent', false, wildcard);
		//		} else {
		//			var textarea = document.getElementById(editor_id);
		//			var startPos = textarea.selectionStart;
		//			var endPos = textarea.selectionEnd;
		//			textarea.value = textarea.value.substring(0, startPos) + wildcard + textarea.value.substring(endPos, textarea.value.length);
		//		}
		//	});
		//}
		//handleEmailWildcardClick();


		function hideSaveButton() {
			var specific_class = $('.disable_functionality');
			if (specific_class.length) {
				var parent_form = specific_class.parents('form.cmb-form');
				if (parent_form.length) {
					var btn = parent_form.find('input[name="submit-cmb"]');
					if (btn.length) {
						btn.hide();
					}
				}
			}
		}
		hideSaveButton();

		var alpha = [];
		function check_select_export_value() {
			$('#cmb2-metabox-primer_export select[name^="export_select"]').on('change', function () {
				var counter = 0;
				let val = $(this).val();
				let currentIndex = alpha.indexOf(val);
				console.log($(this)[0].id);
				if($(this)[0].id === 'export_select_total_vat_rate_amount') {
					$.ajax({
						url: primer_ajax_obj.ajax_url,
						data: '&action=primer_get_woocommerce_tax_rates&_ajax_nonce=' + primer_ajax_obj.nonce,
						method: "POST",
						success: function (response) {
							var getResponse = JSON.parse(response);
							counter = getResponse.counter - 1;
							console.log(counter);
							console.log(String.fromCharCode(val.charCodeAt(0) + counter));
							for (let i = 0; i < counter; i++) {
								alpha.push(String.fromCharCode(val.charCodeAt(0) + i));
							}

						},
						error: function (error) {
							console.log(error);
						}
					})
				}

				if((currentIndex === -1)) {
					if (val !== '') {
						alpha.push(val);
					}
				} else {
					alert('This letter was previously selected!');
					$(this).val('');
				}
			})
		}
		check_select_export_value();

		});

})( jQuery );
