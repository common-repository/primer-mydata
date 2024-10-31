<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Calls the class.
 */
function primer_call_shared_class() {
	new PrimerSettings();

}
add_action( 'primer_loaded', 'primer_call_shared_class', 2 );

class PrimerSettings {

	/**
	 * @var  object  Instance of this class
	 */
	protected static $instance;

	/**
	 * @var  array   Array of instantiated option objects
	 */
	protected static $option_instances;

	public function __construct() {
	}

	public static function get_instance() {
		if ( ! ( self::$instance instanceof self ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private static $options = array(
		'mydata'   => 'primer_mydata',
	);

	public static function get_primer_option( $get_option ) {
		$primer_option = get_option( self::$options[$get_option] );
		return $primer_option;
	}

	public static function get_primer_options() {
		foreach ( self::$options as $option_name => $option ) {
			$primer_options[$option_name] = get_option( $option );
		}
		return $primer_options;
	}

	/**
	 * Get the mydata details.
	 *
	 * @since   1.0.0
	 */
	public static function get_mydata_details() {
		$options = self::get_primer_options();

		return apply_filters( 'primer_mydata_details', array(
			'logo'      => isset( $options['mydata']['upload_img_id'] ) ? $options['mydata']['upload_img_id'] : '',
		) );
	}


	public static function get_mydata_use_details() {
		$options = self::get_primer_options();

		return apply_filters( 'primer_mydata_use_details', array(
			'use_logo'      => isset( $options['mydata']['primer_use_logo'] ) ? $options['mydata']['primer_use_logo'] : '',
		) );
	}
}
