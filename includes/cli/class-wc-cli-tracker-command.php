<?php
/**
 * WC_CLI_Tracker_Command class file.
 *
 * @package WooCommerce\CLI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allows access to tracker snapshot for transparency and debugging.
 *
 * @since x.x.x
 * @package WooCommerce
 */
class WC_CLI_Tracker_Command {

	/**
	 * Registers a command for showing WooCommerce Tracker snapshot data.
	 */
	public static function register_commands() {
		WP_CLI::add_command( 'wc tracker-snapshot', array( 'WC_CLI_Tracker_Command', 'show_tracker_snapshot' ) );
	}

	/**
	 * Dump tracker snapshot data to screen.
	 */
	public static function show_tracker_snapshot() {
		$snapshot_data = WC_Tracker::get_tracking_data();

		// Using print_r as a convenient way to dump the snapshot data to screen in a readable form.
		WP_CLI::log( print_r( $snapshot_data, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		// Could improve this, e.g. flatten keys (join nested fields with `-`) and render as CSV or json.

		WP_CLI::success( 'Printed tracker data.' );
	}
}
