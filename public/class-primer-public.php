<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }

/**
 * The public-facing functionality of the plugin.
 *
 * @link       test.example.com
 * @since      1.0.0
 *
 * @package    Primer
 * @subpackage Primer/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Primer
 * @subpackage Primer/public
 * @author     test_user <testwe@gmail.com>
 */
class Primer_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	protected static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Primer_Loader as all the hooks are defined
		 * in that particular class.
		 *
		 * The Primer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/primer-public.css', array(), $this->version, 'all' );
		wp_register_style('primer-select-woo', plugin_dir_url( __FILE__ ) . 'css/select2.css', array(), PRIMER_VERSION, 'all');
		wp_enqueue_style('primer-select-woo');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Primer_Loader as all the hooks are defined
		 * in that particular class.
		 *
		 * The Primer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
        if(is_cart() || is_checkout()) {
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/primer-public.js', array('jquery'), $this->version, false);
            wp_localize_script( $this->plugin_name, 'primer_validation_ajax_obj', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
            wp_localize_script( $this->plugin_name, 'primer_validation_ajax_obj', array('ajaxurl' => admin_url( 'admin-ajax.php' )));
        }
		wp_enqueue_script('primer-select-woo-js', plugin_dir_url( __FILE__ ) . 'js/selectWoo.full.js', array('jquery'), PRIMER_VERSION, false);

	}

	/**
	 * Set up the template for the receipt.
	 *
	 * @since   1.0.0
	 */
	public function receipt_template( $template ) {
        if (get_post_type() == 'primer_receipt') {
            $primer_license_data = get_option('primer_licenses');
            $can_user_view = false;
            if (is_user_logged_in()) {
                $order_id = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
                $user_id = get_current_user_id();
                if ($order_id != null) {
                    $order_user_id = get_post_meta($order_id, '_customer_user', true);
                    if ($order_user_id != null && $user_id == $order_user_id) {
                        $can_user_view = true;
                    }
                }
            }


            if (current_user_can('administrator') || (isset($_REQUEST['username']) && $_REQUEST['username'] == $primer_license_data['username']) || $can_user_view) {


                if (!post_password_required()) {
                    $template = $this->primer_get_template_part('primer-receipt-display');
                }

                return $template;
            } else {
                wp_redirect(home_url());
                return null;
            }
        } else {
            return $template;
        }
	}

	/**
	 * Retrieve the name of the highest priority template file that exists.
	 *
	 * @since   2.0.0
	 */
	private function primer_locate_template( $template_name ) {
		// No file found yet
		$located = false;

		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );

		// Check child theme first
		if ( file_exists( trailingslashit( get_stylesheet_directory() ) . 'primer/' . $template_name ) ) {
			$located = trailingslashit( get_stylesheet_directory() ) . 'primer/' . $template_name;

			// Check parent theme next
		} elseif ( file_exists( trailingslashit( get_template_directory() ) . 'primer/' . $template_name ) ) {
			$located = trailingslashit( get_template_directory() ) . 'primer/' . $template_name;

		} elseif ( file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/' .  $template_name ) ) {
			$located = plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/' .  $template_name;

		} elseif ( file_exists( plugin_dir_path( __FILE__ ) . 'public/partials/' .  $template_name ) ) {
			$located = plugin_dir_path( __FILE__ ) . 'public/partials/' .  $template_name;

		}

		$located = apply_filters( 'primer_locate_new_templates', $located, $template_name );

		return $located;
	}

	/**
	 * Retrieves a template part
	 *
	 * @since   1.0.0
	 */
	private function primer_get_template_part( $slug ) {
		// Execute code for this part
		do_action( 'get_template_part_' . $slug, $slug );

		$template = $slug . '.php';

		// Allow template parts to be filtered
		$template = apply_filters( 'primer_get_template_part', $template, $slug );

		// Return the part that is found
		return $this->primer_locate_template( $template );
	}

	/**
	 * Set up the template for the receipt.
	 *
	 * @since   1.0.0
	 */
	public function primer_receipt_template( $template ) {
		if ( get_post_type() == 'primer_receipt' ) {
			if ( ! post_password_required() ) {
				$template = $this->primer_get_template_part( 'primer-receipt-display' );
			}
		}
		return $template;
	}

}
