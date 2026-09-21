<?php
/**
 * Server-side Tracks event recorder for the WC Email Template Sync test helper plugin.
 *
 * @package WC_Email_Template_Sync_Test_Helper
 */

declare( strict_types=1 );

namespace WC_Email_Template_Sync_Test_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Mirrors server-side recordEvent calls and the backfill-complete action to an option
 * so Playwright tests can drain and assert. Dormant unless wc_test_tracks_enabled=yes.
 */
class Tracks_Recorder {

	public const ENABLED_OPTION = 'wc_test_tracks_enabled';
	public const LOG_OPTION     = 'wc_test_tracks_log';

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_tracks_event_properties', array( $this, 'record' ), 100, 2 );
	}

	/**
	 * Append a record to the tracks log when enabled. Always returns the untouched properties.
	 *
	 * @param array  $properties Event properties (passed through unchanged).
	 * @param string $event_name Event name.
	 * @return array
	 */
	public function record( $properties, $event_name ): array {
		if ( 'yes' !== get_option( self::ENABLED_OPTION, 'no' ) ) {
			return is_array( $properties ) ? $properties : array();
		}

		$log = get_option( self::LOG_OPTION, array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}

		$log[] = array(
			'name'         => (string) $event_name,
			'properties'   => is_array( $properties ) ? $properties : array(),
			'timestamp_ms' => (int) ( microtime( true ) * 1000 ),
		);

		update_option( self::LOG_OPTION, $log, false );

		return is_array( $properties ) ? $properties : array();
	}
}
