<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }

/**
 * Displays watermark message in the receipt, if is cancelled.
 *
 * @return void
 */
function primer_display_watermark() {
	$watermark_wrapper = '';
	$receipt_id = get_the_ID();
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$order = wc_get_order( $order_id );
	$order_customer_country = $order->get_billing_country();
	$customer_country = $order_customer_country;

	$check_api_type = get_post_meta($receipt_id, 'send_to_api_type', true);

	if (empty($check_api_type)) {
		$watermark_wrapper .= '<div class="watermark_message">'.__('ΑΚΥΡΟ', 'primer').'</div>';
	}

	if ($check_api_type !== 'production') {
		if ($customer_country == 'GR') {
			$watermark_wrapper .= '<div class="watermark_message">'.__('ΑΚΥΡΟ', 'primer').'</div>';
		} else {
			$watermark_wrapper .= '<div class="watermark_message">'.__('INVALID', 'primer').'</div>';
		}
	}

	$allowed_html = array(
		'div' => array(
			'class' => array()
		)
	);
	echo wp_kses($watermark_wrapper, $allowed_html);

//	echo $watermark_wrapper;
}

/**
 *
 *
 * @return void
 */
function primer_display_issuer_container() {
	$issuer_container = '';
	$receipt_id = get_the_ID();
	$issuer_name = get_post_meta($receipt_id, 'receipt_client', true);
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$order = wc_get_order( $order_id );
	$billing_first_name = $order->get_billing_first_name();
	$billing_last_name = $order->get_billing_last_name();
    $order_customer_country = $order->get_billing_country();
    $is_central = true;
    $branchID = get_post_meta($receipt_id, 'branchID', true);
    if ( $branchID == null ) {
        $branchID = "0";
    }
	$primer_license_data = get_option('primer_licenses');
    if( isset($branchID) && $branchID != "0") {
        $subsidiaries = $primer_license_data['subsidiaries'];
        $currentBranchId = $branchID;
        $foundSubsidiary = null;
        $is_central = false;
        foreach ($subsidiaries as $subsidiary) {
            if ($subsidiary['branchId'] == $currentBranchId) {
                $foundSubsidiary = $subsidiary;
                break;
            }
        }

        // Check if a matching subsidiary is found
        if ($foundSubsidiary) {
            // Now, $foundSubsidiary contains the data for the selected subsidiary based on currentBranchID
            $subsidiaryCity = $foundSubsidiary['city'];
            $subsidiaryStreet = $foundSubsidiary['street'];
            $subsidiaryTk = $foundSubsidiary['tk'];
            $subsidiaryDoy = $foundSubsidiary['doy'];
            $subsidiaryNumber = $foundSubsidiary['number'];
            $subsidiaryPhone = $foundSubsidiary['phoneNumber'];

            // Now you can use these variables as needed in your code
        } else {
            // Handle the case where no matching subsidiary is found
            // You can set default values or handle it as needed
            $subsidiaryCity = '';
            $subsidiaryStreet = '';
            $subsidiaryTk = '';
            $subsidiaryDoy = '';
            $subsidiaryNumber = '';
            $subsidiaryPhone = '';
        }
    }

	if(!empty($primer_license_data)) {
        if ( $is_central ) {
            if (!empty($primer_license_data['companyName'])) {
                if ($order_customer_country != 'GR' && !empty($primer_license_data['translated_company_name'])) {
                    $issuer_container .= '<span class="issuer_name skin">' . $primer_license_data['translated_company_name'] . '</span>';
                } else {
                    $issuer_container .= '<span class="issuer_name skin">' . $primer_license_data['companyName'] . '</span>';
                }
            } else {
                $issuer_container .= '<span class="issuer_name skin">' . __('ISSUER\'S COMPANY NAME', 'primer') . '</span>';
            }
            if (!empty($primer_license_data['companyActivity'])) {
                if ($order_customer_country != 'GR' && !empty($primer_license_data['translated_company_activity'])) {
                    $issuer_container .= '<p> <span class="issuer_subjectField skin company_activity">' . $primer_license_data['translated_company_activity'] . '</span></p>';
                } else {
                    $issuer_container .= '<p> <span class="issuer_subjectField skin company_activity">' . $primer_license_data['companyActivity'] . '</span></p>';
                }
            } else {
                $issuer_container .= '<p> <span class="issuer_subjectField skin">' . __('COMPANY ACTIVITY', 'primer') . '</span></p>';
            }

            if (!empty($primer_license_data['companyAddress'] && $primer_license_data['companyCity'] && $primer_license_data['companyTk'] && $primer_license_data['companyPhoneNumber'] && $primer_license_data['companyDoy'])) {
                if ($order_customer_country != 'GR' && !empty($primer_license_data['translated_company_address']) && !empty($primer_license_data['translated_company_city']) && !empty($primer_license_data['companyTk']) && !empty($primer_license_data['companyPhoneNumber']) && !empty($primer_license_data['companyDoy'])) {
                    $issuer_container .= $issuer_container .= '<p><span class="issuer_subjectFiled skin">' . $primer_license_data['translated_company_address'] . ', ' . $primer_license_data['translated_company_city'] . ', ' . $primer_license_data['companyTk'] . ',  <span>PHONE. </span>' . $primer_license_data['companyPhoneNumber'] . ', <span>DΟΥ: </span>' . $primer_license_data['translated_company_doy'] . '</span></p>';

                } else {
                    $issuer_container .= '<p><span class="issuer_subjectField skin">' . $primer_license_data['companyAddress'] . ', ' . $primer_license_data['companyCity'] . ', ' . $primer_license_data['companyTk'] . ',  <span>ΤΗΛ. </span>' . $primer_license_data['companyPhoneNumber'] . ', <span>ΔΟΥ: </span>' . $primer_license_data['companyDoy'] . '</span></p>';
                }
            } else {
                $issuer_container .= '<p><span class="issuer_subjectField skin">' . __('ISSUER\'S COMPANY ADDRESS', 'primer') . ', ' . __('ISSUER\'S COMPANY CITY', 'primer') . ', ' . __('ISSUER\'S COMPANY TK', 'primer') . ', ' . __('ISSUER\'S COMPANY PHONE', 'primer') . ', ' . __('ISSUER\'S COMPANY DOY', 'primer') . '</span></p>';
            }

            if (!empty($primer_license_data['companyVatNumber'] && $primer_license_data['gemh'] && $primer_license_data['gemh'] != 'empty')) {
                if ($order_customer_country == 'GR') {
                    $issuer_container .= '<p><span class="issuer_address skin">' . __('ΑΦΜ: ', 'primer') . 'EL' . $primer_license_data['companyVatNumber'] . ', ' . __('ΓΕΜΗ: ', 'primer') . '' . $primer_license_data['gemh'] . '</span></p>';
                } else {
                    $issuer_container .= '<p><span class="issuer_address skin">' . __('VAT: ', 'primer') . 'EL' . $primer_license_data['companyVatNumber'] . ', ' . __('GEMH: ', 'primer') . '</span></p>';
                }
            } else {
                $issuer_container .= '<p><span class="issuer_address skin">ΑΦΜ: </span></p>';
            }

            /**
            if (!empty($primer_license_data['gemh']) && $primer_license_data['gemh'] != 'empty') {
                if ($order_customer_country == 'GR') {
                    $issuer_container .= '<p><span class="issuer_address skin">' . __('ΓΕΜΗ: ', 'primer') . '' . $primer_license_data['gemh'] . '</span></p>';
                } else {
                    $issuer_container .= '<p><span class="issuer_address skin">' . __('GEMH: ', 'primer') . '' . $primer_license_data['gemh'] . '</span></p>';
                }
            }
            */
            /**
            if (!empty($primer_license_data['companyCity'])) {
                if ($order_customer_country != 'GR' && !empty($primer_license_data['translated_company_city'])) {
                    $issuer_container .= '<p><span class="issuer_subjectField skin">' . $primer_license_data['translated_company_address'] . ', ' . $primer_license_data['translated_company_city'] . ',</span>';
                } else {
                    $issuer_container .= '<p><span class="issuer_subjectField skin">' . $primer_license_data['companyAddress'] . ', ' . $primer_license_data['companyCity'] . ',</span>';
                }
            } else {
                $issuer_container .= '<span class="issuer_name skin">' . __('ISSUER\'S COMPANY CITY', 'primer') . '</span>';
            }
            if (!empty($primer_license_data['companyTk'])) {
                $issuer_container .= '<span class="issuer_subjectField skin">' . $primer_license_data['companyTk'] . '</span></p>';
            } else {
                $issuer_container .= '<span class="issuer_name skin">' . __('ISSUER\'S COMPANY TK', 'primer') . '</span>';
            }
            if (!empty($primer_license_data['companyAddress'])) {
                if ($order_customer_country != 'GR' && !empty($primer_license_data['translated_company_address'])) {
                    $issuer_container .= '<p><span class="issuer_address skin">' . $primer_license_data['translated_company_address'] . '</span></p>';
                } else {
                    $issuer_container .= '<p><span class="issuer_address skin">' . $primer_license_data['companyAddress'] .  '</span></p>';
                }
            } else {
                $issuer_container .= '<p><span class="issuer_address skin">ADDRESS</span></p>';
            }

            if (!empty($primer_license_data['companyPhoneNumber'])) {
                if ($order_customer_country == 'GR') {
                    $issuer_container .= '<p><span class="issuer_address skin">' . __('ΤΗΛΕΦΩΝΟ: ', 'primer') . '' . $primer_license_data['companyPhoneNumber'] . '</span></p>';
                } else {
                    $issuer_container .= '<p><span class="issuer_address skin">' . __('PHONE: ', 'primer') . '' . $primer_license_data['companyPhoneNumber'] . '</span></p>';
                }
            } else {
                $issuer_container .= '<p><span class="issuer_address skin">ΑΦΜ:</span></p>';
            }
            if (!empty($primer_license_data['companyDoy'])) {
                if ($order_customer_country == 'GR') {
                    $issuer_container .= '<p><span class="issuer_address skin">' . __('ΔΟΥ: ', 'primer') . '' . $primer_license_data['companyDoy'] . '</span></p>';
                } else {
                    if (!empty($primer_license_data['translated_company_doy'])) {
                        $issuer_container .= '<p><span class="issuer_address skin">' . __('DOY: ', 'primer') . '' . $primer_license_data['translated_company_doy'] . '</span></p>';
                    } else {
                        $issuer_container .= '<p><span class="issuer_address skin">' . __('DOY: ', 'primer') . '' . $primer_license_data['companyDoy'] . '</span></p>';
                    }
                }
            } else {
                $issuer_container .= '<p><span class="issuer_address skin">ΔΟΥ:</span></p>';
            }
            */


        } else {
            if (!empty($primer_license_data['companyName'])) {
                if ($order_customer_country != 'GR' && !empty($primer_license_data['translated_company_name'])) {
                    $issuer_container .= '<span class="issuer_name skin">' . $primer_license_data['translated_company_name'] . '</span>';
                } else {
                    $issuer_container .= '<span class="issuer_name skin">' . $primer_license_data['companyName'] . '</span>';
                }
            } else {
                $issuer_container .= '<span class="issuer_name skin">' . __('ISSUER\'S COMPANY NAME', 'primer') . '</span>';
            }
            if (!empty($primer_license_data['companyActivity'])) {
                if ($order_customer_country != 'GR' && !empty($primer_license_data['translated_company_activity'])) {
                    $issuer_container .= '<p> <span class="issuer_subjectField skin">' . $primer_license_data['translated_company_activity'] . '</span></p>';
                } else {
                    $issuer_container .= '<p> <span class="issuer_subjectField skin">' . $primer_license_data['companyActivity'] . '</span></p>';
                }
            } else {
                $issuer_container .= '<p> <span class="issuer_subjectField skin">' . __('COMPANY ACTIVITY', 'primer') . '</span></p>';
            }
            if (!empty($primer_license_data['companyVatNumber'])) {
                if ($order_customer_country == 'GR') {
                    $issuer_container .= '<p><span class="issuer_address skin">' . __('ΑΦΜ: ', 'primer') . '' . $primer_license_data['companyVatNumber'] . '</span></p>';
                } else {
                    $issuer_container .= '<p><span class="issuer_address skin">' . __('VAT: ', 'primer') . '' . $primer_license_data['companyVatNumber'] . '</span></p>';
                }
            } else {
                $issuer_container .= '<p><span class="issuer_address skin">ΑΦΜ: </span></p>';
            }
            if ( $branchID != null && $branchID != "0") {
                if ($order_customer_country == 'GR') {
                    $issuer_container .= '<p><span class="issuer_address skin">' . __('Υποκατάστημα: ', 'primer') . '' . $subsidiaryStreet. " " . $subsidiaryNumber . '</span></p>';
                } else {
                    $issuer_container .= '<p><span class="issuer_address skin">' . __('Branch: ', 'primer') . '' . $subsidiaryStreet . " " . $subsidiaryNumber .'</span></p>';
                }
                if (!empty($subsidiaryStreet)) {
                    $issuer_container .= '<p><span class="issuer_subjectField skin">' . $subsidiaryCity .  ', ' .$subsidiaryTk. '</span>';
                }
                if (!empty($subsidiaryDoy)) {
                    if ($order_customer_country == 'GR') {
                        $issuer_container .= '<p><span class="issuer_address skin">' . __('Δ.Ο.Υ: ', 'primer') . '' . $subsidiaryDoy . '</span></p>';
                    } else {
                        $issuer_container .= '<p><span class="issuer_address skin">' . __('DOY: ', 'primer') . '' . $subsidiaryDoy . '</span></p>';
                    }
                }
                if (!empty($subsidiaryPhone)) {
                    if ($order_customer_country == 'GR') {
                        $issuer_container .= '<p><span class="issuer_address skin">' . __('ΤΗΛΕΦΩΝΟ: ', 'primer') . '' . $subsidiaryPhone . '</span></p>';
                    } else {
                        $issuer_container .= '<p><span class="issuer_address skin">' . __('PHONE: ', 'primer') . '' . $subsidiaryPhone . '</span></p>';
                    }
                }
                if (!empty($primer_license_data['gemh']) && $primer_license_data['gemh'] != 'empty') {
                    if ($order_customer_country == 'GR') {
                        $issuer_container .= '<p><span class="issuer_address skin">' . __('ΓΕΜΗ: ', 'primer') . '' . $primer_license_data['gemh'] . '</span></p>';
                    } else {
                        $issuer_container .= '<p><span class="issuer_address skin">' . __('GEMH: ', 'primer') . '' . $primer_license_data['gemh'] . '</span></p>';
                    }
                }
            }

        }
	}

	$allowed_html = array(
		'p' => array(
			'class' => array()
		),
		'span' => array(
			'class' => array()
		)
	);
	echo wp_kses($issuer_container, $allowed_html);

//	echo $issuer_container;
}

function primer_main_info_table_head() {
	$issuer_main_info_table_head = '';
	$receipt_id = get_the_ID();
	$issuer_name = get_post_meta($receipt_id, 'receipt_client', true);
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$order = wc_get_order( $order_id );
	$order_customer_country = $order->get_billing_country();
	$customer_country = $order_customer_country;
	$issuer_main_info_table_head = '<tr class="heading">';

	if ($order_customer_country == 'GR') {
		$issuer_main_info_table_head .= '<td><p>ΕΙΔΟΣ ΠΑΡΑΣΤΑΤΙΚΟΥ</p></td>';
        $issuer_main_info_table_head .= '<td><p>ΣΕΙΡΑ</p></td>';
		$issuer_main_info_table_head .= '<td><p>ΑΡΙΘΜΟΣ</p></td>';
		$issuer_main_info_table_head .= '<td><p>ΗΜΕΡ/ΝΙΑ</p></td>';
		$issuer_main_info_table_head .= '<td><p>ΩΡΑ</p></td>';
	} else {
		$issuer_main_info_table_head .= '<td><p>INVOICE TYPE</p></td>';
        $issuer_main_info_table_head .= '<td><p>SERIES</p></td>';
		$issuer_main_info_table_head .= '<td><p>INVOICE NUMBER</p></td>';
		$issuer_main_info_table_head .= '<td><p>DATE</p></td>';
		$issuer_main_info_table_head .= '<td><p>TIME</p></td>';
	}

	$issuer_main_info_table_head .= '</tr>';

	$allowed_html = array(
		'tr' => array(
			'class' => array(),
		),
		'td' => array(),
		'p' => array(
			'class' => array()
		),
		'span' => array(
			'class' => array()
		)
	);
	echo wp_kses($issuer_main_info_table_head, $allowed_html);

//	echo $issuer_main_info_table_head;
}

function primer_display_issuer_product_head() {
	$issuer_product_head = '';
	$receipt_id = get_the_ID();
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$order = wc_get_order( $order_id );
	$order_customer_country = $order->get_billing_country();
	$customer_country = $order_customer_country;
	$issuer_product_head = '<tr class="heading ">';
	if ($order_customer_country == 'GR') {
		$issuer_product_head .= '<td class="code_head_td"><p> ΚΩΔΙΚΟΣ</p></td>';
		$issuer_product_head .= '<td class="description_head_td"><p> ΠΕΡΙΓΡΑΦΗ</p></td>';
		$issuer_product_head .= '<td class="quantity_head_td"><p> ΠΟΣΟΤΗΤΑ</p></td>';
		$issuer_product_head .= '<td class="mu_head_td"><p> Μ.Μ</p></td>';
		$issuer_product_head .= '<td class="up_head_td"><p> ΤΙΜΗ ΜΟΝΑΔΑΣ</p></td>';
		$issuer_product_head .= '<td class="vat_head_td"><p> ΦΠΑ %</p></td>';
		$issuer_product_head .= '<td class="pricenovat_head_td"><p> ΤΙΜΗ ΠΡΟ ΦΠΑ</p></td>';
        $issuer_product_head .= '<td class="pricenovat_head_td"><p> ΑΞΙΑ ΦΠΑ</p></td>';
		$issuer_product_head .= '<td class="price_head_td"><p> ΤΕΛΙΚΗ ΑΞΙΑ</p></td>';
	} else {
		$issuer_product_head .= '<td class="code_head_td"><p> PRODUCT ID</p></td>';
		$issuer_product_head .= '<td class="description_head_td"><p> DESCRIPTION</p></td>';
		$issuer_product_head .= '<td class="quantity_head_td"><p> PIECES</p></td>';
		$issuer_product_head .= '<td class="mu_head_td"><p> UNIT</p></td>';
		$issuer_product_head .= '<td class="up_head_td"><p> PRICE PER UNIT</p></td>';
		$issuer_product_head .= '<td class="vat_head_td"><p> VAT %</p></td>';
		$issuer_product_head .= '<td class="pricenovat_head_td"><p> PRICE BEFORE TAXES</p></td>';
        $issuer_product_head .= '<td class="pricenovat_head_td"><p> VAT PRICE</p></td>';
		$issuer_product_head .= '<td class="price_head_td"><p> TOTAL AMOUNT</p></td>';
	}
	$issuer_product_head .= '</tr>';

	$allowed_html = array(
		'tr' => array(
			'class' => array(),
		),
		'td' => array(
			'class' => array(),
		),
		'p' => array(
			'class' => array()
		),
		'span' => array(
			'class' => array()
		)
	);
	echo wp_kses($issuer_product_head, $allowed_html);

//	echo $issuer_product_head;
}

function primer_display_issuer_product($i,$last_page) {

	$issuer_product = '';
	$receipt_id = get_the_ID();
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$order = wc_get_order( $order_id );
    $total_products_number = total_products_order();
	$discount = $order->get_discount_total();
	$total_tax = $order->get_total_tax();
	$get_taxes = array();
    $total_products_count = times_html();
	$tax_classes   = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
	if ( ! in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
		array_unshift( $tax_classes, '' );
	}
    $general_settings = get_option('primer_generals');
    $per_page_product = 5;
    if(isset($general_settings['products_per_page_receipt']) && $general_settings['products_per_page_receipt'] != null && $general_settings['products_per_page_receipt'] != ''){
        $per_page_product = $general_settings['products_per_page_receipt'] + 1;
    }
    //calculate the products shown for each page
	$inside_tax_rate = '';
    $product_count=$i*$per_page_product;
    $product_count_html =0;
    $product_per_page = $product_count - $per_page_product;

    ///
    $get_coupons = $order->get_coupon_codes();
    $discount_difference = $order->get_total() - ($order->get_shipping_total() + $order->get_total_tax());
    $discount_percentage = round(number_format(100 - (($discount_difference / $order->get_subtotal()) * 100), 2,'.', ''));

	foreach ( $order->get_items() as $item_id => $item ) {
        $product_count_html++;
        if(($product_count_html<=$product_count)&&($product_count_html>$product_per_page)){
		$issuer_product .= '<tr class="products table_borders">';
		$product_id = $item->get_product_id();
		//$product_instance = wc_get_product($product_id);
		$issuer_product .= '<td class="table_borders"><span class="item_code">'.$product_id.'</span></td>';
		$product_name = $item->get_name();
        $order_country = $order->get_billing_country();
            $attributes = '';
            if ($general_settings['display_attr_var'] == 'on') {
                if ($item->get_all_formatted_meta_data()) {
                    $attribute_strings = array(); // Create an array to hold attribute strings.

                    foreach ($item->get_all_formatted_meta_data() as $meta_key => $formatted_meta) {
                        $attribute_string = $formatted_meta->key . ": " . $formatted_meta->value; // Attribute string
                        $attribute_strings[] = $attribute_string; // Add the attribute string to the array.
                        // If the meta key is 'attribute_pa_color', we assume it's a variation.
                        // Otherwise, treat it as an attribute.
                    }

                    // Combine the attribute strings with commas and a space.
                    $attributes = implode(', ', $attribute_strings);

                    $product_name = get_the_title($product_id) . " - " . $attributes;
                }
            }
            // Add the product name to the table row.
        $issuer_product .= '<td class="table_borders"><span class="item_name">'.$product_name.'</span></td>';
		$quantity = $item->get_quantity();
		$issuer_product .= '<td class="table_borders"><span class="item_quantity">'.$quantity.'</span></td>';

            $licenses = get_option('primer_licenses');
            if($licenses['productKind'] != 'goods'){
                if ($order_country == 'GR') {
                    $measure_unit = 'ΥΠΗΡΕΣΙΑ';
                } else {
                    $measure_unit = 'SERVICES';
                }
            } else {
                if ($order_country == 'GR') {
                    $measure_unit = 'ΤΕΜΑΧΙΑ';
                } else {
                    $measure_unit = 'PIECES';
                }
            }

		$issuer_product .= '<td class="table_borders"><span class="item_mu">'.$measure_unit.'</span></td>';
        $subtotal_order_payment = $item->get_total();
        ///
        if ($discount_percentage > 0 && empty($get_coupons)) {
            $subtotal_order_payment = number_format($subtotal_order_payment - ($subtotal_order_payment * ($discount_percentage / 100)), 2, '.', '');
        }
        $regular_price = $subtotal_order_payment/$quantity;
            $regular_price = number_format((float)$regular_price, 2);
		$issuer_product .= '<td class="table_borders"><span class="item_unit_price">'.$regular_price.'</span></td>';
		$product_tax_class = $item->get_tax_class();
        $inside_tax_rate = "";
        $taxes = $item->get_taxes();
        foreach ($taxes['total'] as $tax_rate_id => $tax_amount) {
            if ($tax_amount > 0) {
                $tax_rate = WC_Tax::_get_tax_rate($tax_rate_id);
                $inside_tax_rate = $tax_rate['tax_rate'];
                break;
            }
        }
            if(is_int($inside_tax_rate) || is_float($inside_tax_rate)){
                $inside_tax_rate = round($inside_tax_rate);
            }
        if ( get_post_meta($order_id, 'is_vat_exempt', true) == 'yes' ) {
            $inside_tax_rate = 0;
        }
        $subtotal_order_payment = $item->get_subtotal();
		$subtotal_item_tax = $item->get_subtotal_tax();
		$total_order_payment = $item->get_total();
        $total_item_vat = $item->get_total_tax();
        //$total_order_payment = $subtotal_order_payment * $quantity;
        ///
        if ($discount_percentage > 0 && empty($get_coupons)) {
            $total_order_payment = number_format($total_order_payment - ($total_order_payment * ($discount_percentage / 100)), 2, '.', '');
            $total_item_vat = number_format($total_item_vat - ($total_item_vat * ($discount_percentage / 100)), 2, '.', '');
        }

		$total_order_item = $total_order_payment + $total_item_vat;
        //make 2 decimal all amount shown to html
            $inside_tax_rate = number_format((float)$inside_tax_rate, 2);
            $subtotal_order_payment = number_format((float)$subtotal_order_payment, 2);
            $total_order_item = number_format((float)$total_order_item, 2);
            $total_order_payment = number_format((float)$total_order_payment, 2);
		$issuer_product .= '<td class="table_borders"><span class="item_vat">'.$inside_tax_rate.'%</span></td>';
		$issuer_product .= '<td class="table_borders"><span class="item_price_novat">'.$total_order_payment.'</span></td>';
        $issuer_product .= '<td class="table_borders"><span class="item_price_novat">' . number_format($total_item_vat, 2) . '</span></td>';
        $issuer_product .= '<td class="table_borders"><span class="item_price_novat">'.$total_order_item.'</span></td>';
		$issuer_product .= '</tr>';
	}}
    //show shipping in last page
    if($last_page == $i){
    if($order->get_shipping_total()) {
        foreach( $order->get_items( 'shipping' ) as $item_id => $item ){
            $order_item_name             = $item->get_name();
            $order_item_type             = $item->get_type();
            $shipping_method_title       = $item->get_method_title();
            $shipping_method_id          = $item->get_method_id(); // The method ID
            $shipping_method_instance_id = $item->get_instance_id(); // The instance ID
            $shipping_method_total       = $item->get_total();
            $shipping_method_total_tax   = $item->get_total_tax();
            $shipping_method_taxes       = $item->get_taxes();

        $issuer_product .= '<tr class="products table_borders">';
        $product_id = "EA";
        $issuer_product .= '<td class="table_borders"><span class="item_code">' . $product_id . '</span></td>';
        $issuer_product .= '<td class="table_borders"><span class="item_name">' . $order_item_name . '</span></td>';
        $quantity = 1;
        $order_country = $order->get_billing_country();
        $issuer_product .= '<td class="table_borders"><span class="item_quantity">' . $quantity . '</span></td>';

        $licenses = get_option('primer_licenses');
            if($licenses['productKind'] != 'goods'){
                if ($order_country == 'GR') {
                    $measure_unit = 'ΥΠΗΡΕΣΙΑ';
                } else {
                    $measure_unit = 'SERVICES';
                }
            } else {
                if ($order_country == 'GR') {
                    $measure_unit = 'ΤΕΜΑΧΙΑ';
                } else {
                    $measure_unit = 'PIECES';
                }
            }
            $shipping_method_total = number_format((float)$shipping_method_total, 2);
        $issuer_product .= '<td class="table_borders"><span class="item_mu">' . $measure_unit . '</span></td>';
        $issuer_product .= '<td class="table_borders"><span class="item_unit_price">' . $shipping_method_total . '</span></td>';
        $tax_arr = json_decode(json_encode($shipping_method_taxes), true);
        $inside_tax_rate_ship = null;
        foreach ($tax_arr as $tax) {
            if (isset($tax['tax_rate_class']) && $shipping_method_taxes == $tax['tax_rate_class']) {
                $inside_tax_rate_ship = $tax['tax_rate'];
            }
        }
        $total_tax_total = $shipping_method_total + $shipping_method_total_tax;
            $inside_tax_rate_ship=round($order->get_shipping_tax() / $order->get_total_shipping(), 2) * 100;
            $inside_tax_rate_ship = number_format((float)$inside_tax_rate_ship, 2);
            $total_tax_total = number_format((float)$total_tax_total, 2);
        $issuer_product .= '<td class="table_borders"><span class="item_vat">' . $inside_tax_rate_ship . '%</span></td>';
        $issuer_product .= '<td class="table_borders"><span class="item_price_novat">' . $shipping_method_total . '</span></td>';
        $issuer_product .= '<td class="table_borders"><span class="item_price_novat">' . number_format($shipping_method_total_tax, 2) . '</span></td>';
        $issuer_product .= '<td class="table_borders"><span class="item_price_novat">' . $total_tax_total . '</span></td>';
        $issuer_product .= '</tr>';
    }}
        $fee_total = '';
        $fee_total_tax = '';
        foreach( $order->get_items('fee') as $item_id => $item_fee ){
            // The fee total amount
            $fee_total = $item_fee->get_total();
            // The fee total tax amount
            $fee_total_tax = $item_fee->get_total_tax();
            $fee_net_value = $fee_total-$fee_total_tax;
            $fee_tax_rate = round($item_fee->get_total_tax() / $item_fee->get_total(), 2) * 100;
        }
        if($fee_total > 0){
            $issuer_product .= '<tr class="products table_borders">';
            $product_id = "ΔΕ";
            $issuer_product .= '<td class="table_borders"><span class="item_code">' . $product_id . '</span></td>';
            $issuer_product .= '<td class="table_borders"><span class="item_name">ΔΙΑΦΟΡΑ ΕΞΟΔΑ</span></td>';
            $quantity = 1;
            $issuer_product .= '<td class="table_borders"><span class="item_quantity">' . $quantity . '</span></td>';
            $licenses = get_option('primer_licenses');
            if($licenses['productKind'] != 'goods'){
                if ($order_country == 'GR') {
                    $measure_unit = 'ΥΠΗΡΕΣΙΑ';
                } else {
                    $measure_unit = 'SERVICES';
                }
            } else {
                if ($order_country == 'GR') {
                    $measure_unit = 'ΤΕΜΑΧΙΑ';
                } else {
                    $measure_unit = 'PIECES';
                }
            }
            $fee_total = number_format((float)$fee_total, 2);
            $issuer_product .= '<td class="table_borders"><span class="item_mu">' . $measure_unit . '</span></td>';
            $issuer_product .= '<td class="table_borders"><span class="item_unit_price">' . $fee_total . '</span></td>';
            $total_tax_total_fee = $fee_total + $fee_total_tax;
            $inside_tax_rate_fee=round($fee_total_tax / $fee_total, 2) * 100;
            $inside_tax_rate_fee = number_format((float)$inside_tax_rate_fee, 2);
            $total_tax_total_fee = number_format((float)$total_tax_total_fee, 2);
            $issuer_product .= '<td class="table_borders"><span class="item_vat">' . $inside_tax_rate_fee . '%</span></td>';
            $issuer_product .= '<td class="table_borders"><span class="item_price_novat">' . $fee_total . '</span></td>';
            $issuer_product .= '<td class="table_borders"><span class="item_price_novat">' . number_format($fee_total_tax, 2) . '</span></td>';
            $issuer_product .= '<td class="table_borders"><span class="item_price_novat">' . $total_tax_total_fee . '</span></td>';
            $issuer_product .= '</tr>';
        }
    }
	$allowed_html = array(
		'tr' => array(
			'class' => array(),
		),
		'td' => array(
			'class' => array(),
		),
		'p' => array(
			'class' => array()
		),
		'span' => array(
			'class' => array()
		)
	);
	echo wp_kses($issuer_product, $allowed_html);

//	echo $issuer_product;
}
//table for taxes rates in html
function primer_display_issuer_tax_total() {
    $check_vat_ship = '';
    $issuer_product_tax_total = '';

    $receipt_id = get_the_ID();
    $issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

    $order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);

    $order = wc_get_order( $order_id );

    $order_customer_country = $order->get_billing_country();

    $customer_country = $order_customer_country;
    $tax_percent= array();
    $tax_total=array();
    $net_total=array();
    $total_with_vat=array();

    $i =0;
    foreach($order->get_items('tax') as $item_id => $item ) {
        $tax_rate_id = $item->get_rate_id(); // Tax rate ID
        $tax_rate_code = $item->get_rate_code(); // Tax code
        $tax_total[$i] = $item->get_tax_total(); // Tax Total
        $tax_percent[$i] = WC_Tax::get_rate_percent($tax_rate_id);// Tax percentage
        $tax_rate = str_replace('%', '', $tax_percent);
        foreach ( $order->get_items() as $item_id => $item_tax ) {
            $product_id = $item_tax->get_product_id();
          //  $product_instance = wc_get_product($product_id);
            $product_tax_class = $item_tax->get_tax_class();
            $taxes = WC_Tax::get_rates_for_tax_class( $product_tax_class );

            $tax_arr = json_decode(json_encode($taxes), true);
            $inside_tax_rate = 0;
            foreach ( $tax_arr as $tax ) {
                if(!empty($tax['tax_rate_country']) && $tax['postcode_count'] != 0 && $tax['city_count'] !=0 && !empty($tax['tax_rate_state'])){
                    if($tax['tax_rate_country'] == $order_customer_country && (in_array($order->get_billing_postcode(),$tax['postcode']) || in_array($order->get_shipping_postcode(),$tax['postcode'])) && (in_array(strtoupper($order->get_billing_city()),$tax['city']) || in_array(strtoupper($order->get_shipping_city()),$tax['city'])) && ($order->get_billing_state() == $tax['tax_rate_state'] || $order->get_shipping_state() == $tax['tax_rate_state'])){
                        $inside_tax_rate = $tax['tax_rate'];
                        break;
                    }else{
                        continue;
                    }
                }elseif(!empty($tax['tax_rate_country']) && $tax['postcode_count'] != 0 && $tax['city_count'] !=0){
                    if($tax['tax_rate_country'] == $order_customer_country && (in_array($order->get_billing_postcode(),$tax['postcode']) || in_array($order->get_shipping_postcode(),$tax['postcode'])) && (in_array(strtoupper($order->get_billing_city()),$tax['city']) || in_array(strtoupper($order->get_shipping_city()),$tax['city']))){
                        $inside_tax_rate = $tax['tax_rate'];
                        break;
                    }else{
                        continue;
                    }
                }elseif(!empty($tax['tax_rate_country']) && $tax['postcode_count'] != 0){
                    if($tax['tax_rate_country'] == $order_customer_country && (in_array($order->get_billing_postcode(),$tax['postcode']) || in_array($order->get_shipping_postcode(),$tax['postcode']))){
                        $inside_tax_rate = $tax['tax_rate'];
                        break;
                    }else{
                        continue;
                    }
                }elseif(!empty($tax['tax_rate_country'])){
                    if($tax['tax_rate_country'] == $order_customer_country){
                        $inside_tax_rate = $tax['tax_rate'];
                        break;
                    }else{
                        continue;
                    }
                }elseif(empty($tax['tax_rate_country']) &&  $tax['postcode_count'] == 0 && $tax['city_count'] ==0 && empty($tax['tax_rate_state'])){
                    if($item_tax->get_total_tax() !=0){
                        $inside_tax_rate = $tax['tax_rate'];
                        break;
                    }else{
                        $inside_tax_rate = 0;
                    }
                }
            }
            if(is_int($inside_tax_rate) || is_float($inside_tax_rate)){
                $inside_tax_rate = round($inside_tax_rate);
            }
            if($inside_tax_rate == (int)$tax_percent[$i]){
                if(isset($net_total[$i])) {
                    $net_total[$i] += $item_tax->get_total();
                }else{
                    $net_total[$i] = $item_tax->get_total();
                }
            }
        }
        if($order->get_shipping_total()) {
            $check_vat_ship = 1;
            foreach( $order->get_items( 'shipping' ) as $item_id => $item ){
                $order_item_name             = $item->get_name();
                $order_item_type             = $item->get_type();
                $shipping_method_title       = $item->get_method_title();
                $shipping_method_id          = $item->get_method_id(); // The method ID
                $shipping_method_instance_id = $item->get_instance_id(); // The instance ID
                $shipping_method_total_tax_box       = $item->get_total();
                $shipping_method_total_tax   = $item->get_total_tax();
                $shipping_method_taxes       = $item->get_taxes();
                $total_tax_total_tax = $shipping_method_total_tax_box + $shipping_method_total_tax;
                $inside_tax_rate_ship_tax=round($order->get_shipping_tax() / $order->get_total_shipping(), 2) * 100;
                if (isset($tax_rate[$i]) && $inside_tax_rate_ship_tax == $tax_rate[$i]) {
                    if (isset($net_total[$i])) {
                        $net_total[$i] = $net_total[$i] + $shipping_method_total_tax_box;
                    } else {
                        $net_total[$i] = $shipping_method_total_tax_box;
                    }

                    if (isset($tax_total[$i])) {
                        $tax_total[$i] = $tax_total[$i] + $shipping_method_total_tax;
                    } else {
                        $tax_total[$i] = $shipping_method_total_tax;
                    }
                }

            }
        }
        foreach( $order->get_items('fee') as $item_id => $item_fee ) {
            // The fee total amount
            $fee_total = $item_fee->get_total();
            // The fee total tax amount
            $fee_total_tax = $item_fee->get_total_tax();
            $fee_net_value = $fee_total - $fee_total_tax;
            $fee_tax_rate = round($item_fee->get_total_tax() / $item_fee->get_total(), 2) * 100;
            if($fee_tax_rate == $tax_rate[$i]){
                $net_total[$i]= $net_total[$i] + $fee_total;
                $tax_total[$i] = $tax_total[$i];
            }
        }


        if (isset($net_total[$i]) && isset($tax_total[$i])) {
            $total_with_vat[$i] = $net_total[$i] + $tax_total[$i];
        } else {
            $total_with_vat[$i] = 0.00; // Set to a default value if either value is not set.
        }
        $i++;
    }
    $issuer_product_tax_total .= '<tr>';
        if($order_customer_country == 'GR') {
            $issuer_product_tax_total .= '<td class="bold table_tax" style="width: 50%"><span>Συντελεστής ΦΠΑ</span></td>';
        }else{
            $issuer_product_tax_total .= '<td class="bold table_tax" style="width: 50%"><span>VAT Rate</span></td>';
        }
        for($i=0; $i< count($tax_percent); $i++) {
            $issuer_product_tax_total .= '<td class="table_tax"><span class="table_tax">' . $tax_percent[$i] . '</span></td>';
        }
    $issuer_product_tax_total .= '</tr>';
    $issuer_product_tax_total .= '<tr>';
    if($order_customer_country == 'GR') {
        $issuer_product_tax_total .= '<td class="skin bold table_tax" style="width: 50%"><span>Καθαρή Αξία</span></td>';
    }else{
        $issuer_product_tax_total .= '<td class="skin bold table_tax" style="width: 50%"><span>Net Value</span></td>';
    }
    for($i=0; $i< count($tax_percent); $i++) {
        if (isset($net_total[$i])) {
            $net_total[$i] = number_format((float)$net_total[$i], 2);
        } else {
            $net_total[$i] = 0.00; // Set to a default value if not set.
        }

        $issuer_product_tax_total .= '<td class="table_tax"><span class="table_tax">' . $net_total[$i] . '</span></td>';
    }
    $issuer_product_tax_total .= '</tr>';
    $issuer_product_tax_total .= '<tr>';
    if($order_customer_country == 'GR') {
        $issuer_product_tax_total .= '<td class="skin bold table_tax" style="width: 50%"><span>Αξία ΦΠΑ</span></td>';
    }else{
        $issuer_product_tax_total .= '<td class="skin bold table_tax" style="width: 50%"><span>VAT Value</span></td>';
    }
    for($i=0; $i< count($tax_percent); $i++) {
        $tax_total[$i] = number_format((float)$tax_total[$i], 2);
        $issuer_product_tax_total .= '<td class="table_tax"><span class="table_tax">' . $tax_total[$i] . '</span></td>';
    }
    $issuer_product_tax_total .= '</tr>';
    $issuer_product_tax_total .= '<tr>';
    if($order_customer_country == 'GR') {
        $issuer_product_tax_total .= '<td class="skin bold table_tax" style="width: 50%"><span>Τελική Αξία</span></td>';
    }else{
        $issuer_product_tax_total .= '<td class="skin bold table_tax" style="width: 50%"><span>Total Value</span></td>';
    }
    for($i=0; $i< count($tax_percent); $i++) {
        $total_with_vat[$i] = number_format((float)$total_with_vat[$i], 2);
        $issuer_product_tax_total .= '<td class="table_tax"><span class="table_tax">' .$total_with_vat[$i]. '</span></td>';
    }
    $issuer_product_tax_total .= '</tr>';
	$allowed_html = array(
		'tr' => array(),
		'td' => array(
			'class' => array(),
			'style' => array(),
		),
		'span' => array(
			'class' => array(),
		)
	);
	echo wp_kses($issuer_product_tax_total, $allowed_html);
//    echo $issuer_product_tax_total;
}

function primer_display_issuer_comments() {
	$issuer_comment = '';
	$receipt_id = get_the_ID();
    $log_id = get_post_meta($receipt_id, 'log_id_for_order', true);
    $mydata_options = get_option('primer_mydata');
    if(array_key_exists('mydata_invoice_notes', $mydata_options)) {
        $primer_invoice_notes = $mydata_options['mydata_invoice_notes'] != null ? $mydata_options['mydata_invoice_notes'] : '';
    }else{
        $primer_invoice_notes = '';
    }
    $length_notes = strlen( (string)$primer_invoice_notes );
    if($length_notes > 750) {
        $primer_invoice_notes = substr($primer_invoice_notes, 0, 750);
    }
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);
	$order = wc_get_order( $order_id );
	$order_customer_country = $order->get_billing_country();
	$customer_country = $order_customer_country;
	$order_comment = $order->get_customer_note();
    // I retrieve the json here of the invoice
    $json = get_post_meta($log_id,'json_send_to_api',true);
    $data = json_decode($json, true);
    $varExemptionCategory = array();

    if (isset($data['invoice'][0]['invoiceDetails']) && is_array($data['invoice'][0]['invoiceDetails'])) {
        foreach ($data['invoice'][0]['invoiceDetails'] as $invoiceDetails) {
            if (isset($invoiceDetails['vatExemptionCategory']) && $invoiceDetails['vatExemptionCategory'] != null) {
                $varExemptionCategory[] = $invoiceDetails['vatExemptionCategory'];
            }
        }
    }

    $varExemptionCategory = array_unique($varExemptionCategory);
    $varExemptionCategory = array_values($varExemptionCategory);

    $count = count($varExemptionCategory);

    $create_json_instance = new Create_json();
    $Vat_exemption_categories = $create_json_instance->getVatExemptionCategories();
    $Vat_exemption_categories_en = $create_json_instance->getVatExemptionCategoriesEn();
	if ($order_customer_country == 'GR') {
        if ( $count>0 ) {
            $exception_vat = '<div><span class="skin bold">ΑΠΑΛΛΑΓΗ ΑΠΟ ΤΟ Φ.Π.Α :</span></div>';
            for ($i = 0; $i < $count; $i++){
                $exception_vat .= '<div>'.$Vat_exemption_categories[$varExemptionCategory[$i]].'</div>';
            }
        } else {
            $exception_vat = '';
        }
		$issuer_comment .= '<div class="cont_notation">' . $exception_vat .'<span class="skin bold">ΠΑΡΑΤΗΡΗΣΕΙΣ:</span>
							<div class="cont_notation_inner">
								<span class="notes">'.$order_comment.'&nbsp;'.$primer_invoice_notes.'</span>
							</div>
						</div>';
	} else {
        if ( $count>0 ) {
            $exception_vat = '<div><span class="skin bold">EXEMPTION FROM VAT :</span></div>';
            for ($i = 0; $i < $count; $i++) {
                $exception_vat .= '<div>' . $Vat_exemption_categories_en[$varExemptionCategory[$i]] . '</div>';
            }
        } else {
            $exception_vat = '';
        }
		$issuer_comment .= '<div class="cont_notation">' . $exception_vat .'<span class="skin bold">COMMENTS:</span>
							<div class="cont_notation_inner">
								<span class="notes">'.$order_comment.'&nbsp;'.$primer_invoice_notes.'</span>
							</div>
						</div>';
	}

	$allowed_html = array(
		'div' => array(
			'class' => array(),
		),
		'span' => array(
			'class' => array(),
		)
	);

	echo wp_kses($issuer_comment, $allowed_html);
}

function primer_sign_issuer_title() {
	$sign_issuer_title = '';
	$receipt_id = get_the_ID();
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$order = wc_get_order( $order_id );

	$order_customer_country = $order->get_billing_country();

	$customer_country = $order_customer_country;

	if ($order_customer_country == 'GR') {
		$sign_issuer_title = '<span class="sign_left">ΕΚΔΟΣΗ</span>';
	} else {
		$sign_issuer_title = '<span class="sign_left">ISSUER</span>';
	}

	$allowed_html = array(
		'span' => array(
			'class' => array(),
		)
	);

	echo wp_kses($sign_issuer_title, $allowed_html);

//	echo $sign_issuer_title;
}

function primer_sign_issuer_fullname() {
	$sign_issuer_fullname = '';
	$receipt_id = get_the_ID();
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$order = wc_get_order( $order_id );

	$order_customer_country = $order->get_billing_country();

	$customer_country = $order_customer_country;

	if ($order_customer_country == 'GR') {
		$sign_issuer_fullname = '<span class="fullname_sign">Ονοματεπώνυμο Υπογραφή</span>';
	} else {
		$sign_issuer_fullname = '<span class="fullname_sign">FULL NAME SIGNATURE</span>';
	}

	$allowed_html = array(
		'span' => array(
			'class' => array()
		)
	);
	echo wp_kses($sign_issuer_fullname, $allowed_html);
}

function primer_sign_recipient_title() {
	$sign_recipient_title = '';

	$receipt_id = get_the_ID();
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$order = wc_get_order( $order_id );

	$order_customer_country = $order->get_billing_country();

	$customer_country = $order_customer_country;

	if ($customer_country == 'GR') {
		$sign_recipient_title = '<span class="sign_right">ΠΑΡΑΛΑΒΗ</span>';
	} else {
		$sign_recipient_title = '<span class="sign_right">RECIPIENT</span>';
	}

	$allowed_html = array(
		'span' => array(
			'class' => array()
		)
	);
	echo wp_kses($sign_recipient_title, $allowed_html);

//	echo $sign_recipient_title;
}

function primer_sign_recipient_fullname() {
	$sign_recipient_fullname = '';

	$receipt_id = get_the_ID();
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$order = wc_get_order( $order_id );

	$order_customer_country = $order->get_billing_country();

	$customer_country = $order_customer_country;

	if ($customer_country == 'GR') {
		$sign_recipient_fullname = '<span class="fullname_sign">Ονοματεπώνυμο Υπογραφή</span>';
	} else {
		$sign_recipient_fullname = '<span class="fullname_sign">FULL NAME <BR>SIGNATURE</span>';
	}

	$allowed_html = array(
		'span' => array(
			'class' => array()
		),
		'br' => array(),
	);
	echo wp_kses($sign_recipient_fullname, $allowed_html);

//	echo $sign_recipient_fullname;
}

function primer_sum_unit_title() {
	$sum_unit_title = '';
	$receipt_id = get_the_ID();
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$order = wc_get_order( $order_id );
	$order_customer_country = $order->get_billing_country();
	$customer_country = $order_customer_country;
	if ($order_customer_country == 'GR') {
		$sum_unit_title = 'ΣΥΝΟΛΟ ΤΕΜΑΧΙΩΝ: ';
	} else {
		$sum_unit_title = 'SUM OF UNITS: ';
	}
	echo esc_html__($sum_unit_title, 'primer');
}

function primer_sum_unit_count() {
	$sum_unit_count = '';
	$receipt_id = get_the_ID();
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$order = wc_get_order( $order_id );
	$sum = 0;
    $shipping_items = 0;

	foreach ( $order->get_items() as $item_id => $item ) {
		$quantity = $item->get_quantity();
		$sum += $quantity;
	}
    if ($order->get_shipping_total()) {
        $shipping_total = $order->get_shipping_total();
        $shipping_items = count(explode(', ', $shipping_total));
    }
    $sum += $shipping_items ;
	$sum_unit_count = $sum;

    $licenses = get_option('primer_licenses');
    if ($licenses['productKind'] != 'goods') {
        $sum_unit_count = '';
    }
    echo esc_html__($sum_unit_count, 'primer');
//	echo $sum_unit_count;
}

function primer_display_issuer_order_total_price($i,$last_page) {

	$issuer_total = '';
	$receipt_id = get_the_ID();
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);
	$order = wc_get_order( $order_id );
	$order_customer_country = $order->get_billing_country();
	$customer_country = $order_customer_country;

	/*foreach ( $order->get_items() as $item_id => $item ) {
		$product_id = $item->get_product_id();
		$product_instance = wc_get_product($product_id);
	} */

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $get_coupons = $order->get_coupon_codes();
    $discount_difference = $order->get_total() - ($order->get_shipping_total() + $order->get_total_tax());
    $discount_percentage = round(number_format(100 - (($discount_difference / $order->get_subtotal()) * 100), 2,'.', ''));
    $total_without_shipping = $order->get_total() - ($order->get_shipping_total() + $order->get_shipping_tax());

    foreach ( $order->get_items() as $item_id => $item ){
        $taxis_before_discount += number_format($item->get_subtotal_tax(), 2, '.', '');
        $total_item_vat += number_format(($item->get_total_tax() - ($item->get_total_tax() * ($discount_percentage / 100))), 2, '.', '');
        $discount_items_total += $item->get_total() - ($item->get_total() * ($discount_percentage / 100));
    }
    $subtotal_before_discount = $order->get_subtotal() + $taxis_before_discount;
    $total_discount_amount = number_format($subtotal_before_discount - $total_without_shipping, 2, '.', '');
    $total_item_vat += $order->get_shipping_tax();
    $total_discount_difference = $total_item_vat + $discount_items_total + $order->get_shipping_total();
    $total_discount_difference = number_format($total_discount_difference, 2, '.', '');
    

    $discount_tax = $order->get_discount_tax();
	$discount_total = $order->get_discount_total();
	$fees = $order->get_fees();
	$shipping_tax = $order->get_shipping_tax();
	$shipping_total = $order->get_shipping_total();
	$tax_totals = $order->get_tax_totals();
	$taxes = $order->get_taxes();
	$total = $order->get_total();
    $fee_total = 0;
    //echo get_post_meta($order_id, '_order_tax',true);
    //$net_total_products = get_post_meta($order_id, '_order_total',true) - get_post_meta($order_id, '_order_tax',true) - get_post_meta($order_id, '_order_shipping_tax',true) - get_post_meta($order_id, '_cart_discount',true)  ;
    //echo $net_total_products;
    foreach( $order->get_items('fee') as $item_id => $item_fee ){
        // The fee total amount
        $fee_total = $item_fee->get_total();
        // The fee total tax amount
        $fee_total_tax = $item_fee->get_total_tax();
        $fee_net_value = $fee_total-$fee_total_tax;
        $fee_tax_rate = round($item_fee->get_total_tax() / $item_fee->get_total(), 2) * 100;
    }

	$subtotal = number_format($order->get_subtotal() + $shipping_total + $fee_total, 2, '.', '');
	$total_discount = $order->get_total_discount();
	$total_tax = $order->get_total_tax();
    $total_item_vat = number_format((float)$total_item_vat, 2, '.', '');
    ////////////////////////////////////////////////////////////////////

    if ($discount_percentage > 0 && empty($get_coupons)) {
        if ($total_discount_difference < $total) {
            $total = $total_discount_difference;
            $total_tax = $total_item_vat;
        }
    }

	$currency   = $order->get_currency();
	$currency_symbol = get_woocommerce_currency_symbol( $currency );

	//$price_excl_tax = wc_get_price_excluding_tax( $product_instance ); // price without VAT
	//$price_incl_tax = $price_excl_tax + $total_tax;  // price with VAT

	$issuer_total .= '<div class="totals">';
	$issuer_total .= '<table class="totals_table">';
	$issuer_total .= '<tr>';
	if ($order_customer_country == 'GR') {
		$issuer_total .= '<td class="text-left"><p>ΑΞΙΑ ΠΡΟ ΕΚΠΤΩΣΗΣ</p></td>';
	} else {
		$issuer_total .= '<td class="text-left"><p>TOTAL NO DISCOUNT</p></td>';
	}
    $total_before_discount = number_format($total + $total_discount + $discount_tax, 2, '.', '');
    $total_before_discount = number_format((float)$total_before_discount, 2);

    //////////////////////////////////////////////////////////
    if ($discount_percentage > 0 && empty($get_coupons)) {
        $total_before_discount += $total_discount_amount;
    }

    if($i == $last_page) {
        $issuer_total .= '<td class="text-right">';
        $issuer_total .= '<p><span class="total_nodiscount">' . $total_before_discount . ' ' . $currency_symbol . '</span> </p>';
        $issuer_total .= '</td>';
    }
	$issuer_total .= '</tr>';

	$issuer_total .= '<tr>';
	if ($order_customer_country == 'GR') {
		$issuer_total .= '<td class="text-left"><p>ΣΥΝΟΛΟ ΕΚΠΤΩΣΗΣ</p></td>';
	} else {
		$issuer_total .= '<td class="text-left"><p>TOTAL DISCOUNT</p></td>';
	}
    if($i == $last_page) {
        $total_discount = number_format((float)$total_discount, 2);
        $discount_tax = number_format((float)$discount_tax, 2); // Ensure it's numeric

        ///////////////////////////////////////////////////////////
        $total_max_discount = $total_discount + $discount_tax;
        if ($discount_percentage > 0 && empty($get_coupons)) {
            $total_max_discount = $total_discount_amount;
        }

        $currency_symbol = (string)$currency_symbol; // Ensure it's a string (if not already)
        $issuer_total .= '<td class="text-right">';
        $issuer_total .= '<p><span class="total_discount">' . ($total_max_discount) . ' ' . $currency_symbol . '</span></p>';
        $issuer_total .= '</td>';
        $issuer_total .= '</tr>';
    }
	$issuer_total .= '<tr>';
	if ($order_customer_country == 'GR') {
		$issuer_total .= '<td class="text-left"><p>ΣΥΝΟΛΟ ΧΩΡΙΣ ΦΠΑ</p></td>';
	} else {
		$issuer_total .= '<td class="text-left"><p>TOTAL WITHOUT VAT</p></td>';
	}
    if($i == $last_page) {
        $total_before_vat = $subtotal - $total_discount;
        $subtotal = number_format((float)$subtotal, 2);
        $issuer_total .= '<td class="text-right">';
        $issuer_total .= '<p><span class="total_withoutvat">' . $total_before_vat . ' ' . $currency_symbol . '</span> </p>';
        $issuer_total .= '</td>';
        $issuer_total .= '</tr>';
    }
	$issuer_total .= '<tr>';
	if ($order_customer_country == 'GR') {
		$issuer_total .= '<td class="text-left"><p>Φ.Π.Α</p></td>';
	} else {
		$issuer_total .= '<td class="text-left"><p>TAXES</p></td>';
	}
    if($i == $last_page) {
        $total_tax = number_format((float)$total_tax, 2);
        if ($discount_difference > 0 && empty($get_coupons)) {
            if ($total_tax < $total_item_vat) {
                $total += 0.01;
            }
        }

        if($discount_difference > 0 && empty($get_coupons)) {
            $total_tax = $total_item_vat;
        }
        $issuer_total .= '<td class="text-right">';
        $issuer_total .= '<p><span class="amounttotal">' . $total_tax . ' ' . $currency_symbol . '</span> </p>';
        $issuer_total .= '</td>';
        $issuer_total .= '</tr>';
    }
	$issuer_total .= '<tr>';
	if ($order_customer_country == 'GR') {
		$issuer_total .= '<td class="text-left"><p>ΤΕΛΙΚΟ ΣΥΝΟΛΟ</p></td>';
	} else {
		$issuer_total .= '<td class="text-left"><p>TOTAL SUM</p></td>';
	}
    if($i == $last_page) {
        $total = number_format((float)$total, 2);
        $issuer_total .= '<td class="text-right">';
        $issuer_total .= '<p><span class="amounttotal">' . $total . ' ' . $currency_symbol . '</span> </p>';
        $issuer_total .= '</td>';
        $issuer_total .= '</tr>';
    }

	$issuer_total .= '<tr class="blank_row bordered"><td class="text-left">&nbsp;</td></tr>';

	$issuer_total .= '<tr>';
	if ($order_customer_country == 'GR') {
		$issuer_total .= '<td class="text-left finalprice"><p>ΠΛΗΡΩΤΕΟ ΠΟΣΟ</p></td>';
	} else {
		$issuer_total .= '<td class="text-left finalprice"><p>TOTAL PAYMENT</p></td>';
	}
    if($i == $last_page) {
        $issuer_total .= '<td class="text-right">';
        $issuer_total .= '<p><span class="totalpayment">' . $total . ' ' . $currency_symbol . '</span> </p>';
        $issuer_total .= '</td>';
        $issuer_total .= '</tr>';
    }
	$issuer_total .= '</table>';
	$issuer_total .= '<div class="total_funny_box"></div>';
	$issuer_total .= '</div>';

	$allowed_html = array(
		'table' => array(
			'class' => array(),
		),
		'tr' => array(
			'class' => array(),
		),
		'td' => array(
			'class' => array(),
			'style' => array(),
		),
		'div' => array(
			'class' => array(),
		),
		'span' => array(
			'class' => array(),
		),
		'p' => array(
			'class' => array()
		)
	);

	echo wp_kses($issuer_total, $allowed_html);
//	echo $issuer_total;

}

function primer_display_issuer_logo() {
	$check_use_logo = primer_get_mydata_use_logo();
	if (!empty(primer_get_mydata_logo()) && $check_use_logo == 'on') {

		$find_invoice_in_slug = '';
		$invoice_type = get_the_terms(get_the_ID(), 'receipt_status');
		if (is_array($invoice_type)) {
			$invoice_type_slug = $invoice_type[0]->slug;
			$invoice_type_name = explode('_', $invoice_type_slug);
			//$find_invoice_in_slug = $invoice_type_name[1];
		}

		$mydata_options = get_option('primer_mydata');
		$photo_id_arg = explode(':', $mydata_options['image_api_id']);
		if (count($photo_id_arg) > 1) {
			$response_key = $photo_id_arg[0];
			$response_value = $photo_id_arg[1];
			$response_key = str_replace('"', '', $response_key);
			$response_value = str_replace('"', '', $response_value);
			$photo_id = ltrim($response_value);
			if (isset($_GET['type_logo'])) {
				echo esc_attr($photo_id);
			}
		}

		if (!isset($_GET['type_logo'])) {
			echo primer_get_mydata_logo() ? '<img class="logo_img" src="'.wp_get_attachment_image_url( primer_get_mydata_logo(),'full' ).'">' : '';
		}
	} else {
		echo '';
	}
}

function primer_get_mydata_logo() {
	$mydata = PrimerSettings::get_mydata_details();
	return apply_filters( 'primer_get_mydata_logo', $mydata['logo'], $mydata );
}

function primer_get_mydata_use_logo() {
	$mydata = PrimerSettings::get_mydata_use_details();
	return apply_filters( 'primer_get_mydata_use_logo', $mydata['use_logo'], $mydata );
}

function primer_display_invoice_information() {
	$invoice_information_container = '';
    $licenses = get_option('primer_licenses');
	$receipt_id = get_the_ID();
    $order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
    $order = wc_get_order( $order_id );
    $order_customer_country = $order->get_billing_country();

	$receipt_invoice_number = get_post_meta($receipt_id, '_primer_receipt_number', true);
    $receipt_invoice_series = get_post_meta($receipt_id, '_primer_receipt_series', true);
	$receipt_invoice_number = $receipt_invoice_number ? $receipt_invoice_number : $receipt_id;

	$invoice_type_text = '';

	$invoice_type = get_the_terms($receipt_id, 'receipt_status');
	$invoice_type_slug = $invoice_type[0]->slug;
    if($invoice_type_slug == 'credit-invoice' || $invoice_type_slug == 'credit-receipt'){
        $find_invoice_in_slug = $invoice_type_slug;
    }else{
	$invoice_type_name = explode('_', $invoice_type_slug);
	$find_invoice_in_slug = $invoice_type_name[1];
        }
    if($order_customer_country == 'GR'){
        if ($find_invoice_in_slug == 'receipt') {
            if($licenses['productKind'] == 'goods') {
                $invoice_type_text = __('Απόδειξη Λιανικής');
            }else{
                $invoice_type_text = __('Απόδειξη Παροχής Υπηρεσιών');
            }
        }
        if ($find_invoice_in_slug == 'invoice') {
            if($licenses['productKind'] == 'goods') {
                $invoice_type_text = __('Τιμολόγιο Πώλησης');
            }else{
                $invoice_type_text = __('Τιμολόγιο Παροχής Υπηρεσιών');
            }
        }
        if($find_invoice_in_slug == 'credit-receipt'){
                $invoice_type_text = __('Πιστωτικό Στοιχείο Λιανικής');
        }
        if($find_invoice_in_slug == 'credit-invoice'){
            $invoice_type_text = __('Πιστωτικό Τιμολόγιο Συσχετιζόμενο');
        }
    }else {
        if ($find_invoice_in_slug == 'receipt') {
            if($licenses['productKind'] == 'goods') {
                $invoice_type_text = __('RETAIL RECEIPT');
            }else{
                $invoice_type_text = __('PROOF OF SERVICE');
            }
        }
        if ($find_invoice_in_slug == 'invoice') {
            if($licenses['productKind'] == 'goods') {
                $invoice_type_text = __('SALE INVOICE');
            }else{
                $invoice_type_text = __('INVOICE');
            }
        }
        if($find_invoice_in_slug == 'credit-receipt'){
            $invoice_type_text = __('Credit Retail Receipt');
        }
        if($find_invoice_in_slug == 'credit-invoice'){
            $invoice_type_text = __('Credit Invoice Related');
        }
    }
	$invoice_information_container = '<tr>';

	$invoice_information_container .= '<td><span class="invoice_type">'.$invoice_type_text.'</span></td>';
    if($receipt_invoice_series != 'EMPTY') {
        $invoice_information_container .= '<td><span class="invoice_series">' . $receipt_invoice_series . '</span></td>';
    }else{
        $invoice_information_container .= '<td><span class="invoice_series"></span></td>';
    }
	$invoice_information_container .= '<td><span class="invoice_number">'.$receipt_invoice_number.'</span></td>';

	$receipt_order_date = get_the_date('d/m/Y', $receipt_id);
	$receipt_order_time = get_the_date('H:i', $receipt_id);

    if($find_invoice_in_slug == 'credit-receipt' || $find_invoice_in_slug == 'credit-invoice'){
        $receipt_date = get_post_meta($receipt_id, 'credit_success_mydata_date', true);
        $receipt_time = get_post_meta($receipt_id, 'credit_success_mydata_time', true);
    }else {
        $receipt_date = get_post_meta($receipt_id, 'success_mydata_date', true);
        $receipt_time = get_post_meta($receipt_id, 'success_mydata_time', true);
    }

    if (empty($receipt_date)) {
		$receipt_date = $receipt_order_date;
	}

	if (empty($receipt_time)) {
		$receipt_time = $receipt_order_time;
	}

	$invoice_information_container .= '<td><span class="invoice_date"> '.$receipt_date.'</span></td>';
	$invoice_information_container .= '<td><span class="invoice_time"> '.$receipt_time.'</span></td>';

	$invoice_information_container .= '</tr>';

	$allowed_html = array(
		'tr' => array(
			'class' => array(),
		),
		'td' => array(
			'class' => array(),
			'style' => array(),
		),

		'span' => array(
			'class' => array(),
		),
	);

	echo wp_kses($invoice_information_container, $allowed_html);

//	echo $invoice_information_container;
}

function primer_display_left_customer_info() {
	$left_customer_info = '';
	$receipt_id = get_the_ID();
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$invoice_type_text = '';
	$invoice_type = get_the_terms($receipt_id, 'receipt_status');
	$invoice_type_slug = $invoice_type[0]->slug;
    if($invoice_type_slug == 'credit-invoice'){
        $find_invoice_in_slug = 'invoice';
    }elseif($invoice_type_slug == 'credit-receipt') {
        $find_invoice_in_slug = 'receipt';
    }else{
        $invoice_type_name = explode('_', $invoice_type_slug);
        $find_invoice_in_slug = $invoice_type_name[1];
    }
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);

	$total_order = wc_get_order( $order_id );

	$order_customer_country = $total_order->get_billing_country();

	$customer_country = $order_customer_country;

	$order_user_first_name = $total_order->get_billing_first_name();
	$order_user_last_name = $total_order->get_billing_last_name();
	$customer_full_name = $order_user_first_name . ' ' . $order_user_last_name;


	$left_customer_info = '<table>';

	$left_customer_info .= '<tr>';
	if ($order_customer_country == 'GR') {
        $left_customer_info .= '<p class="table_titles">ΣΤΟΙΧΕΙΑ ΠΕΛΑΤΗ</p>';
		$left_customer_info .= '<td class="skin bold"><span> ΚΩΔΙΚΟΣ</span></td>';
	} else {
        $left_customer_info .= '<p class="table_titles">CUSTOMER INFORMATION</p>';
		$left_customer_info .= '<td class="skin bold"><span> CUSTOMER ID</span></td>';
	}
	$left_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_code">'.$issuer_client_id.'</span></td>';
	$left_customer_info .= '</tr>';

    if ($find_invoice_in_slug == 'invoice') {
        $profession = get_post_meta($order_id, '_billing_store', true);
        $vat_number = get_post_meta($order_id, '_billing_vat', true);
        $doy = get_post_meta($order_id, '_billing_doy', true);
        $doy_value = primer_return_doy_args()[$doy];
        if (empty($doy_value)) {
            $doy_value = $doy;
        }
    } else {
        $profession = '';
        $vat_number = '';
        $doy = '';
        $doy_value = '';
    }

    $left_customer_info .= '<tr>';
    if ($order_customer_country == 'GR') {
        $left_customer_info .= '<td class="skin bold"><span> ΑΦΜ</span></td>';
    } else {
        $left_customer_info .= '<td class="skin bold"><span> VAT NUMBER</span></td>';
    }
    $left_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_vat">'.$vat_number.'</span></td>';
    $left_customer_info .= '</tr>';

	$left_customer_info .= '<tr>';
	if ($order_customer_country == 'GR') {
		$left_customer_info .= '<td class="skin bold"><span> ΕΠΩΝΥΜΙΑ</span></td>';
	} else {
		$left_customer_info .= '<td class="skin bold"><span> NAME</span></td>';
	}
    $company_bil = get_post_meta($order_id,'_billing_company', true);
    if($find_invoice_in_slug == 'invoice') {
        $left_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_name">' . $company_bil . '</span></td>';
        $left_customer_info .= '</tr>';
    }else{
        $left_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_name">' . $customer_full_name . '</span></td>';
        $left_customer_info .= '</tr>';
    }

	$left_customer_info .= '<tr>';
	if ($order_customer_country == 'GR') {
		$left_customer_info .= '<td class="skin bold"><span> ΕΠΑΓΓΕΛΜΑ</span></td>';
	} else {
		$left_customer_info .= '<td class="skin bold"><span> ACTIVITY</span></td>';
	}
	$left_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_activity">'.$profession.'</span></td>';
	$left_customer_info .= '</tr>';

	$left_customer_info .= '<tr>';
	if ($order_customer_country == 'GR') {
        $left_customer_info .= '<td class="skin bold"><span> ΔΟΥ</span></td>';
        $left_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_doy">' . $doy_value . '</span></td>';
        $left_customer_info .= '</tr>';
    }
	$left_customer_info .= '</table>';
	$allowed_html = array(
		'table' => array(
			'class' => array(),
		),
		'tr' => array(
			'class' => array(),
		),
		'td' => array(
			'class' => array(),
			'style' => array(),
		),
		'div' => array(
			'class' => array(),
		),
		'span' => array(
			'class' => array(),
		),
		'p' => array(
			'class' => array()
		)
	);

	echo wp_kses($left_customer_info, $allowed_html);

//	echo $left_customer_info;
}

function primer_display_right_customer_info() {
	$right_customer_info = '';
	$receipt_id = get_the_ID();
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$order = wc_get_order( $order_id );

	$invoice_type_text = '';

	$invoice_type = get_the_terms($receipt_id, 'receipt_status');
	$invoice_type_slug = $invoice_type[0]->slug;
	$invoice_type_name = explode('_', $invoice_type_slug);
	//$find_invoice_in_slug = $invoice_type_name[1];

	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);


	$order_customer_city = $order->get_billing_city();
	$order_billing_address_1 = $order->get_billing_address_1();
	$order_billing_address_2 = $order->get_billing_address_2();
	$order_shipping_address_1 = $order->get_shipping_address_1();
	$order_shipping_address_2 = $order->get_shipping_address_2();
	$order_customer_country = $order->get_billing_country();

	$customer_country = $order_customer_country;

	$customer_city = $order_customer_city;

	$billing_address = $order_billing_address_1 . ' ' . $order_billing_address_2;

	if (!empty($order_shipping_address_1)) {
		$shipping_address = $order_shipping_address_1 . ' ' . $order_shipping_address_2;
        $order_shipping_name = $order->get_formatted_shipping_full_name();
	} else {
		$shipping_address = $order_billing_address_1 . ' ' . $order_billing_address_2;
	}
    $order_payment_method = $order->get_payment_method_title();
	$right_customer_info = '<table>';
	$right_customer_info .= '<tr>';
	if ($order_customer_country == 'GR') {
        $right_customer_info .= '<p class="table_titles">ΛΟΙΠΑ ΣΤΟΙΧΕΙΑ</p>';
		$right_customer_info .= '<td class="skin bold"><span> ΠΟΛΗ</span></td>';
	} else {
        $right_customer_info .= '<p class="table_titles">OTHER INFORMATION</p>';
		$right_customer_info .= '<td class="skin bold"><span> CITY</span></td>';
	}
	$right_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_city">'.$customer_city.'</span></td>';
	$right_customer_info .= '</tr>';

	$right_customer_info .= '<tr>';
	if ($order_customer_country == 'GR') {
		$right_customer_info .= '<td class="skin bold"><span> ΔΙΕΥΘΥΝΣΗ</span></td>';
	} else {
		$right_customer_info .= '<td class="skin bold"><span> ADDRESS</span></td>';
	}
	$right_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_address">'.$billing_address.'</span></td>';
	$right_customer_info .= '</tr>';

	$right_customer_info .= '<tr>';
    if(!empty($order_shipping_address_1)) {
        if ($order_customer_country == 'GR') {
            $right_customer_info .= '<td class="skin bold"><span> ΔΙΕΥΘΥΝΣΗ ΑΠΟΣΤΟΛΗΣ</span></td>';
        } else {
            $right_customer_info .= '<td class="skin bold"><span> SHIPPING ADDRESS</span></td>';
        }
        $right_customer_info .= '<td class="info_value"><span>: </span><span class="send_place">' . $shipping_address . '</span></td>';
        $right_customer_info .= '</tr>';
        $right_customer_info .= '<tr>';
        if ($order_customer_country == 'GR') {
            $right_customer_info .= '<td class="skin bold"><span> ΠΑΡΑΛΗΠΤΗΣ</span></td>';
        } else {
            $right_customer_info .= '<td class="skin bold"><span>RECIPIENT</span></td>';
        }
        $right_customer_info .= '<td class="info_value"><span>: </span><span class="send_place">' . $order_shipping_name . '</span></td>';
        $right_customer_info .= '</tr>';
        $right_customer_info .= '<tr>';
    }
    if ($order_customer_country == 'GR') {
        $right_customer_info .= '<td class="skin bold"><span> ΤΡΟΠΟΣ ΠΛΗΡΩΜΗΣ</span></td>';
    } else {
        $right_customer_info .= '<td class="skin bold"><span> PAYMENT METHOD</span></td>';
    }
    $right_customer_info .= '<td class="info_value"><span>: </span><span class="send_place">'.$order_payment_method.'</span></td>';
    $right_customer_info .= '</tr>';
	$right_customer_info .= '</table>';

	$allowed_html = array(
		'table' => array(
			'class' => array(),
		),
		'tr' => array(
			'class' => array(),
		),
		'td' => array(
			'class' => array(),
			'style' => array(),
		),
		'div' => array(
			'class' => array(),
		),
		'span' => array(
			'class' => array(),
		),
		'p' => array(
			'class' => array()
		)
	);

	echo wp_kses($right_customer_info, $allowed_html);

//	echo $right_customer_info;
}

function primer_invoice_uid() {
	$invoice_uid = '';

	$receipt_id = get_the_ID();
	$invoice_uid = get_post_meta($receipt_id, 'response_invoice_uid', true);

	echo esc_html($invoice_uid);
}

function primer_invoice_mark() {
	$invoice_mark = '';

	$receipt_id = get_the_ID();
	$invoice_mark = get_post_meta($receipt_id, 'response_invoice_mark', true);

	echo esc_html($invoice_mark);
}

function primer_invoice_authcode() {
	$invoice_authcode = '';

	$receipt_id = get_the_ID();
	$invoice_authcode = get_post_meta($receipt_id, 'response_invoice_authcode', true);

	echo esc_html($invoice_authcode);
}

function primer_generate_qr($receipt_id, $generated_uid) {
	$upload_dir = wp_upload_dir()['basedir'];
	if (!file_exists($upload_dir . '/primer_qrs')) {
		mkdir($upload_dir . '/primer_qrs');
	}

	$is_qr_code_exist = get_post_meta($receipt_id, '_is_qr_code_exist', true);
	$primer = new Primer();
	if (empty($is_qr_code_exist) && !empty($generated_uid)) {
		$receipt_link = "https://primer.gr/mydatasearch/" . $generated_uid;
		$image_name = time() . '_' . $receipt_id . '.png';
		$image_name = sanitize_text_field($image_name);
		$qr_size = 4;
		$qr_frame_size = 2;
		$primer->QRcode->png($receipt_link, PRIMER_QR_IMAGE_DIR . $image_name, QR_ECLEVEL_M, $qr_size, $qr_frame_size);
		update_post_meta($receipt_id, '_is_qr_code_exist', 1);
		update_post_meta($receipt_id, '_product_qr_code', $image_name);
	}
}

function primer_get_generated_qr() {
	$qr_code = '';
	$receipt_id = get_the_ID();
	$receipt_qr_code = get_post_meta($receipt_id, '_product_qr_code', true);
	if (!empty($receipt_qr_code) && file_exists(PRIMER_QR_IMAGE_DIR . $receipt_qr_code)) {
		$path = PRIMER_QR_IMAGE_DIR . $receipt_qr_code;
		$type = pathinfo($path, PATHINFO_EXTENSION);
		$data = file_get_contents($path);
		$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
		$qr_code .= '<img class="product-qr-code-img" src="'.$base64.'" alt="QR Code" /img>';
	}
    echo wp_kses_normalize_entities( $qr_code, [
        'img' => [
            'src'      => true,
            'sizes'    => true,
            'class'    => true,
            'id'       => true,
            'width'    => true,
            'height'   => true,
            'alt'      => true,
            'align'    => true,
        ],
    ] );
}
//get how many pages should be created according to product count
function times_html(){
    $issuer_product = '';
    $receipt_id = get_the_ID();
    $order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
    $order = wc_get_order( $order_id );
    $count_product = 0;
    $html_time = 1;
    $general_settings = get_option('primer_generals');
    $per_page_product = 5;
    if(isset($general_settings['products_per_page_receipt']) && $general_settings['products_per_page_receipt'] != null && $general_settings['products_per_page_receipt'] != ''){
        $per_page_product = $general_settings['products_per_page_receipt'] + 1;
    }

    foreach ( $order->get_items() as $item_id => $item ) {
        $count_product ++;
    }
    if($order->get_shipping_total()) {
        $count_product ++;
    }
    $fee_total = 0;
    foreach( $order->get_items('fee') as $item_id => $item_fee ){
        // The fee total amount
        $fee_total = $item_fee->get_total();
        // The fee total tax amount
        $fee_total_tax = $item_fee->get_total_tax();
        $fee_net_value = $fee_total-$fee_total_tax;
        $fee_tax_rate = round($item_fee->get_total_tax() / $item_fee->get_total(), 2) * 100;
    }
    if($fee_total > 0){
        $count_product ++;
    }


    if($count_product > $per_page_product){

        $html_time = (int)($count_product/$per_page_product) +1;
        if(($count_product % $per_page_product)==0){
            $html_time = $html_time -1;
        }
    }
    return $html_time;
}
function total_products_order(){
    $issuer_product = '';
    $receipt_id = get_the_ID();
    $order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);

    $order = wc_get_order( $order_id );
    $count_product = 0;
    $html_time = 1;

    foreach ( $order->get_items() as $item_id => $item ) {
        $count_product ++;
    }
    if($order->get_shipping_total()) {
        $count_product ++;
    }
    $fee_total = 0;
    foreach( $order->get_items('fee') as $item_id => $item_fee ){
        // The fee total amount
        $fee_total = $item_fee->get_total();
        // The fee total tax amount
        $fee_total_tax = $item_fee->get_total_tax();
        $fee_net_value = $fee_total-$fee_total_tax;
        $fee_tax_rate = round($item_fee->get_total_tax() / $item_fee->get_total(), 2) * 100;
    }
    if($fee_total > 0){
        $count_product ++;
    }
    return $count_product;
}
function get_customer_country() {
    $receipt_id = get_the_ID();
    $issuer_name = get_post_meta($receipt_id, 'receipt_client', true);

    $issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);


    $order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
    $order = wc_get_order( $order_id );

    $order_customer_country = $order->get_billing_country();
    return $order_customer_country;
}
function get_transmission_failure(){
    $receipt_id = get_the_ID();
    $order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
    return get_post_meta($order_id,'transmission_failure_check',true);
}
function get_failure_message(){
    $message = "";

    $receipt_id = get_the_ID();
    $message = get_post_meta($receipt_id, 'connection_fail_message', true);

    echo  esc_html($message);

}

function get_credit_receipt_failed(){
    $receipt_id = get_the_ID();
    $order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
    return get_post_meta($order_id,'credit_receipt_failed_to_issue',true);
}
function get_date_failed(){
    $receipt_id = get_the_ID();
    $order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
    return get_post_meta($order_id,'order_date_failed',true);
}
