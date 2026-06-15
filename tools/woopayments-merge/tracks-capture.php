<?php
/**
 * Tracks capture drop-in (WooPayments → core merge verification harness).
 *
 * Records every SERVER-SIDE Tracks emission as line-delimited JSON so the Tracks-parity gate
 * can diff the {name, props} contract between the reference (plugin) and target (native) runtimes
 * — the runtime half of the non-negotiable telemetry-continuity requirement (bc-manifest §0.3/§3.6).
 *
 * Install: copy into the store's `wp-content/mu-plugins/` (both stores, for cross-store parity).
 * Tracking must be enabled (WC_Tracks::record_event early-returns otherwise) — the harness sets
 * `woocommerce_allow_tracking=yes` before driving flows.
 *
 * Captures the WC Tracks path (WC_Tracks::record_event → woocommerce_tracks_event_properties),
 * which is where the plugin's PHP recorders (wc_admin_record_tracks_event / WCPay\Tracker) land.
 * Client-side JS events (window.wcTracks.recordEvent — the majority) are captured separately by the
 * Playwright spy in the e2e flow drivers (see HARNESS.md); this drop-in is the server-side half.
 *
 * Capture file: wp-content/uploads/wcpay-tracks-capture.jsonl (truncate via the harness between runs).
 *
 * @package WooCommerce\Tools\WooPaymentsMerge
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'woocommerce_tracks_event_properties',
	function ( $properties, $event_name ) {
		$dir  = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR . '/uploads' : sys_get_temp_dir();
		$file = $dir . '/wcpay-tracks-capture.jsonl';
		$line = wp_json_encode(
			array(
				'event' => (string) $event_name,
				'props' => is_array( $properties ) ? $properties : array(),
			)
		);
		if ( false !== $line ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			@file_put_contents( $file, $line . "\n", FILE_APPEND | LOCK_EX );
		}
		return $properties;
	},
	99,
	2
);
