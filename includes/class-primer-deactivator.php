<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }
/**
 * Fired during plugin deactivation
 *
 * @link       test.example.com
 * @since      1.0.0
 *
 * @package    Primer
 * @subpackage Primer/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Primer
 * @subpackage Primer/includes
 * @author     test_user <testwe@gmail.com>
 */
class Primer_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		// Primer Recurring Tasks
		wp_clear_scheduled_hook( 'primer_cron_process' );
        wp_clear_scheduled_hook( 'primer_cron_process_failed' );
        wp_clear_scheduled_hook( 'primer_cron_process_credit_failed' );

		//flush_rewrite_rules();
	}

}
