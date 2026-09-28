<?php
/**
 * Records use of the removed Settings UI.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Logs and tracks calls to the deprecated Settings UI classes, so we know who still uses them before they're deleted.
 *
 * @internal
 * @since 11.4.0
 */
final class SettingsUIDeprecation {

	/**
	 * Symbols already recorded in this request.
	 *
	 * @var array<string, true>
	 */
	private static array $recorded = array();

	/**
	 * Log a warning and record a Tracks event for a deprecated symbol, once per request.
	 *
	 * @since 11.4.0
	 *
	 * @param string $symbol The class or method that was used.
	 */
	public static function record_usage( string $symbol ): void {
		if ( isset( self::$recorded[ $symbol ] ) ) {
			return;
		}
		self::$recorded[ $symbol ] = true;

		wc_get_logger()->warning(
			sprintf(
				'%s is deprecated since WooCommerce 11.4.0 and does nothing. The Settings UI was removed, so settings pages use the classic renderer.',
				$symbol
			),
			array( 'source' => 'settings-ui' )
		);

		if ( function_exists( 'wc_admin_record_tracks_event' ) ) {
			wc_admin_record_tracks_event( 'settings_ui_deprecated_usage', array( 'symbol' => $symbol ) );
		}
	}

	/**
	 * Forget what was recorded. For tests.
	 *
	 * @internal
	 */
	public static function reset(): void {
		self::$recorded = array();
	}
}
