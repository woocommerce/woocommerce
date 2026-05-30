<?php
/**
 * Navigation v2 telemetry.
 *
 * Server-side Tracks events:
 *   - navigation_v2_toggled — on any flag flip, {enabled: bool}.
 *
 * JS-side Tracks events (emitted from admin-navigation-v2.js):
 *   - navigation_v2_item_clicked  — {slug, depth, surface}.
 *   - navigation_v2_hover_opened  — {depth_reached}.
 *   - navigation_v2_back_clicked  — count of explicit exits.
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Emits navigation_v2 Tracks events.
 */
class Telemetry {

	private const OPTION_NAME = 'woocommerce_feature_navigation_v2_enabled';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'update_option_' . self::OPTION_NAME, array( $this, 'on_flag_toggled' ), 10, 2 );
		add_action( 'add_option_' . self::OPTION_NAME, array( $this, 'on_flag_toggled_first_time' ), 10, 2 );
	}

	/**
	 * Fire toggled event with the new enabled state.
	 *
	 * @internal
	 *
	 * @param string $_old Old value (unused; required by hook signature).
	 * @param string $new_value New value.
	 */
	public function on_flag_toggled( $_old, $new_value ): void {
		$this->record_toggled( 'yes' === $new_value );
	}

	/**
	 * First-time add_option signature: ($option, $value) not ($old, $new).
	 *
	 * @internal
	 *
	 * @param string $_option Option name (unused; required by hook signature).
	 * @param string $value   Option value.
	 */
	public function on_flag_toggled_first_time( $_option, $value ): void {
		$this->record_toggled( 'yes' === $value );
	}

	/**
	 * Emit the toggled Tracks event if Tracks is available.
	 *
	 * @param bool $enabled Whether the feature is now enabled.
	 */
	private function record_toggled( bool $enabled ): void {
		if ( function_exists( 'wc_admin_record_tracks_event' ) ) {
			wc_admin_record_tracks_event(
				'navigation_v2_toggled',
				array( 'enabled' => $enabled )
			);
		}
	}
}
