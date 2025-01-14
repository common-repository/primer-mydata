<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Class.
 */
class Primer_Admin_notices {

	/**
	 * Stores notices.
	 * @var array
	 */
	private static $notices = array();

	/**
	 * Array of notices - name => callback.
	 * @var array
	 */
	private static $core_notices = array(
		'invalid_mydata_page'         => 'invalid_mydata_page_notice',
		'update_needed_additional_tax' => 'update_needed_additional_tax_notice',
	);

	/**
	 * Constructor.
	 */
	public static function init() {

		self::$notices = get_option( 'primer_admin_notices', array() );

		add_action( 'switch_theme', array( __CLASS__, 'reset_admin_notices' ) );
		add_action( 'primer_activated', array( __CLASS__, 'reset_admin_notices' ) );
		add_action( 'wp_loaded', array( __CLASS__, 'hide_notices' ) );
		add_action( 'shutdown', array( __CLASS__, 'store_notices' ), 999 );

		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'admin_print_styles', array( __CLASS__, 'add_notices' ) );
		}

		add_action( 'wp_ajax_primer_hide_notice', array( __CLASS__, 'hide_notices_ajax' ) );

	}

	/**
	 * Store notices to DB
	 */
	public static function store_notices() {
		update_option( 'primer_admin_notices', self::get_notices() );
	}

	/**
	 * Get notices
	 * @return array
	 */
	public static function get_notices() {
		return self::$notices;
	}

	/**
	 * Remove all notices.
	 */
	public static function remove_all_notices() {
		self::$notices = array();
	}

	/**
	 * Reset notices for themes when switched or a new version of Sliced Invoices is installed.
	 */
	public static function reset_admin_notices() {
		// nothing yet to do here...
	}

	/**
	 * Show a notice.
	 * @param string $name
	 */
	public static function add_notice( $name ) {
		self::$notices = array_unique( array_merge( self::get_notices(), array( $name ) ) );
	}

	/**
	 * Remove a notice from being displayed.
	 * @param  string $name
	 */
	public static function remove_notice( $name ) {
		self::$notices = array_diff( self::get_notices(), array( $name ) );
		delete_option( 'primer_admin_notice_' . $name );
		delete_transient( 'primer_hide_' . $name . '_notice' );
	}

	/**
	 * See if a notice is being shown.
	 * @param  string  $name
	 * @return boolean
	 */
	public static function has_notice( $name ) {
		return in_array( $name, self::get_notices() );
	}

	/**
	 * Hide a notice if the GET variable is set.
	 */
	public static function hide_notices() {
		if ( isset( $_GET['primer-hide-notice'] ) && isset( $_GET['_primer_notice_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_GET['_primer_notice_nonce'], 'primer_hide_notices_nonce' ) ) {
				wp_die( __( 'Action failed. Please refresh the page and retry.', 'primer' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Cheatin&#8217; huh?', 'primer' ) );
			}

			$hide_notice = sanitize_text_field( $_GET['primer-hide-notice'] );

			if ( isset( $_GET['primer-dismiss'] ) && (int)$_GET['primer-dismiss'] === 1 ) {

				update_option( 'primer_admin_notice_' . $hide_notice, '' );

			} else {

				self::remove_notice( $hide_notice );

			}

			if ( isset( $_GET['primer-hide-transient'] ) && (int)$_GET['primer-hide-transient'] > 0 ) {
				set_transient( 'primer_hide_' . $hide_notice . '_notice', 1, intval( $_GET['primer-hide-transient'] ) );
			}

			do_action( 'primer_hide_' . $hide_notice . '_notice' );
		}
	}

	/**
	 * Hide a notice for AJAX requests
	 */
	public static function hide_notices_ajax() {
		if ( isset( $_POST['primer-hide-notice'] ) && isset( $_POST['_primer_notice_nonce'] ) ) {

			$notice = sanitize_text_field( $_POST['primer-hide-notice'] );

			if ( ! wp_verify_nonce( $_POST['_primer_notice_nonce'], 'primer_admin_notice_'.$notice.'_nonce' ) ) {
				wp_die( __( 'Action failed. Please refresh the page and retry.', 'primer' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Cheatin&#8217; huh?', 'primer' ) );
			}

			if ( isset( $_POST['primer-dismiss'] ) && (int)$_POST['primer-dismiss'] === 1 ) {

				update_option( 'primer_admin_notice_' . $notice, '' );

			} else {

				self::remove_notice( $notice );

			}

			if ( isset( $_POST['primer-hide-transient'] ) && (int)$_POST['primer-hide-transient'] > 0 ) {
				set_transient( 'primer_hide_' . $notice . '_notice', 1, intval( $_POST['primer-hide-transient'] ) );
			}

			do_action( 'primer_hide_' . $notice . '_notice' );

			wp_send_json( array( 'status' => 'success' ) );
		}
	}

	/**
	 * Add notices + styles if needed.
	 */
	public static function add_notices() {

		$notices = self::get_notices();

		if ( ! empty( $notices ) ) {
			foreach ( $notices as $notice ) {
				if ( ! empty( self::$core_notices[ $notice ] ) && apply_filters( 'primer_show_admin_notice', true, $notice ) ) {
					add_action( 'admin_notices', array( __CLASS__, self::$core_notices[ $notice ] ) );
				} else {
					add_action( 'admin_notices', array( __CLASS__, 'output_custom_notices' ) );
				}
			}
		}
	}

	/**
	 * Add a custom notice.
	 * @param string $name
	 * @param string $notice_html
	 */
	public static function add_custom_notice( $name, $data ) {
		self::add_notice( $name );
		update_option( 'primer_admin_notice_' . $name, $data );
	}

	/**
	 * Output any stored custom notices.
	 */
	public static function output_custom_notices() {
		$notices = self::get_notices();
		if ( ! empty( $notices ) ) {
			foreach ( $notices as $notice ) {
				if ( empty( self::$core_notices[ $notice ] ) ) {
					if ( ! get_transient( 'primer_hide_' . $notice . '_notice' ) ) {
						$data = get_option( 'primer_admin_notice_' . $notice );
						if ( is_array( $data ) ) {
							$class             = isset( $data['class'] ) ? $data['class'] : '';
							$content           = isset( $data['content'] ) ? $data['content'] : '';
							$dismissable       = isset( $data['dismissable'] ) ? $data['dismissable'] : true;
							$dismiss_permanent = isset( $data['dismiss_permanent'] ) ? $data['dismiss_permanent'] : false;
							$dismiss_transient = isset( $data['dismiss_transient'] ) ? $data['dismiss_transient'] : false;
							echo esc_html('<div class="notice '.$class.' primer-message" id="primer_admin_notice_'.$notice.'">
								'.( $dismissable ? '<a class="primer-message-close notice-dismiss" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'primer-hide-notice' => $notice ) ), 'primer_hide_notices_nonce', '_primer_notice_nonce' ) ) . '">' . __( 'Dismiss', 'primer' ) . '</a>' : '' ).'
								'.$content.'
							</div>');
							if ( $dismissable ) {
								echo '<script type="text/javascript">
									jQuery(document).ready(function($){
										$("#primer_admin_notice_'.$notice.' .notice-dismiss").on("click",function(e){
											e.preventDefault();
											var data = {
												action: "primer_hide_notice",
												"_primer_notice_nonce": "'.wp_create_nonce( 'primer_admin_notice_'.$notice.'_nonce' ).'",
												'.($dismiss_permanent ? '"primer-dismiss": "'.$dismiss_permanent.'",' : '').'
												'.($dismiss_transient ? '"primer-hide-transient": "'.$dismiss_transient.'",' : '').'
												"primer-hide-notice": "'.$notice.'"
											};
											$.post(ajaxurl, data, function(response) {
												if ( response.status === "success" ) {
													$("#primer_admin_notice_'.$notice.'").fadeOut();
												}
											});
											return false;
										});
									});
								</script>';
							}
						} elseif ( $data ) {
							echo $data;
						}
					}
				}
			}
		}
	}

	/**
	 * @since 1.0.0
	 */
	public static function invalid_mydata_page_notice() {
		?>
		<div class="error primer-message">
			<?php /* let's not make this one dismissable for now: <a class="sliced-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'sliced-hide-notice', 'invalid_payment_page' ), 'sliced_hide_notices_nonce', '_sliced_notice_nonce' ) ); ?>"><?php _e( 'Dismiss', 'sliced-invoices' ); ?></a> */ ?>
			<p><?php echo __( '<strong>Fill in required fields!</strong>', 'primer' ); ?></p>
		</div>
		<?php
	}

	/**
	 * @since 1.0.0
	 */
	public static function update_needed_additional_tax_notice() {
		?>
		<div class="error primer-message">
			<a class="primer-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'primer-hide-notice', 'update_needed_additional_tax' ), 'primer_hide_notices_nonce', '_primer_notice_nonce' ) ); ?>"><?php _e( 'Dismiss', 'primer' ); ?></a>
			<p><?php printf( __( 'The plugin "Primer Additional Tax" is out of date and not fully compatible with this version of Primer. Please go to your %sPlugins page%s and update it now.', 'primer' ), '<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">', '</a>' ); ?>
				<br /><?php printf( __( '<strong>You have:</strong> Primer Additional Tax version %s.', 'primer' ), SI_ADD_TAX_VERSION ); ?>
				<br /><?php _e( '<strong>Required:</strong> Primer Additional Tax version 1.3.0 or newer', 'primer' ); ?></p>
		</div>
		<?php
	}

}

// Call the class
Primer_Admin_Notices::init();
