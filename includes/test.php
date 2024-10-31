<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php do_action( 'wpo_wcpdf_before_document', $this->type, $this->order ); ?>

<table class="head container">
    <tr>
        <td class="header">
            <?php
            /*if( $this->has_header_logo() ) {
                $this->header_logo();
            } else {
                echo $this->get_title();
            }*/
            ?>ΑΠΟΔΕΙΞΗ ΛΙΑΝΙΚΗΣ ΠΩΛΗΣΗΣ
        </td>
        <td class="shop-info">
            <div class="shop-name"><h3><?php $this->shop_name(); ?></h3></div>
            <div class="shop-address"><?php $this->shop_address(); ?></div>
        </td>
    </tr>
</table>

<h1 class="document-type-label">
    <?php if( $this->has_header_logo() ) echo $this->get_title(); ?>
</h1>

<?php do_action( 'wpo_wcpdf_after_document_label', $this->type, $this->order ); ?>

<table class="order-data-addresses">
    <tr>
        <td class="address billing-address">
            <!-- <h3><?php _e( 'Billing Address:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3> -->
            <?php do_action( 'wpo_wcpdf_before_billing_address', $this->type, $this->order ); ?>
            <?php $this->billing_address(); ?>
            <?php do_action( 'wpo_wcpdf_after_billing_address', $this->type, $this->order ); ?>
            <?php if ( isset($this->settings['display_email']) ) { ?>
                <div class="billing-email"><?php $this->billing_email(); ?></div>
            <?php } ?>
            <?php if ( isset($this->settings['display_phone']) ) { ?>
                <div class="billing-phone"><?php $this->billing_phone(); ?></div>
            <?php } ?>
        </td>
        <td class="address shipping-address">
            <?php if ( isset($this->settings['display_shipping_address']) && $this->ships_to_different_address()) { ?>
                <h3><?php _e( 'Ship To:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
                <?php do_action( 'wpo_wcpdf_before_shipping_address', $this->type, $this->order ); ?>
                <?php $this->shipping_address(); ?>
                <?php do_action( 'wpo_wcpdf_after_shipping_address', $this->type, $this->order ); ?>
            <?php } ?>
        </td>
        <td class="order-data">
            <table>
                <?php do_action( 'wpo_wcpdf_before_order_data', $this->type, $this->order ); ?>
                <?php if ( isset($this->settings['display_number']) ) { ?>
                    <tr class="invoice-number">
                        <th><?php _e( 'Invoice Number:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
                        <td><?php $this->invoice_number(); ?></td>
                    </tr>
                <?php } ?>
                <?php if ( isset($this->settings['display_date']) ) { ?>
                    <tr class="invoice-date">
                        <th><?php _e( 'Invoice Date:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
                        <td><?php $this->invoice_date(); ?></td>
                    </tr>
                <?php } ?>
                <tr class="order-number">
                    <th><?php _e( 'Order Number:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
                    <td><?php $this->order_number(); ?></td>
                </tr>
                <tr class="order-date">
                    <th><?php _e( 'Order Date:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
                    <td><?php $this->order_date(); ?></td>
                </tr>
                <tr class="payment-method">
                    <th><?php _e( 'Payment Method:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
                    <td><?php $this->payment_method(); ?></td>
                </tr>
                <?php do_action( 'wpo_wcpdf_after_order_data', $this->type, $this->order ); ?>
            </table>
        </td>
    </tr>
</table>

<?php do_action( 'wpo_wcpdf_before_order_details', $this->type, $this->order ); ?>

<table class="order-details">
    <thead>
    <tr>
        <th class="product"><?php _e('Product', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
        <th class="quantity"><?php _e('Quantity', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
        <th class="price"><?php _e('Price', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
        <th class="quantity"><?php _e('VAT', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
        <th class="price"><?php _e('Total', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
    </tr>
    </thead>
    <tbody>

    <?php
    /* Here Start the editing */


    //Get data of invoice

    $items = $this->get_order_items();
    $invoice_number = $this->get_invoice_number();
    $invoice_number = str_replace('B','',$invoice_number);
    $invoice_number =  intval($invoice_number);



    if( sizeof( $items ) > 0 ) :

        $updated_items = array();

        foreach( $items as $item_id => $item ) {

            //Check if it is a subscription
            $subscription = false;
            $update_item = false;

            if($item['product_id']==84){
                $subscription=true;
                if($invoice_number>13633){
                    $magazine_limit = 9.99;
                }else{
                    $magazine_limit = 10;
                }
                $magazine_price = 4.72;
                $magazine_total = 5;
                $magazine_tax =  0.28;
                $magazine_name = "Μηνιαία Συνδρομή Περιοδικού";
                $item["name"] = "Μηνιαία Συνδρομή Ιστοσελίδας";
            }
            if($item['product_id']==86){
                $subscription=true;
                if($invoice_number>13633){
                    $magazine_limit = 119.99;
                }else{
                    $magazine_limit = 120;
                }
                $magazine_price = 56.6;
                $magazine_total = 60;
                $magazine_tax =  3.4;
                $magazine_name = "Ετήσια Συνδρομή Περιοδικού";
                $item["name"] = "Ετήσια Συνδρομή Ιστοσελίδας";
            }

            if($subscription){

                $subscription_price =  preg_replace('/[^0-9]/', '', $item['single_price'])/100;

                if($magazine_limit<$subscription_price){
                    //We need to process this subsciption

                    $update_item = true;

                    //Update Total
                    $updated_total = $subscription_price - $magazine_total ;
                    $item['single_price'] = $updated_total."€";

                    //Update Price
                    $updated_price = $updated_total / 1.24;
                    $updated_price = number_format((float)$updated_price, 2, '.', '');
                    $item['single_line_total'] = $updated_price."€";

                    //Update VAT
                    $updated_vat = $updated_total - $updated_price  ;
                    $item['vat_clean'] = $updated_vat ;
                    $item['line_tax'] = $updated_vat."€ (24%)";

                    //Add Magazine Item
                    $magazine_item = array(
                        'item_id'=> $item_id.'_mag',
                        'name'=>$magazine_name,
                        'quantity'=>1,
                        'line_tax'=> $magazine_tax."€ (6%)",
                        'vat_clean'=> $magazine_tax ,
                        'single_line_total'=> $magazine_price."€",
                        'single_price'=>$magazine_total."€"
                    );
                    array_push($updated_items, $magazine_item );

                }

            }

            //If not a Subscription eligible for magazine
            if(!$update_item){
                //Set Price
                $price =  preg_replace('/[^0-9]/', '', $item['single_price'])/100;
                if($item['tax_rates']=='6 %'){
                    $updated_price = $price / 1.06;
                }else{
                    $updated_price = $price / 1.24;
                }
                $updated_price = number_format((float)$updated_price, 2, '.', '');
                $item['single_line_total'] =  $updated_price."€";
                //Set Vat
                $updated_vat =$price  - $updated_price  ;
                $item['vat_clean'] = $updated_vat ;
                $item['line_tax'] = $updated_vat."€ (".$item['tax_rates'].")";
            }

            //Update Item
            array_push($updated_items, $item );
        }
        //Run again to print
        $total_vat = 0;
        foreach( $updated_items as $item_id => $item ) :
            ?>
            <tr class="<?php echo apply_filters( 'wpo_wcpdf_item_row_class', $item_id, $this->type, $this->order, $item_id ); ?>">
                <td class="product">
                    <?php $description_label = __( 'Description', 'woocommerce-pdf-invoices-packing-slips' ); // registering alternate label translation ?>
                    <span class="item-name"><?php echo $item['name']; ?></span>
                    <?php do_action( 'wpo_wcpdf_before_item_meta', $this->type, $item, $this->order  ); ?>
                    <span class="item-meta"><?php echo $item['meta']; ?></span>
                    <dl class="meta">
                        <?php $description_label = __( 'SKU', 'woocommerce-pdf-invoices-packing-slips' ); // registering alternate label translation ?>
                        <?php if( !empty( $item['sku'] ) ) : ?><dt class="sku"><?php _e( 'SKU:', 'woocommerce-pdf-invoices-packing-slips' ); ?></dt><dd class="sku"><?php echo $item['sku']; ?></dd><?php endif; ?>
                        <?php if( !empty( $item['weight'] ) ) : ?><dt class="weight"><?php _e( 'Weight:', 'woocommerce-pdf-invoices-packing-slips' ); ?></dt><dd class="weight"><?php echo $item['weight']; ?><?php echo get_option('woocommerce_weight_unit'); ?></dd><?php endif; ?>
                    </dl>
                    <?php do_action( 'wpo_wcpdf_after_item_meta', $this->type, $item, $this->order  ); ?>
                </td>
                <td class="quantity"><?php echo $item['quantity']; ?></td>
                <td class="price"><?php echo $item['single_line_total']; ?></td>
                <td class="vat"><?php echo $item['line_tax']; ?></td>
                <td class="total-price"><?php echo $item['single_price']; ?></td>
            </tr>
            <?php
            $total_vat = $total_vat + $item['vat_clean'] ;
        endforeach;
    endif;

    /* Here ends the editing */ ?>

    </tbody>
    <tfoot>
    <tr class="no-borders">
        <td class="no-borders">
            <!--<div class="customer-notes">
					<?php do_action( 'wpo_wcpdf_before_customer_notes', $this->type, $this->order ); ?>
					<?php if ( $this->get_shipping_notes() ) : ?>
						<h3><?php _e( 'Customer Notes', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
						<?php $this->shipping_notes(); ?>
					<?php endif; ?>
					<?php do_action( 'wpo_wcpdf_after_customer_notes', $this->type, $this->order ); ?>
				</div>-->
        </td>
        <td class="no-borders" colspan="2">
            <table class="totals">
                <tfoot>
                <?php
                $totals = $this->get_woocommerce_totals();
                $total = $totals['cart_subtotal']
                ?>
                <tr class="cart_subtotal">
                    <td class="no-borders"></td>
                    <td class="no-borders"></td>
                    <th class="description">Σύνολο</th>
                    <td colspan="3" lass="price"><span class="totals-price"><?php echo $total['value']; ?></span> (Συμπεριλαμβάνεται ΦΠΑ <?php echo $total_vat.'€' ; ?> ) </td>
                </tr>
                </tfoot>
            </table>
        </td>
    </tr>
    </tfoot>
</table>

<?php do_action( 'wpo_wcpdf_after_order_details', $this->type, $this->order ); ?>

<?php if ( $this->get_footer() ): ?>
    <div id="footer">
        <?php $this->footer(); ?>
    </div><!-- #letter-footer -->
<?php endif; ?>
<?php
do_action('tpp_number_order', $this->order );
do_action( 'wpo_wcpdf_after_document', $this->type, $this->order ); ?>
