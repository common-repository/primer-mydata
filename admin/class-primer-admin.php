<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       test.example.com
 * @since      1.0.0
 *
 * @package    Primer
 * @subpackage Primer/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Primer
 * @subpackage Primer/admin
 * @author     test_user <testwe@gmail.com>
 */

class Primer_Admin {

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

	 /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/*
	 * Make sure the jQuery UI datepicker is enqueued for CMB2.
	 *
	 * If there are no cmb2 fields of the datepicker type on a page, cmb2 will
	 * not enqueue the datepicker scripts.  Since we are now using cmb2 field
	 * type 'text' for dates and initializing datepickers on our own, we need to
	 * manually add them as cmb2 dependencies.
	 *
	 * @since   3.8.0
	 */
	public function cmb2_enqueue_datepicker( $dependencies ) {
		$dependencies['jquery-ui-core'] = 'jquery-ui-core';
		$dependencies['jquery-ui-datepicker'] = 'jquery-ui-datepicker';
		$dependencies['jquery-ui-datetimepicker'] = 'jquery-ui-datetimepicker';
		return $dependencies;
	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/primer-admin.css', array(), $this->version, 'all' );

		//jQuery UI style
		wp_register_style('primer-jquery-ui', PRIMER_URL . '/public/css/jquery-ui.min.css', array(), PRIMER_VERSION);
		wp_enqueue_style('primer-jquery-ui');
		wp_enqueue_script('jquery-ui-datepicker');
		//Bootstrap select
		$screen = get_current_screen();
		if ( $screen->id == "toplevel_page_wp_ajax_list_order" || $screen->id == "primer-receipts_page_primer_receipts" || $screen->id == "admin_page_primer_receipts_logs" || $screen->id == "admin_page_primer_receipts_logs_automation" ) {
            wp_enqueue_script('primer-bootstrap-js', PRIMER_URL . '/public/js/bootstrap.bundle.min.js', array('jquery'), PRIMER_VERSION, true);
            wp_register_style('primer-bootstrap-css', PRIMER_URL . '/public/css/bootstrap.min.css', array(), PRIMER_VERSION);
            wp_enqueue_script('primer-bootstrap-select-js', PRIMER_URL . '/public/js/bootstrap-select.min.js', array('jquery'), PRIMER_VERSION, true);
			wp_enqueue_style('primer-bootstrap-css');
			wp_register_style('primer-bootstrap-select-css', PRIMER_URL . '/public/css/bootstrap-select.min.css', array(), PRIMER_VERSION);
			wp_enqueue_style('primer-bootstrap-select-css');
			wp_register_style('primer-select-woo', PRIMER_URL . '/public/css/select2.css', array(), PRIMER_VERSION);
			wp_enqueue_style('primer-select-woo');
			wp_enqueue_script('primer-select-woo-js', PRIMER_URL . '/public/js/selectWoo.full.js', array('jquery'), PRIMER_VERSION, false);
            wp_enqueue_script('cmb2-conditional', PRIMER_URL . '/includes/vendor/conditional/cmb2-conditionals.js');
		}

		if ( $screen->id == "primer-receipts_page_primer_settings" ) {
			wp_enqueue_script('plupload');
			wp_register_script('upload-js', PRIMER_URL . '/admin/js/upload.js', array('jquery', 'plupload'), PRIMER_VERSION, true);
			$profile_data = array(
				'url' => admin_url('admin-ajax.php'),
				'upload_nonce' => wp_create_nonce('upload_nonce'),
				'verify_file_type' => esc_html__('Valid file formats', 'primer'),
				'site_url' => site_url(),
			);
			wp_localize_script('upload-js', 'ajax_object', $profile_data);
			wp_enqueue_script('upload-js');
            wp_enqueue_script('cmb2-conditional', PRIMER_URL . '/includes/vendor/conditional/cmb2-conditionals.js');
		}

        if ( $screen->id == "primer-receipts_page_primer_export" || $screen->id == "primer-receipts_page_primer_licenses") {
            wp_enqueue_script('cmb2-conditional', PRIMER_URL . '/includes/vendor/conditional/cmb2-conditionals.js');
        }

	}

	/**
	 * Register the JavaScript for the admin area.
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
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/primer-admin.js', array('jquery'), $this->version, false);
		$primer_nonce = wp_create_nonce( 'primer_ajax_nonce' );
		wp_localize_script($this->plugin_name, 'primer_ajax_obj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => $primer_nonce
		));
	}

	 /**
	 * Creates a new taxonomy for a custom post type.
	 *
	 * @since 	1.0.0
	 */
	public function new_taxonomy_receipt_status() {

		$plural 	= __( 'Statuses');
		$single 	= __( 'Status');
		$tax_name 	= 'receipt_status';

		$opts['hierarchical']							= TRUE;
		$opts['public']									= TRUE;
		$opts['query_var']								= $tax_name;
		$opts['show_admin_column'] 						= TRUE;
		$opts['show_in_nav_menus']						= FALSE;
		$opts['show_tag_cloud'] 						= FALSE;
		$opts['show_ui']								= TRUE;
		$opts['show_in_menu']							= TRUE;
		$opts['sort'] 									= '';

		$opts['capabilities']['assign_terms'] 			= 'edit_posts';
		$opts['capabilities']['delete_terms'] 			= 'manage_categories';
		$opts['capabilities']['edit_terms'] 			= 'manage_categories';
		$opts['capabilities']['manage_terms'] 			= 'manage_categories';

		/* translators: %s is a placeholder for the localized word "Status" (singular) */
		$opts['labels']['add_new_item']					= sprintf( __( 'Add New %s'), $single );
		/* translators: %s is a placeholder for the localized word "Statuses" (plural) */
		$opts['labels']['add_or_remove_items'] 			= sprintf( __( 'Add or remove %s'), $plural );
		$opts['labels']['all_items']					= $plural;
		/* translators: %s is a placeholder for the localized word "Statuses" (plural) */
		$opts['labels']['choose_from_most_used'] 		= sprintf( __( 'Choose from most used %s'), $plural );
		/* translators: %s is a placeholder for the localized word "Status" (singular) */
		$opts['labels']['edit_item']					= sprintf( __( 'Edit %s'), $single );
		$opts['labels']['menu_name']					= $plural;
		$opts['labels']['name']							= $plural;
		/* translators: %s is a placeholder for the localized word "Status" (singular) */
		$opts['labels']['new_item_name'] 				= sprintf( __( 'New %s Name'), $single );
		/* translators: %s is a placeholder for the localized word "Statuses" (plural) */
		$opts['labels']['not_found']					= sprintf( __( 'No %s Found'), $plural );
		/* translators: %s is a placeholder for the localized word "Status" (singular) */
		$opts['labels']['parent_item'] 					= sprintf( __( 'Parent %s'), $single );
		/* translators: %s is a placeholder for the localized word "Status" (singular) */
		$opts['labels']['parent_item_colon']			= sprintf( __( 'Parent %s:'), $single );
		/* translators: %s is a placeholder for the localized word "Statuses" (plural) */
		$opts['labels']['popular_items'] 				= sprintf( __( 'Popular %s'), $plural );
		/* translators: %s is a placeholder for the localized word "Statuses" (plural) */
		$opts['labels']['search_items']					= sprintf( __( 'Search %s'), $plural );
		/* translators: %s is a placeholder for the localized word "Statuses" (plural) */
		$opts['labels']['separate_items_with_commas'] 	= sprintf( __( 'Separate %s with commas'), $plural );
		$opts['labels']['singular_name']				= $single;
		/* translators: %s is a placeholder for the localized word "Status" (singular) */
		$opts['labels']['update_item'] 					= sprintf( __( 'Update %s'), $single );
		/* translators: %s is a placeholder for the localized word "Status" (singular) */
		$opts['labels']['view_item']					= sprintf( __( 'View %s'), $single );

		$opts['rewrite']['slug']						= __( strtolower( $tax_name ));

		$opts = apply_filters( 'primer_receipt_status_params', $opts );

		register_taxonomy( $tax_name, 'primer_receipt', $opts );
	}


	public function register_new_terms() {
		$taxonomy = 'receipt_status';
		$terms = array (
			'english_receipt' => array (
				'name'          => 'English Receipt',
				'slug'          => 'english_receipt',
				'description'   => '',
			),
			'greek_receipt' => array (
				'name'          => 'Greek Receipt',
				'slug'          => 'greek_receipt',
				'description'   => '',
			),
			'english_invoice' => array (
				'name'          => 'English Invoice',
				'slug'          => 'english_invoice',
				'description'   => '',
			),
			'greek_invoice' => array (
				'name'          => 'Greek Invoice',
				'slug'          => 'greek_invoice',
				'description'   => '',
			),
		);

		foreach ( $terms as $term_key=>$term) {
			wp_insert_term(
				$term['name'],
				$taxonomy,
				array(
					'description'   => $term['description'],
					'slug'          => $term['slug'],
				)
			);
			unset( $term );
		}

	}

	/**
	 * Creates a new custom post type.
	 *
	 * @since 	1.0.0
	 */
	public function new_cpt_receipt() {

		$translate = get_option( 'primer_translate' );

		$cap_type = 'post';
		$plural = primer_get_receipt_label_plural();
		$single = primer_get_receipt_label();
		$cpt_name = 'primer_receipt';

		$opts['can_export']             = TRUE;
		$opts['capability_type']        = $cap_type;
		$opts['description']            = '';
		$opts['exclude_from_search']    = TRUE;
		$opts['has_archive']            = FALSE;
		$opts['hierarchical']           = TRUE;
		$opts['map_meta_cap']           = TRUE;
		$opts['menu_icon']              = 'dashicons-text-page';
		$opts['public']                 = TRUE;
		$opts['publicly_querable']      = TRUE;
		$opts['query_var']              = TRUE;
		$opts['register_meta_box_cb']   = '';
		$opts['rewrite']                = FALSE;
		$opts['show_in_admin_bar']      = TRUE;
		$opts['show_in_menu']           = FALSE;
		$opts['show_in_nav_menu']       = FALSE;
		$opts['show_ui']                = TRUE;
		$opts['supports']			    = array( 'title', 'comments' );
		$opts['taxonomies']				= array( 'receipt_status' );

		$opts['capabilities']['delete_others_posts']	= "delete_others_{$cap_type}s";
		$opts['capabilities']['delete_post']			= "delete_{$cap_type}";
		$opts['capabilities']['delete_posts']			= "delete_{$cap_type}s";
		$opts['capabilities']['delete_private_posts']	= "delete_private_{$cap_type}s";
		$opts['capabilities']['delete_published_posts']	= "delete_published_{$cap_type}s";
		$opts['capabilities']['edit_others_posts']		= "edit_others_{$cap_type}s";
		$opts['capabilities']['edit_post']				= "edit_{$cap_type}";
		$opts['capabilities']['edit_posts']				= "edit_{$cap_type}s";
		$opts['capabilities']['edit_private_posts']		= "edit_private_{$cap_type}s";
		$opts['capabilities']['edit_published_posts']	= "edit_published_{$cap_type}s";
		$opts['capabilities']['publish_posts']			= "publish_{$cap_type}s";
		$opts['capabilities']['read_post']				= "read_{$cap_type}";
		$opts['capabilities']['read_private_posts']		= "read_private_{$cap_type}s";

		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['add_new']						= sprintf( __( 'Add New %s'), $single );
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['add_new_item']					= sprintf( __( 'Add New %s'), $single );
		$opts['labels']['all_items']					= $plural;
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['edit_item']					= sprintf( __( 'Edit %s'), $single );
		$opts['labels']['menu_name']					= $plural;
		$opts['labels']['name']							= $plural;
		$opts['labels']['name_admin_bar']				= $single;
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['new_item']						= sprintf( __( 'New %s'), $single );
		/* translators: %s is a placeholder for the localized word "Receipts" (plural) */
		$opts['labels']['not_found']					= sprintf( __( 'No %s Found'), $plural );
		/* translators: %s is a placeholder for the localized word "Receipts" (plural) */
		$opts['labels']['not_found_in_trash']			= sprintf( __( 'No %s Found in Trash'), $plural );
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['parent_item_colon']			= sprintf( __( 'Parent %s:'), $single );
		/* translators: %s is a placeholder for the localized word "Receipts" (plural) */
		$opts['labels']['search_items']					= sprintf( __( 'Search %s'), $plural );
		$opts['labels']['singular_name']				= $single;
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['view_item']					= sprintf( __( 'View %s'), $single );
        $opts['rewrite'] = [];
		$opts['rewrite']['slug']						= FALSE;
		$opts['rewrite']['with_front']					= FALSE;
		$opts['rewrite']['feeds']						= FALSE;
		$opts['rewrite']['pages']						= FALSE;

		$opts = apply_filters('primer_receipt_params', $opts);

		register_post_type('primer_receipt', $opts );
	}

	/**
	 * Creates a new custom post type for receipt report.
	 *
	 * @since 	1.0.0
	 */
	public function new_cpt_receipt_log() {

		$translate = get_option( 'primer_translate' );

		$cap_type = 'post';
		$plural = primer_get_receipt_log_label_plural();
		$single = primer_get_receipt_log_label();
		$cpt_name = 'primer_receipt_log';

		$opts['can_export']             = TRUE;
		$opts['capability_type']        = $cap_type;
		$opts['description']            = '';
		$opts['exclude_from_search']    = TRUE;
		$opts['has_archive']            = FALSE;
		$opts['hierarchical']           = TRUE;
		$opts['map_meta_cap']           = TRUE;
		$opts['menu_icon']              = 'dashicons-text-page';
		$opts['public']                 = TRUE;
		$opts['publicly_querable']      = TRUE;
		$opts['query_var']              = TRUE;
		$opts['register_meta_box_cb']   = '';
		$opts['rewrite']                = FALSE;
		$opts['show_in_admin_bar']      = TRUE;
		$opts['show_in_menu']           = FALSE;
		$opts['show_in_nav_menu']       = FALSE;
		$opts['show_ui']                = TRUE;
		$opts['supports']			    = array( 'title', 'comments' );
		$opts['taxonomies']				= array( 'receipt_status' );

		$opts['capabilities']['delete_others_posts']	= "delete_others_{$cap_type}s";
		$opts['capabilities']['delete_post']			= "delete_{$cap_type}";
		$opts['capabilities']['delete_posts']			= "delete_{$cap_type}s";
		$opts['capabilities']['delete_private_posts']	= "delete_private_{$cap_type}s";
		$opts['capabilities']['delete_published_posts']	= "delete_published_{$cap_type}s";
		$opts['capabilities']['edit_others_posts']		= "edit_others_{$cap_type}s";
		$opts['capabilities']['edit_post']				= "edit_{$cap_type}";
		$opts['capabilities']['edit_posts']				= "edit_{$cap_type}s";
		$opts['capabilities']['edit_private_posts']		= "edit_private_{$cap_type}s";
		$opts['capabilities']['edit_published_posts']	= "edit_published_{$cap_type}s";
		$opts['capabilities']['publish_posts']			= "publish_{$cap_type}s";
		$opts['capabilities']['read_post']				= "read_{$cap_type}";
		$opts['capabilities']['read_private_posts']		= "read_private_{$cap_type}s";

		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['add_new']						= sprintf( __( 'Add New %s'), $single );
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['add_new_item']					= sprintf( __( 'Add New %s'), $single );
		$opts['labels']['all_items']					= $plural;
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['edit_item']					= sprintf( __( 'Edit %s'), $single );
		$opts['labels']['menu_name']					= $plural;
		$opts['labels']['name']							= $plural;
		$opts['labels']['name_admin_bar']				= $single;
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['new_item']						= sprintf( __( 'New %s'), $single );
		/* translators: %s is a placeholder for the localized word "Receipts" (plural) */
		$opts['labels']['not_found']					= sprintf( __( 'No %s Found'), $plural );
		/* translators: %s is a placeholder for the localized word "Receipts" (plural) */
		$opts['labels']['not_found_in_trash']			= sprintf( __( 'No %s Found in Trash'), $plural );
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['parent_item_colon']			= sprintf( __( 'Parent %s:'), $single );
		/* translators: %s is a placeholder for the localized word "Receipts" (plural) */
		$opts['labels']['search_items']					= sprintf( __( 'Search %s'), $plural );
		$opts['labels']['singular_name']				= $single;
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['view_item']					= sprintf( __( 'View %s'), $single );
        $opts['rewrite'] = [];
		$opts['rewrite']['slug']						= FALSE;
		$opts['rewrite']['with_front']					= FALSE;
		$opts['rewrite']['feeds']						= FALSE;
		$opts['rewrite']['pages']						= FALSE;

		$opts = apply_filters('primer_receipt_log_params', $opts);

		register_post_type('primer_receipt_log', $opts );
	}

	/**
	 * Creates a new custom post type for receipt report automation.
	 *
	 * @since 	1.0.0
	 */
	public function new_cpt_receipt_log_automation() {

		$translate = get_option( 'primer_translate' );

		$cap_type = 'post';
		$plural = primer_get_receipt_log_automation_label_plural();
		$single = primer_get_receipt_log_automation_label();
		$cpt_name = 'pr_log_automation';

		$opts['can_export']             = TRUE;
		$opts['capability_type']        = $cap_type;
		$opts['description']            = '';
		$opts['exclude_from_search']    = TRUE;
		$opts['has_archive']            = FALSE;
		$opts['hierarchical']           = TRUE;
		$opts['map_meta_cap']           = TRUE;
		$opts['menu_icon']              = 'dashicons-text-page';
		$opts['public']                 = TRUE;
		$opts['publicly_querable']      = TRUE;
		$opts['query_var']              = TRUE;
		$opts['register_meta_box_cb']   = '';
		$opts['rewrite']                = FALSE;
		$opts['show_in_admin_bar']      = TRUE;
		$opts['show_in_menu']           = FALSE;
		$opts['show_in_nav_menu']       = FALSE;
		$opts['show_ui']                = TRUE;
		$opts['supports']			    = array( 'title', 'comments' );
		$opts['taxonomies']				= array( 'receipt_status' );

		$opts['capabilities']['delete_others_posts']	= "delete_others_{$cap_type}s";
		$opts['capabilities']['delete_post']			= "delete_{$cap_type}";
		$opts['capabilities']['delete_posts']			= "delete_{$cap_type}s";
		$opts['capabilities']['delete_private_posts']	= "delete_private_{$cap_type}s";
		$opts['capabilities']['delete_published_posts']	= "delete_published_{$cap_type}s";
		$opts['capabilities']['edit_others_posts']		= "edit_others_{$cap_type}s";
		$opts['capabilities']['edit_post']				= "edit_{$cap_type}";
		$opts['capabilities']['edit_posts']				= "edit_{$cap_type}s";
		$opts['capabilities']['edit_private_posts']		= "edit_private_{$cap_type}s";
		$opts['capabilities']['edit_published_posts']	= "edit_published_{$cap_type}s";
		$opts['capabilities']['publish_posts']			= "publish_{$cap_type}s";
		$opts['capabilities']['read_post']				= "read_{$cap_type}";
		$opts['capabilities']['read_private_posts']		= "read_private_{$cap_type}s";

		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['add_new']						= sprintf( __( 'Add New %s'), $single );
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['add_new_item']					= sprintf( __( 'Add New %s'), $single );
		$opts['labels']['all_items']					= $plural;
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['edit_item']					= sprintf( __( 'Edit %s'), $single );
		$opts['labels']['menu_name']					= $plural;
		$opts['labels']['name']							= $plural;
		$opts['labels']['name_admin_bar']				= $single;
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['new_item']						= sprintf( __( 'New %s'), $single );
		/* translators: %s is a placeholder for the localized word "Receipts" (plural) */
		$opts['labels']['not_found']					= sprintf( __( 'No %s Found'), $plural );
		/* translators: %s is a placeholder for the localized word "Receipts" (plural) */
		$opts['labels']['not_found_in_trash']			= sprintf( __( 'No %s Found in Trash'), $plural );
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['parent_item_colon']			= sprintf( __( 'Parent %s:'), $single );
		/* translators: %s is a placeholder for the localized word "Receipts" (plural) */
		$opts['labels']['search_items']					= sprintf( __( 'Search %s'), $plural );
		$opts['labels']['singular_name']				= $single;
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['view_item']					= sprintf( __( 'View %s'), $single );
        $opts['rewrite'] = [];
		$opts['rewrite']['slug']						= FALSE;
		$opts['rewrite']['with_front']					= FALSE;
		$opts['rewrite']['feeds']						= FALSE;
		$opts['rewrite']['pages']						= FALSE;

		$opts = apply_filters('pr_log_automation_params', $opts);

		register_post_type('pr_log_automation', $opts );
	}

	public function primer_create_tax_rates() {

		$tax_option = get_option('woocommerce_calc_taxes');

		if ($tax_option != 'yes') {
			update_option('woocommerce_calc_taxes', 'yes');
		}

		$tax_rate = array('tax_rate_country' => 'GR', 'tax_rate_state' => '*', 'tax_rate' => '24.0000', 'tax_rate_name' => 'Standard', 'tax_rate_priority' => '1', 'tax_rate_compound' => '0', 'tax_rate_shipping' => '1', 'tax_rate_order' => '1', 'tax_rate_class' => '');

		$tax_classes   = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
		if ( ! in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
			array_unshift( $tax_classes, '' );
		}

		$tax_check = 'true';

		foreach ( $tax_classes as $tax_class ) { // For each tax class, get all rates.
			if (empty($tax_class)) {
				$taxes         = WC_Tax::get_rates_for_tax_class( $tax_class );
				$count_taxes = count((array)$taxes);
				if ($count_taxes == 0) {
					$tax_check = 'false';
				}
			}
		}
		if ($tax_check == 'false') {
			WC_Tax::_insert_tax_rate($tax_rate);
		}
	}

	/**
	 * Handle a custom 'customvar' query var to get orders with the 'customvar' meta.
	 * @param array $query - Args for WP_Query.
	 * @param array $query_vars - Query vars from WC_Order_Query.
	 * @return array modified $query
	 */
	public function handle_custom_query_var( $query, $query_vars, $that ) {

		if ( ! empty($query_vars['meta_query']['1']['key']) ) {
			$query['meta_query'][] = array(
				'key' => 'receipt_status',
				'value' => esc_attr($query_vars['receipt_status'] ),
			);
		}

		return $query;
	}

	public function primer_set_shipping_country() {
		return 'GR';
	}

	/**
	 * Create Invoice/Receipt billing fields in admin
	 */
	public function primer_add_woocommerce_admin_billing_fields($billing_fields) {
		// Loop through the (complete) keys/labels array
		foreach ( primer_get_keys_labels() as $key => $label ) {
			$billing_fields[$key]['label'] = $label;
		}
		return $billing_fields;
	}

	/**
	 * Create Invoice/Receipt billing fields in checkout.
	 */
	public function primer_checkout_field_process() {
		if ( sanitize_text_field($_POST['billing_invoice_type']) == 'primer_invoice' ) {
			// Loop through the (partial) keys/labels array
			foreach ( primer_get_keys_labels(false) as $key => $label ) {
				// Check if set, if not avoid checkout displaying an error notice.
				if ( ! $_POST['billing_'.$key] && $key != 'phone_mobile' &&  sanitize_text_field($_POST['billing_country']) == 'GR') {
					wc_add_notice( sprintf( __('%s is a required field.', 'primer' ), $label ), 'error' );
				}
                if ( ! $_POST['billing_'.$key] && $key != 'phone_mobile' &&  ($key != 'doy' && sanitize_text_field($_POST['billing_country']) != 'GR')) {
                    wc_add_notice( sprintf( __('%s is a required field.', 'primer' ), $label ), 'error' );
                }
			}
		}

		/*if ( email_exists( sanitize_email($_POST['billing_email']) ) && ! is_user_logged_in() ) {
			wc_add_notice( __('User already exists with the specified email, please log in or choose another email', 'primer'), 'error' );
		} */
	}


    function fix_wpml_error(){
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php') ) {
            // Plugin is active

          //  wp_register_script('sitepress-post-edit-tags', ICL_PLUGIN_URL . '/res/js/post-edit-terms.js', array('jquery', 'cmb2-conditionals'));
          //  wp_enqueue_script('sitepress-post-edit-tags');
        }

}


    function add_my_account_my_orders_custom_action( $actions, $order ) {
        $action_slug = 'show_invoice';
        $orderid = $order->get_id();
        $issued = get_post_meta($orderid,'receipt_status',true);
        $trasmission_failure_invoice = get_post_meta($orderid,'order_datetime_failed',true);
        if($issued == 'issued' && empty($trasmission_failure_invoice)) {
            $actions[$action_slug] = array(
                'url' => home_url('primer_receipt/receipt-for-order-' . $order->get_id() . '/?receipt=view'),
                'name' => 'INVOICE',
            );
        }elseif($issued == 'issued' && !empty($trasmission_failure_invoice)){
            $actions[$action_slug] = array(
                'url' => home_url('primer_receipt/receipt-for-order-' . $order->get_id() . '-failed/?receipt=view'),
                'name' => 'INVOICE',
            );
       }
        return $actions;
    }


	/**
	 * Add Invoice Type column
	 * @param $column
	 */
	public function primer_icon_to_order_notes_column( $column ) {
		global $post, $the_order;

		// Added WC 3.2+  compatibility
		if ( $column == 'order_notes' || $column == 'order_number' ) {
			// Added WC 3+  compatibility
			$order_id = method_exists( $the_order, 'get_id' ) ? $the_order->get_id() : $the_order->id;

			$primer_type = get_post_meta( $order_id, '_billing_invoice_type', true );
			if ( $primer_type == 'primer_invoice' ) {
				$style     = $column == 'order_notes' ? 'style="margin-top:5px;" ' : 'style="margin-left:8px;padding:5px;"';
				echo '<span class="dashicons dashicons-format-aside" '.$style.'title="'. __('Invoice Type', 'primer').'"></span>';
			}
		}
	}


	public function primer_add_woocommerce_found_customer_details($customer_data, $user_id, $type_to_load) {
		if ($type_to_load == 'billing') {
			// Loop through the (partial) keys/labels array
			foreach ( primer_get_keys_labels(false) as $key => $label ) {
				$customer_data[$type_to_load.'_'.$key] = get_user_meta($user_id, $type_to_load.'_'.$key, true);
			}
		}
		return $customer_data;
	}


	public function primer_add_woocommerce_billing_fields($billing_fields) {
		$labels = primer_get_keys_labels();

		$billing_fields['billing_invoice_type'] = array(
			'priority'  => '1',
			'type'      => 'radio',
			'required'  => true,
			'class'     => array('form-row-wide', 'form-row-flex'),
			'options'   => array(
				'receipt' => __('Receipt', 'primer'),
                'primer_invoice' => __('Invoice', 'primer')
			),
			'default' => 'receipt',
		);


		$billing_fields['billing_company'] = array(
			'priority' => '2',
			'class' => array('form-row-wide', 'invoice_type-hide', 'validate-required'),
			'label'         => $labels['company'],
			'placeholder'   => _x( $labels['company'], 'placeholder' ),
			'required' => false,
		);

		$billing_fields['billing_vat'] = array(
			'priority'      => '3',
			'type'          => 'text',
			'label'         => $labels['vat'],
			'placeholder'   => _x( $labels['vat'], 'placeholder' ),
			'class'         => array('form-row-first', 'invoice_type-hide', 'validate-required'),
			'maxlength'     => '20',
			'required'      => false,
            'clear'     => true
		);

		$billing_fields['billing_doy'] = array(
			'priority'      => '4',
			'type'          => 'select',
			'options'       => primer_return_doy_args(),
			'label'         => $labels['doy'],
			'placeholder'   => _x( $labels['doy'], 'placeholder' ),
			'class'         => array('form-row-last', 'invoice_type-hide', 'validate-required'),
			'required'      => false
		);

		$billing_fields['billing_store'] = array(
			'priority'    => '5',
			'type' => 'text',
			'label' => $labels['store'],
			'placeholder' => _x( $labels['store'], 'placeholder' ),
			'class' => array('form-row-wide', 'invoice_type-hide', 'validate-required'),
			'required' => false,
			'clear' => true
		);

		return $billing_fields;

	}


	public function primer_add_woocommerce_customer_meta_fields($billing_fields) {
		if (isset($billing_fields['billing']['fields'])) {

			// Loop through the (partial) keys/labels array
			foreach ( primer_get_keys_labels(false) as $key => $label ) {
				$billing_fields['billing']['fields']['billing_'.$key] = array(
					'label' => $label,
					'description' => ''
				);
			}
		}
		return $billing_fields;
	}

	public function primer_add_woocommerce_order_fields($address, $order) {
		// Added WC 3+  compatibility
		$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
		// Loop through (partial) the keys/labels array (not the first entry)
		foreach( primer_get_keys_labels(false) as $key => $label ){
			$address['billing_'.$key] = get_post_meta( $order_id, '_billing_'.$key, true );
		}
		return $address;
	}

	public function primer_editable_order_meta_general( $order ) { ?>
		<br class="clear" />
		<h4>Invoice Type <a href="#" class="edit_address">Edit</a></h4>
		<?php
        $invoice_type_primer = get_post_meta( $order->get_id(), '_billing_invoice_type', true );
        if($invoice_type_primer == 'primer_invoice'){
            $get_invoice_type = 'primer_invoice';
        }else{
            $get_invoice_type = get_post_meta( $order->get_id(), '_billing_invoice_type', true );
        }
		?>

		<div class="edit_address">
			<?php
            if(function_exists('woocommerce_wp_radio')) {
                woocommerce_wp_radio(array(
                    'id' => '_billing_invoice_type',
                    'label' => 'Invoice Type',
                    'options' => array(
                        'receipt' => 'Receipt',
                        'primer_invoice' => 'Invoice'
                    ),
                    'style' => 'width:16px',
                    'wrapper_class' => 'form-field-wide'
                ));
            }
			?>
		</div>
	<?php }

	public function primer_save_general_details( $order_id ) {
		update_post_meta($order_id, '_billing_invoice_type', wc_clean( $_POST['_billing_invoice_type'] ));
	}

	public function primer_add_woocommerce_formatted_address_replacements( $replace, $args ) {
		// The (partial) keys/labels array (not the first entry)
		$data = primer_get_keys_labels(false);

		$replace['{billing_vat}'] = !empty($args['billing_vat']) ? $data['vat'] .': '. $args['billing_vat'] : '';
		$replace['{billing_store}'] = !empty($args['billing_store']) ? $data['store'] .': '. $args['billing_store'] : '';

		return $replace;
	}

	public function primer_checkout_save_user_meta( $order_id ) {
		$order = wc_get_order( $order_id );
		$user_id = $order->get_user_id();
		if ( $order->get_billing_first_name() ) {
			update_user_meta( $user_id, 'first_name', $order->get_billing_first_name() );
		}
		if ( $order->get_billing_last_name() ) {
			update_user_meta( $user_id, 'last_name', $order->get_billing_last_name() );
		}
		if ( $order->get_billing_email() ) {
			update_user_meta( $user_id, 'user_email', $order->get_billing_email() );
		}
		update_post_meta( $order_id, '_customer_user', $user_id );
		update_user_meta( $user_id, 'billing_address_1', $order->get_billing_address_1() );
		update_user_meta( $user_id, 'billing_address_2', $order->get_billing_address_2() );
		update_user_meta( $user_id, 'billing_city', $order->get_billing_city() );
		update_user_meta( $user_id, 'billing_company', $order->get_billing_company() );
		update_user_meta( $user_id, 'billing_country', $order->get_billing_country() );
		update_user_meta( $user_id, 'billing_first_name', $order->get_billing_first_name() );
		update_user_meta( $user_id, 'billing_last_name', $order->get_billing_last_name() );
		update_user_meta( $user_id, 'billing_email', $order->get_billing_email() );
		update_user_meta( $user_id, 'billing_phone', $order->get_billing_phone() );
		update_user_meta( $user_id, 'billing_postcode', $order->get_billing_postcode() );

		$billing_vat = get_post_meta($order->get_id(), '_billing_vat', true);
		$billing_store = get_post_meta($order->get_id(), '_billing_store', true);
		$billing_doy = get_post_meta($order->get_id(), '_billing_doy', true);

		if (!empty($billing_vat)) {
			update_user_meta( $user_id, 'billing_vat', $billing_vat );
		}
		if (!empty($billing_store)) {
			update_user_meta( $user_id, 'billing_store', $billing_store );
		}
		if (!empty($billing_doy)) {
			update_user_meta( $user_id, 'billing_doy', $billing_doy );
		}
		$doy = get_post_meta($order_id, '_billing_doy', true);
		$doy_value = primer_return_doy_args()[$doy];
		if (!empty($doy_value)) {
			update_user_meta( $user_id, 'billing_doy_name', $doy_value );
		}
	}

    public function update_receipt_status_on_order_creation ( $order_id ) {
        update_post_meta($order_id,'receipt_status', 'not_issued');
    }
	public function primer_checkout_create_user( $order_id ) {
		$order = wc_get_order( $order_id );
		//get the user email from the order
		$order_email = $order->get_billing_email();
		// check if there are any users with the billing email as user or email
		$email = email_exists( $order_email );
		$user = username_exists( $order_email );

		if( $user == false && $email == false ) {
			// random password with 12 chars
			$random_password = wp_generate_password();
			// create new user with email as username & newly created pw
			$user_id = wp_create_user( $order_email, $random_password, $order_email );
			if ( is_wp_error( $user_id ) ) {
				echo $user_id->get_error_message();
			} else {
				$billing_vat = get_post_meta($order->get_id(), '_billing_vat', true);
				$billing_store = get_post_meta($order->get_id(), '_billing_store', true);
				$billing_doy = get_post_meta($order->get_id(), '_billing_doy', true);

				$u = new WP_User($user_id);
				$u->remove_role('subscriber');
				$u->add_role('customer');

				update_post_meta( $order_id, '_customer_user', $user_id );
				update_user_meta( $user_id, 'first_name', $order->get_billing_first_name() );
				update_user_meta( $user_id, 'last_name', $order->get_billing_last_name() );
				update_user_meta( $user_id, 'billing_address_1', $order->get_billing_address_1() );
				update_user_meta( $user_id, 'billing_address_2', $order->get_billing_address_2() );
				update_user_meta( $user_id, 'billing_city', $order->get_billing_city() );
				update_user_meta( $user_id, 'billing_company', $order->get_billing_company() );
				update_user_meta( $user_id, 'billing_country', $order->get_billing_country() );
				update_user_meta( $user_id, 'billing_email', $order->get_billing_email() );
				update_user_meta( $user_id, 'billing_first_name', $order->get_billing_first_name() );
				update_user_meta( $user_id, 'billing_last_name', $order->get_billing_last_name() );
				update_user_meta( $user_id, 'billing_phone', $order->get_billing_phone() );
				update_user_meta( $user_id, 'billing_postcode', $order->get_billing_postcode() );

				if (!empty($billing_vat)) {
					update_user_meta( $user_id, 'billing_vat', $billing_vat );
				}
				if (!empty($billing_store)) {
					update_user_meta( $user_id, 'billing_store', $billing_store );
				}
				if (!empty($billing_doy)) {
					update_user_meta( $user_id, 'billing_doy', $billing_doy );
				}
				$doy = get_post_meta($order_id, '_billing_doy', true);
				$doy_value = primer_return_doy_args()[$doy];
				if (!empty($doy_value)) {
					update_user_meta( $user_id, 'billing_doy_name', $doy_value );
				}
			}
		}

	}

	public function primer_admin_order_success_message() { ?>
		<div class="notice notice-success is-dismissible">
			<p><strong><?php _e('Editing of this order has been disabled because it has been converted to invoice', 'primer'); ?></strong></p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">Dismiss this notice.</span>
			</button>
		</div>
	<?php }

	public function primer_admin_order_error_message() { ?>
		<div class="notice notice-error">
			<p><strong><?php _e('Editing of this order has been disabled because data have been send to AADE, but there was an error in the conversion. Please check conversion log and fix conversion error to continue', 'primer'); ?></strong></p>
		</div>
	<?php }

    public function prevent_order_deletion_callback($delete, $post) {
        // Check if the post being deleted is an order
        if ($post->post_type === 'shop_order') {
            // Get the order ID
            $order_id = $post->ID;
            // Check for the specific postmeta value
            $specific_value = get_post_meta($order_id, 'receipt_status', true);
            if ($specific_value == 'issued') {
                // Prevent deletion by returning false
                return false;
            }
        }
        return $delete;
    }

	public function primer_order_permissions() {
		global $pagenow;

		if ( 'post.php' != $pagenow || ! isset( $_GET['post'] ) ) {
			return;
		}

		if (isset($_GET['post']) && isset($_GET['action'])) {
			if ($_GET['action'] == 'edit') {
				$post_id = sanitize_text_field($_GET['post']);
				$post_type = get_post_type( $post_id );
				if ($post_type !== 'shop_order') {
					return;
				} else {
					$receipt_status = get_post_meta($post_id, 'receipt_status', true);
					$html_receipt_json = get_post_meta($post_id, 'html_body_post_fields', true);
					$send_receipt_json = get_post_meta($post_id, 'order_id_from_receipt', true);
					$invoice_response = get_post_meta($post_id, 'invoice_response', true);
                    $transmission_failed = get_post_meta($post_id, 'transmission_failure_check', true);
                    $cancelled_order = get_post_meta($post_id, 'cancelled', true);
                    $order = wc_get_order( $post_id );
                    $order_status = $order->get_status();
					if (!empty($receipt_status) && !empty($send_receipt_json) && $transmission_failed != 1) {
						if ($receipt_status == 'issued') {
							$this->primer_admin_order_success_message();
						}
						if ($receipt_status == 'not_issued') {
							$this->primer_admin_order_error_message();
						}
						?>
						<style>
                            #postbox-container-2 #order_data {
                                pointer-events: none;
                            }
                            #delete-action{
                                pointer-events: none;
                            }

                            #postbox-container-2 #order_data a {
                                cursor: not-allowed;
                                pointer-events: none;
                            }
                            #postbox-container-2 #order_data a:hover {
                                pointer-events: none;
                            }
                            #postbox-container-2 #order_data select {
                                pointer-events: none;
                            }
                            #postbox-container-2 .wc-order-items-editable {
                                opacity: .5;
                                pointer-events: none;
                            }

                            #postbox-container-2 #postcustom {
                                opacity: .5;
                                pointer-events: none;
                            }
                            #postbox-container-2 .wc-order-status{
                                opacity: 1;
                                pointer-events: auto;
                            }

						</style>
					<?php
                    if($cancelled_order == 'yes'){
                        ?>
                            <style>
                                #postbox-container-2 .wc-order-bulk-actions{
                                    opacity: 1;
                                }
                                #postbox-container-2 .refund-items{
                                    opacity: 1;
                                    pointer-events: auto;
                                }
                                #postbox-container-2 .order_data_column{
                                    opacity: 1;
                                }
                            </style>
                        <?php
                    }
                        if($order_status != 'wc-pending'){
                            ?>
                            <style>
                                #postbox-container-2 .wc-order-items-editable {
                                    opacity: 1;
                                    pointer-events: auto;
                                }
                            </style>
                            <?php
                        }



                    }
				}
			}
		}
	}


	/**
	 * Admin notices
	 *
	 * @since 	1.0.0
	 */
	public function custom_admin_notices( $post_states ) {

		global $pagenow;

		/*
		 * Options updated notice
		 */
		if ( $pagenow == 'admin.php' && ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'primer_' ) !== false ) && isset( $_POST['submit-cmb'] ) ) {
			echo '<div class="updated">
				<p>' . __( 'Settings saved successfully.', 'primer' ) . '</p>
			</div>';
		}

		/*
		 * Possible not compatible notices
		 */
		$errors = get_transient( 'primer_activation_warning' );
		if ( $errors ) {
			if ( $pagenow == 'plugins.php' && isset($errors['wp_error'] ) ) {
				echo '<div class="error">
		             <p>' . __( 'Your WordPress version may not be compatible with the Primer Receipts plugin. If you are having issues with the plugin, we recommend making a backup of your site and upgrading to the latest version of WordPress.', 'primer' ) . '</p>
		         </div>';
			}
			if ( $pagenow == 'plugins.php' && isset($errors['php_error'] ) ) {
				echo '<div class="error">
		             <p>' . __( 'Your PHP version may not be compatible with the Primer Receipts plugin. We recommend contacting your server administrator and getting them to upgrade to a newer version of PHP.', 'primer' ) . '</p>
		         </div>';
			}
			if ( $pagenow == 'plugins.php' && isset($errors['curl_error'] ) ) {
				echo '<div class="error">
		             <p>' . __( 'You do not have the cURL extension installed on your server. This extension is required for some tasks. Please contact your server administrator to have them install this on your server.', 'primer' ) . '</p>
		         </div>';
			}
		}
	}

	/**
	 * Add links to plugin page
	 *
	 * @since 	1.0.0
	 */
	public function plugin_action_links( $links ) {
		$links[] = '<a href="'. esc_url( get_admin_url( null, 'admin.php?page=primer' ) ) .'">' . __( 'Settings', 'primer' ) . '</a>';
		return $links;
	}

	/**
	 * Add a class to the admin body
	 *
	 * @since 	1.0.0
	 */
	public function add_admin_body_class( $classes ) {

		global $pagenow;
		$add_class = false;
		if ( $pagenow == 'admin.php' && isset( $_GET['page'] ) ) {
			$add_class = strpos( $_GET['page'], 'primer_' );
		}

		return $classes;
	}

	public function intervals($schedules) {
        $schedules['90seconds'] = array('interval' => 90, 'display' => __('90 seconds', 'primer'));
		$schedules['fiveminutes'] = array('interval' => 300, 'display' => __('5 minutes', 'primer'));
		$schedules['tenminutes'] = array('interval' => 600, 'display' => __('10 minutes', 'primer'));
		$schedules['thirtyminutes'] = array('interval' => 1800, 'display' => __('30 minutes', 'primer'));
		return $schedules;
	}

}




function primer_get_keys_labels( $all = true ) {
	$data = [
		'primer_invoice_type' => __('Invoice Type', 'primer'),
		'vat' => __('VAT', 'primer'),
		'store' 	=> __('Profession', 'primer'),
		'company' => __('Company Name', 'primer'),
		'phone_mobile' => __('Mobile Phone Number', 'primer'),
		'doy'   => __('DOY', 'primer')
	];
	if (! $all)
		unset($data['primer_invoice_type']);

	return $data;
}

/**
 * Array with taxis office codes and area.
 *
 * @return array
 */
function primer_return_doy_args() {
	$doy_args = array(
		""     => "Select...",
		"1101" => "ΑΘΗΝΩΝ Α'",
		"1104" => "ΑΘΗΝΩΝ Δ'",
		"1105" => "ΑΘΗΝΩΝ Ε'",
        "1190" => "ΚΕΦΟΔΕ ΑΤΤΙΚΗΣ",
		"1106" => "ΑΘΗΝΩΝ ΣΤ'",
		"1110" => "ΑΘΗΝΩΝ Ι'",
		"1111" => "ΑΘΗΝΩΝ ΙΑ'",
		"1112" => "ΑΘΗΝΩΝ ΙΒ'",
		"1113" => "ΑΘΗΝΩΝ ΙΓ'",
		"1114" => "ΑΘΗΝΩΝ ΙΔ'",
		"1115" => "ΑΘΗΝΩΝ ΙΕ'",
		"1116" => "ΑΘΗΝΩΝ ΙΣΤ'",
		"1117" => "ΑΘΗΝΩΝ ΙΖ'",
		"1118" => "ΑΘΗΝΩΝ ΦΑΒΕ",
		"1124" => "ΑΘΗΝΩΝ ΙΗ'",
		"1125" => "ΚΑΤΟΙΚΩΝ ΕΞΩΤΕΡΙΚΟΥ",
		"1126" => "ΑΘΗΝΩΝ ΙΘ'",
		"1129" => "ΑΓ. ΔΗΜΗΤΡΙΟΥ",
		"1130" => "ΚΑΛΛΙΘΕΑΣ Α'",
		"1131" => "ΝΕΑΣ ΙΩΝΙΑΣ",
		"1132" => "ΝΕΑΣ ΣΜΥΡΝΗΣ",
		"1133" => "ΠΑΛΑΙΟΥ ΦΑΛΗΡΟΥ",
		"1134" => "ΧΑΛΑΝΔΡΙΟΥ",
		"1135" => "ΑΜΑΡΟΥΣΙΟΥ",
		"1136" => "ΑΓΙΩΝ ΑΝΑΡΓΥΡΩΝ",
		"1137" => "ΑΙΓΑΛΕΩ",
		"1138" => "ΠΕΡΙΣΤΕΡΙΟΥ Α'",
		"1139" => "ΓΛΥΦΑΔΑΣ",
		"1140" => "ΑΘΗΝΩΝ Κ'",
		"1141" => "ΑΘΗΝΩΝ ΚΑ'",
		"1142" => "ΑΘΗΝΩΝ ΚΒ'",
		"1143" => "ΑΘΗΝΩΝ ΚΓ'",
		"1144" => "ΔΑΦΝΗΣ",
		"1145" => "ΗΡΑΚΛΕΙΟΥ ΑΤΤΙΚΗΣ",
		"1151" => "ΑΓΙΑΣ ΠΑΡΑΣΚΕΥΗΣ",
		"1152" => "ΒΥΡΩΝΑ",
		"1153" => "ΚΗΦΙΣΙΑΣ",
		"1154" => "ΙΛΙΟΥ",
		"1155" => "ΝΕΑΣ ΦΙΛΑΔΕΛΦΕΙΑΣ",
		"1156" => "ΧΑΙΔΑΡΙΟΥ",
		"1157" => "ΠΕΡΙΣΤΕΡΙΟΥ Β'",
        "1158" => "ΠΕΡΙΣΤΕΡΙΟΥ",
		"1159" => "ΑΘΗΝΩΝ ΦΑΕΕ",
		"1172" => "ΖΩΓΡΑΦΟΥ",
		"1173" => "ΗΛΙΟΥΠΟΛΗΣ",
		"1174" => "ΚΑΛΛΙΘΕΑΣ Β'",
		"1175" => "ΨΥΧΙΚΟΥ",
		"1176" => "ΧΟΛΑΡΓΟΥ",
		"1177" => "ΑΡΓΥΡΟΥΠΟΛΗΣ",
		"1178" => "ΠΕΤΡΟΥΠΟΛΗΣ",
		"1179" => "ΓΑΛΑΤΣΙΟΥ",
		"1180" => "ΑΝΩ ΛΙΟΣΙΩΝ",
		"1201" => "ΠΕΙΡΑΙΑ Α'",
		"1203" => "ΠΕΙΡΑΙΑ Γ'",
		"1204" => "ΠΕΙΡΑΙΑ Δ'",
		"1205" => "ΠΕΙΡΑΙΑ Ε'",
		"1206" => "ΠΕΙΡΑΙΑ ΦΑΕ",
		"1207" => "ΠΕΙΡΑΙΑ ΠΛΟΙΩΝ",
		"1209" => "ΠΕΙΡΑΙΑ ΣΤ'",
		"1210" => "ΚΟΡΥΔΑΛΛΟΥ",
		"1211" => "ΜΟΣΧΑΤΟΥ",
		"1220" => "ΝΙΚΑΙΑΣ",
		"1301" => "ΑΙΓΙΝΑΣ",
		"1302" => "ΑΧΑΡΝΩΝ",
		"1303" => "ΕΛΕΥΣΙΝΑΣ",
		"1304" => "ΚΟΡΩΠΙΟΥ",
		"1305" => "ΚΥΘΗΡΩΝ",
		"1306" => "ΛΑΥΡΙΟΥ",
		"1307" => "ΑΓΙΟΥ ΣΤΕΦΑΝΟΥ",
		"1308" => "ΜΕΓΑΡΩΝ",
		"1309" => "ΣΑΛΑΜΙΝΑΣ",
		"1310" => "ΠΟΡΟΥ",
		"1311" => "ΥΔΡΑΣ",
		"1312" => "ΠΑΛΛΗΝΗΣ",
		"1411" => "ΘΗΒΑΣ",
		"1421" => "ΛΕΙΒΑΔΙΑΣ",
		"1511" => "ΑΜΦΙΛΟΧΙΑΣ",
		"1521" => "ΑΣΤΑΚΟΥ",
		"1522" => "ΒΟΝΙΤΣΑΣ",
		"1531" => "ΜΕΣΟΛΟΓΓΙΟΥ",
		"1541" => "ΝΑΥΠΑΚΤΟΥ",
		"1551" => "ΘΕΡΜΟΥ",
		"1552" => "ΑΓΡΙΝΙΟΥ",
		"1611" => "ΚΑΡΠΕΝΗΣΙΟΥ",
		"1711" => "ΙΣΤΙΑΙΑΣ",
		"1721" => "ΚΑΡΥΣΤΟΥ",
		"1722" => "ΚΥΜΗΣ",
		"1731" => "ΛΙΜΝΗΣ",
		"1732" => "ΧΑΛΚΙΔΑΣ",
		"1811" => "ΔΟΜΟΚΟΥ",
		"1821" => "ΑΜΦΙΚΛΕΙΑΣ",
		"1822" => "ΑΤΑΛΑΝΤΗΣ",
		"1831" => "ΜΑΚΡΑΚΩΜΗΣ",
		"1832" => "ΛΑΜΙΑΣ",
		"1833" => "ΣΤΥΛΙΔΑΣ",
		"1911" => "ΛΙΔΟΡΙΚΙΟΥ",
		"1912" => "ΑΜΦΙΣΣΑΣ",
		"2111" => "ΑΡΓΟΥΣ",
		"2121" => "ΣΠΕΤΣΩΝ",
		"2122" => "ΚΡΑΝΙΔΙΟΥ",
		"2131" => "ΝΑΥΠΛΙΟΥ",
		"2211" => "ΔΗΜΗΤΣΑΝΑΣ",
		"2213" => "ΛΕΩΝΙΔΙΟΥ",
		"2214" => "ΤΡΟΠΑΙΩΝ",
		"2221" => "ΠΑΡΑΛΙΟΥ ΑΣΤΡΟΥΣ",
		"2231" => "ΤΡΙΠΟΛΗΣ",
		"2241" => "ΜΕΓΑΛΟΠΟΛΗΣ",
		"2311" => "ΑΙΓΙΟΥ",
		"2312" => "ΑΚΡΑΤΑΣ",
		"2321" => "ΚΑΛΑΒΡΥΤΩΝ",
		"2322" => "ΚΛΕΙΤΟΡΙΑΣ",
		"2331" => "ΠΑΤΡΩΝ Α'",
		"2332" => "ΠΑΤΡΩΝ Β'",
		"2333" => "ΚΑΤΩ ΑΧΑΙΑΣ",
		"2334" => "ΠΑΤΡΩΝ Γ'",
		"2411" => "ΑΜΑΛΙΑΔΑΣ",
		"2412" => "ΠΥΡΓΟΥ",
		"2413" => "ΓΑΣΤΟΥΝΗΣ",
		"2414" => "ΒΑΡΔΑ",
		"2421" => "ΚΡΕΣΤΕΝΩΝ",
		"2422" => "ΛΕΧΑΙΝΩΝ",
		"2423" => "ΑΝΔΡΙΤΣΑΙΝΑΣ",
		"2424" => "ΖΑΧΑΡΩΣ",
		"2511" => "ΔΕΡΒΕΝΙΟΥ",
		"2512" => "ΚΙΑΤΟΥ",
		"2513" => "ΚΟΡΙΝΘΟΥ",
		"2514" => "ΝΕΜΕΑΣ",
		"2515" => "ΞΥΛΟΚΑΣΤΡΟΥ",
		"2611" => "ΓΥΘΕΙΟΥ",
		"2621" => "ΜΟΛΑΩΝ",
		"2622" => "ΝΕΑΠΟΛΗΣ ΒΟΙΩΝ ΛΑΚΩΝΙΑΣ",
		"2630" => "ΣΚΑΛΑ ΛΑΚΩΝΙΑΣ",
		"2631" => "ΚΡΟΚΕΩΝ",
		"2632" => "ΣΠΑΡΤΗΣ",
		"2641" => "ΑΡΕΟΠΟΛΗΣ",
		"2711" => "ΚΑΛΑΜΑΤΑΣ",
		"2721" => "ΜΕΛΙΓΑΛΑ",
		"2722" => "ΜΕΣΣΗΝΗΣ",
		"2731" => "ΠΥΛΟΥ",
		"2741" => "ΓΑΡΓΑΛΙΑΝΩΝ",
		"2742" => "ΚΥΠΑΡΙΣΣΙΑΣ",
		"2743" => "ΦΙΛΙΑΤΡΩΝ ΜΕΣΣΗΝΙΑΣ",
		"3111" => "ΚΑΡΔΙΤΣΑΣ",
		"3112" => "ΜΟΥΖΑΚΙΟΥ",
		"3113" => "ΣΟΦΑΔΩΝ",
		"3114" => "ΠΑΛΑΜΑ",
		"3211" => "ΑΓΙΑΣ",
		"3221" => "ΕΛΑΣΣΟΝΑΣ",
		"3222" => "ΔΕΣΚΑΤΗΣ",
		"3231" => "ΛΑΡΙΣΑΣ Α'",
		"3232" => "ΛΑΡΙΣΑΣ Β'",
		"3233" => "ΛΑΡΙΣΑΣ Γ'",
		"3241" => "ΤΥΡΝΑΒΟΥ",
		"3251" => "ΦΑΡΣΑΛΩΝ",
		"3311" => "ΑΛΜΥΡΟΥ",
		"3321" => "ΒΟΛΟΥ Α'",
		"3322" => "ΒΟΛΟΥ Β'",
		"3323" => "ΙΩΝΙΑΣ ΜΑΓΝΗΣΙΑΣ",
		"3331" => "ΣΚΟΠΕΛΟΥ",
		"3332" => "ΣΚΙΑΘΟΥ",
		"3411" => "ΚΑΛΑΜΠΑΚΑΣ",
		"3412" => "ΤΡΙΚΑΛΩΝ",
		"3413" => "ΠΥΛΗΣ",
		"4111" => "ΑΛΕΞΑΝΔΡΕΙΑΣ",
		"4112" => "ΒΕΡΟΙΑΣ",
		"4121" => "ΝΑΟΥΣΑΣ",
		"4211" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Α'",
		"4212" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Β'",
		"4214" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Δ'",
		"4215" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Ε'",
		"4216" => "ΘΕΣΣΑΛΟΝΙΚΗΣ ΣΤ'",
		"4217" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Ζ'",
		"4221" => "ΖΑΓΚΛΙΒΕΡΙΟΥ",
		"4222" => "ΛΑΓΚΑΔΑ",
		"4223" => "ΣΩΧΟΥ",
		"4224" => "ΘΕΣΣΑΛΟΝΙΚΗΣ ΦΑΕ",
		"4225" => "ΝΕΑΠΟΛΗΣ ΘΕΣ/ΝΙΚΗΣ",
		"4226" => "ΤΟΥΜΠΑΣ",
		"4227" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Ι'",
		"4228" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Η'",
		"4229" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Θ'",
		"4231" => "ΑΓ. ΑΘΑΝΑΣΙΟΥ",
		"4232" => "ΚΑΛΑΜΑΡΙΑΣ",
		"4233" => "ΑΜΠΕΛΟΚΗΠΩΝ",
		"4234" => "Ν.ΙΩΝΙΑΣ ΘΕΣ/ΚΗΣ",
		"4311" => "ΚΑΣΤΟΡΙΑΣ",
		"4312" => "ΝΕΣΤΟΡΙΟΥ",
		"4313" => "ΑΡΓΟΥΣ ΟΡΕΣΤΙΚΟΥ",
		"4411" => "ΚΙΛΚΙΣ",
		"4421" => "ΓΟΥΜΕΝΙΣΣΑΣ",
		"4521" => "ΓΡΕΒΕΝΩΝ",
		"4511" => "ΝΕΑΠΟΛΗΣ ΒΟΙΟΥ",
		"4531" => "ΠΤΟΛΕΜΑΙΔΑΣ",
		"4541" => "ΚΟΖΑΝΗ",
		"4542" => "ΣΕΡΒΙΩΝ",
		"4543" => "ΣΙΑΤΙΣΤΑΣ",
		"4611" => "ΑΡΙΔΑΙΑΣ",
		"4621" => "ΓΙΑΝΝΙΤΣΩΝ",
		"4631" => "ΕΔΕΣΣΑΣ",
		"4641" => "ΣΚΥΔΡΑΣ",
		"4711" => "ΚΑΤΕΡΙΝΗΣ Α'",
		"4712" => "ΚΑΤΕΡΙΝΗΣ Β'",
		"4714" => "ΑΙΓΙΝΙΟΥ",
		"4811" => "ΑΜΥΝΤΑΙΟΥ",
		"4812" => "ΦΛΩΡΙΝΑΣ",
		"4911" => "ΑΡΝΑΙΑΣ",
		"4921" => "ΚΑΣΣΑΝΔΡΑΣ",
		"4922" => "ΠΟΛΥΓΥΡΟΥ",
		"4923" => "ΝΕΩΝ ΜΟΥΔΑΝΙΩΝ",
		"5111" => "ΔΡΑΜΑΣ",
		"5112" => "ΝΕΥΡΟΚΟΠΙΟΥ",
		"5211" => "ΑΛΕΞΑΝΔΡΟΥΠΟΛΗΣ",
		"5221" => "ΔΙΔΥΜΟΤΕΙΧΟΥ",
		"5231" => "ΟΡΕΣΤΕΙΑΔΑΣ",
		"5241" => "ΣΟΥΦΛΙΟΥ",
		"5311" => "ΘΑΣΟΥ",
		"5321" => "ΚΑΒΑΛΑΣ Α'",
		"5322" => "ΚΑΒΑΛΑΣ Β'",
		"5331" => "ΧΡΥΣΟΥΠΟΛΗΣ",
		"5341" => "ΕΛΕΥΘΕΡΟΥΠΟΛΗΣ",
		"5411" => "ΞΑΝΘΗΣ Α'",
		"5412" => "ΞΑΝΘΗΣ Β'",
		"5511" => "ΚΟΜΟΤΗΝΗΣ",
		"5521" => "ΣΑΠΠΩΝ",
		"5611" => "ΝΙΓΡΙΤΑΣ",
		"5621" => "ΣΕΡΡΩΝ Α'",
		"5622" => "ΣΕΡΡΩΝ Β'",
		"5631" => "ΣΙΔΗΡΟΚΑΣΤΡΟΥ",
		"5632" => "ΗΡΑΚΛΕΙΑΣ",
		"5641" => "ΝΕΑΣ ΖΙΧΝΗΣ",
		"6111" => "ΑΡΤΑΣ",
		"6113" => "ΦΙΛΙΠΠΙΑΔΑΣ",
		"6211" => "ΗΓΟΥΜΕΝΙΤΣΑΣ",
		"6231" => "ΠΑΡΑΜΥΘΙΑΣ",
		"6241" => "ΦΙΛΙΑΤΩΝ",
		"6221" => "ΠΑΡΓΑΣ",
		"6222" => "ΦΑΝΑΡΙΟΥ",
		"6411" => "ΠΡΕΒΕΖΑΣ",
		"6311" => "ΙΩΑΝΝΙΝΩΝ Α'",
		"6312" => "ΙΩΑΝΝΙΝΩΝ Β'",
		"6313" => "ΔΕΛΒΙΝΑΚΙΟΥ",
		"6315" => "ΜΕΤΣΟΒΟΥ",
		"6321" => "ΚΟΝΙΤΣΑΣ",
		"7111" => "ΑΝΔΡΟΥ",
		"7121" => "ΘΗΡΑΣ",
		"7131" => "ΚΕΑΣ",
		"7141" => "ΜΗΛΟΥ",
		"7151" => "ΝΑΞΟΥ",
		"7161" => "ΠΑΡΟΥ",
		"7171" => "ΣΥΡΟΥ",
		"7172" => "ΜΥΚΟΝΟΥ",
		"7181" => "ΤΗΝΟΥ",
		"7211" => "ΛΗΜΝΟΥ",
		"7221" => "ΚΑΛΛΟΝΗΣ",
		"7222" => "ΜΗΘΥΜΝΑΣ",
		"7231" => "ΜΥΤΙΛΗΝΗΣ",
		"7241" => "ΠΛΩΜΑΡΙΟΥ",
		"7311" => "ΑΓ. ΚΗΡΥΚΟΥ ΙΚΑΡΙΑΣ",
		"7321" => "ΚΑΡΛΟΒΑΣΙΟΥ",
		"7322" => "ΣΑΜΟΥ",
		"7411" => "ΧΙΟΥ",
		"7511" => "ΚΑΛΥΜΝΟΥ",
		"7512" => "ΛΕΡΟΥ",
		"7521" => "ΚΑΡΠΑΘΟΥ",
		"7531" => "ΚΩ",
		"7542" => "ΡΟΔΟΥ",
        "8110" => "ΗΡΑΚΛΕΙΟΥ",
		"8112" => "ΜΟΙΡΩΝ",
		"8114" => "ΤΥΜΠΑΚΙΟΥ",
		"8115" => "ΛΙΜΕΝΑ ΧΕΡΣΟΝΗΣΟΥ",
		"8121" => "ΚΑΣΤΕΛΙΟΥ ΠΕΔΙΑΔΟΣ",
		"8131" => "ΑΡΚΑΛΟΧΩΡΙΟΥ",
		"8211" => "ΙΕΡΑΠΕΤΡΑΣ",
		"8221" => "ΑΓΙΟΥ ΝΙΚΟΛΑΟΥ",
		"8231" => "ΝΕΑΠΟΛΗΣ ΚΡΗΤΗΣ",
		"8241" => "ΣΗΤΕΙΑΣ",
		"8341" => "ΡΕΘΥΜΝΟΥ",
		"8421" => "ΚΙΣΣΑΜΟΥ",
		"8431" => "ΧΑΝΙΩΝ Α'",
		"8432" => "ΧΑΝΙΩΝ Β'",
		"9111" => "ΖΑΚΥΝΘΟΥ",
		"9211" => "ΚΕΡΚΥΡΑΣ Α'",
		"9212" => "ΚΕΡΚΥΡΑΣ Β'",
		"9221" => "ΠΑΞΩΝ",
		"9311" => "ΑΡΓΟΣΤΟΛΙΟΥ",
		"9321" => "ΛΗΞΟΥΡΙΟΥ",
		"9411" => "ΙΘΑΚΗΣ",
		"9421" => "ΛΕΥΚΑΔΑΣ",
	);
	return $doy_args;
}

function theme_xyz_header_metadata() { ?>
	<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
	<?php
}
add_action( 'admin_head', 'theme_xyz_header_metadata' );

/**
 * Check the time difference between the current time and the time in Athens, Greece.
 *
 * @return string The message indicating whether the time is correct or not.
 */
function primer_check_time_difference() {
	$time_message = '';
	$gr_time = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, "GR");
	$d = new DateTime("now", new DateTimeZone("Europe/Athens"));
	$format_date = $d->format(DateTime::W3C);
	$f = new DateTime("now");
	$current_date = date_i18n('Y-m-d\TH:i:sP');


	if ($format_date > $current_date || $format_date < $current_date) {
		$time_message = '<p>'.__('Fail - ', 'primer').'<span class="info_error">'.$format_date.__('Time is not correct with Greek time zone. AADE will not accept your invoices.', 'primer').'</span></p>';
	}
	if ($format_date == $current_date) {
		$time_message = '<p><span class="info_success">'.__('Pass', 'primer').'</span></p>';
	}

	return $time_message;
}

function primer_plugin_allowed_functions($plugin_modules) {

	$plugin_edition = '';

	$silver_edition = array('1', '2', '6', '7', '8', '11', '12', '13');

	$bronze_edition = array('1', '2', '6', '7', '8', '11', '12', '13', '5', '3');

	$gold_edition = array('1', '2', '6', '7', '8', '11', '12', '13', '5', '3', '9', '10', '4');

	asort($silver_edition, SORT_NUMERIC );
	asort($bronze_edition, SORT_NUMERIC );
	asort($gold_edition, SORT_NUMERIC );

	$silver_arr = array_diff($silver_edition, $plugin_modules);
	$bronze_arr = array_diff($bronze_edition, $plugin_modules);
	$gold_arr = array_diff($gold_edition, $plugin_modules);

	if (empty($silver_arr)) {
		$plugin_edition = 'silver_edition';
	}

	if (empty($bronze_arr)) {
		$plugin_edition = 'bronze_edition';
	}

	if (empty($gold_arr)) {
		$plugin_edition = 'gold_edition';
	}

	return $plugin_edition;
}
