<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }

/**
 * Calls the class.
 */
function primer_call_invoice_class() {
	new Primer_Invoice();
}
add_action('primer_loaded', 'primer_call_invoice_class');

class Primer_Invoice {

	/**
	 * @var  object  Instance of this class
	 */
	private static $instance;

	private static $meta_key = array(
		'number'        => '_primer_receipt_number'
	);

	public function __construct() {
	//	add_action( 'wp_insert_post', array( $this, 'update_receipt_number' ), 10, 3 );
		//add_action( 'wp_trash_post', array( $this, 'decrease_receipt_number' ), 10, 1 );
	}

	public static function get_instance() {
		if ( ! ( self::$instance instanceof self ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * update the receipt number sequentially
	 *
	 * @since 1.0.0
	 */
/*	public static function update_receipt_number( $post_id = null, $post = null, $update = null ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
		if ( false !== wp_is_post_revision( $post_id ) ) { return; }
		if ( get_post_type( $post_id ) !== 'primer_receipt' ) { return; }

		$receipts = get_option( 'primer_mydata' );

		$id_of_order = get_post_meta($post_id, 'order_id_to_receipt', true);
		$order_invoice_type = get_post_meta($id_of_order, '_billing_invoice_type', true);
		$order_country = get_post_meta($id_of_order, '_billing_country', true);
		$exist_number = get_post_meta($post_id, '_primer_receipt_number', true);
		if ($order_invoice_type == 'receipt' && $order_country == 'GR') {
			$invoiceType = '11.1';
                if($receipts['mydata_api'] == 'production_api'){
			$receipts['invoice_numbering_gr'] = $exist_number + 1;
		}else{
                    $receipts['invoice_numbering_gr_test_api'] = $exist_number + 1;
                }
        }
		if ($order_invoice_type == 'receipt' && $order_country !== 'GR' && check_zone_country($order_country) == true) {
			$invoiceType = '11.1';
            if($receipts['mydata_api'] == 'production_api'){
                $receipts['invoice_numbering_gr'] = $exist_number + 1;
            }else{
                $receipts['invoice_numbering_gr_test_api'] = $exist_number + 1;
            }
		}

		if ($order_invoice_type == 'receipt' && check_zone_country($order_country) == false) {
			$invoiceType = '11.1';
            if($receipts['mydata_api'] == 'production_api'){
                $receipts['invoice_numbering_gr'] = $exist_number + 1;
            }else{
                $receipts['invoice_numbering_gr_test_api'] = $exist_number + 1;
            }
		}

		if (($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice') && $order_country == 'GR') {
			$invoiceType = '1.1';
            if($receipts['mydata_api'] == 'production_api'){
                $receipts['invoice_numbering_gi'] = $exist_number + 1;
            }else{
                $receipts['invoice_numbering_gi_test_api'] = $exist_number + 1;
            }
		}
		if (($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice') && $order_country !== 'GR' && check_zone_country($order_country) == true) {
			$invoiceType = '1.2';
            if($receipts['mydata_api'] == 'production_api'){
                $receipts['invoice_numbering_within'] = $exist_number + 1;
            }else{
                $receipts['invoice_numbering_within_test_api'] = $exist_number + 1;
            }
		}

		if (($order_invoice_type == 'primer_invoice' || $order_invoice_type == 'invoice') && check_zone_country($order_country) == false) {
			$invoiceType = '1.3';
            if($receipts['mydata_api'] == 'production_api'){
                $receipts['invoice_numbering_outside'] = $exist_number + 1;
            }else{
                $receipts['invoice_numbering_outside_test_api'] = $exist_number + 1;
            }
		}

		if (empty($order_invoice_type) && $order_country == 'GR') {
			$invoiceType = '11.1';
            if($receipts['mydata_api'] == 'production_api'){
                $receipts['invoice_numbering_gr'] = $exist_number + 1;
            }else{
                $receipts['invoice_numbering_gr_test_api'] = $exist_number + 1;
            }
		}

		if (empty($order_invoice_type) && $order_country !== 'GR') {
			$invoiceType = '11.1';
            if($receipts['mydata_api'] == 'production_api'){
                $receipts['invoice_numbering_gr'] = $exist_number + 1;
            }else{
                $receipts['invoice_numbering_gr_test_api'] = $exist_number + 1;
            }
		}

		update_option( 'primer_mydata', $receipts );
	} */

	/**
	 * Get the next invoice number.
	 *
	 * @since   1.0.0
	 */
	/*public static function get_next_receipt_number() {
		$receipts = get_option( 'primer_generals' );

		if ( !isset( $receipts['increment'] )) {
			$receipts['increment'] = 'on';
		}
		if (!isset($receipts['number'])) {
			$receipts['number'] = '000978';
		}
		update_option('primer_generals', $receipts);

		if ( isset( $receipts['increment'] ) && $receipts['increment'] == 'on' ) {
			return $receipts['number'];
		}
		else {
			return null;
		}
	} */

	/**
	 * update the receipt number sequentially
	 *
	 * @since 1.0.0
	 */
	/*public static function decrease_receipt_number( $post_id = null ) {

		if ( false !== wp_is_post_revision( $post_id ) ) { return; }
		if ( get_post_type( $post_id ) !== 'primer_receipt' ) { return; }

		$receipts = get_option( 'primer_generals' );

		if ( isset( $_POST['_primer_receipt_number'] ) ) {
			$this_number = sanitize_text_field( $_POST['_primer_receipt_number'] );
		} elseif ( $post_id > 0 && $post = get_post( $post_id ) ) {
			$this_number = $post->_primer_receipt_number;
		} else {
			$this_number = 0;
		}


		$receipts['increment'] = 'on';
		// clean up the number
		$length = strlen( (string)$this_number ); // get the length of the number

		$new_number = (int)$this_number - 1; // increment number
		$number = zeroise( $new_number, $length ); // return the new number, ensuring correct length (if using leading zeros)

		// set the number in the options as the new, next number and update it.
		$receipts['number'] = (string)$number;
		update_option( 'primer_generals', $receipts );



	} */

	/**
	 * Get the invoice template.
	 *
	 * @since   1.0.0
	 */
	public static function get_greek_template() {
		$invoices 	= get_option( 'primer_mydata' );
		$template 	= isset( $invoices['greek_template'] ) ? $invoices['greek_template'] : 'greek_template1';
		return $template;
	}

	public static function get_english_template() {
		$invoices 	= get_option( 'primer_mydata' );
		$template 	= isset( $invoices['english_template'] ) ? $invoices['english_template'] : 'english_template1';
		return $template;
	}
}
