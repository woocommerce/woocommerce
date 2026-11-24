<?php
/**
 * Plugin Name: E2E Log Marker
 * Description: Writes a marker to verify the browser test site's PHP debug log.
 *
 * @package WooCommerce\ExtensionTooling
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Test site log probe.
		if ( ! isset( $_GET['e2e_log_marker'] ) || ! is_string( $_GET['e2e_log_marker'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Test site log probe.
		$marker = sanitize_key( wp_unslash( $_GET['e2e_log_marker'] ) );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Test site log probe.
		error_log( 'e2e-log-marker-' . $marker );
	}
);
