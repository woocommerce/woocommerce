<?php
/**
 * Navigation v2 telemetry.
 *
 * Server-side Tracks events:
 *   - navigation_v2_toggled       — on any flag flip, {enabled: bool}.
 *   - navigation_v2_duration_days — daily beacon while enabled, scheduled via Action Scheduler.
 *
 * JS-side Tracks events (emitted from admin-navigation-v2.js):
 *   - navigation_v2_item_clicked  — {slug, depth, surface}.
 *   - navigation_v2_hover_opened  — {depth_reached}.
 *   - navigation_v2_back_clicked  — count of explicit exits.
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Emits navigation_v2 Tracks events.
 */
class Telemetry {

	private const OPTION_NAME         = 'woocommerce_feature_navigation_v2_enabled';
	private const DAILY_BEACON_ACTION = 'woocommerce_nav_v2_daily_beacon';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'update_option_' . self::OPTION_NAME, array( $this, 'on_flag_toggled' ), 10, 2 );
		add_action( 'add_option_' . self::OPTION_NAME, array( $this, 'on_flag_toggled_first_time' ), 10, 2 );
		add_action( self::DAILY_BEACON_ACTION, array( $this, 'emit_daily_beacon' ) );

		$this->maybe_schedule_daily_beacon();
	}

	/**
	 * Fire toggled event with the new enabled state.
	 *
	 * @param string $old Old value.
	 * @param string $new New value.
	 */
	public function on_flag_toggled( $old, $new ): void {
		if ( function_exists( 'wc_admin_record_tracks_event' ) ) {
			wc_admin_record_tracks_event(
				'navigation_v2_toggled',
				array( 'enabled' => 'yes' === $new )
			);
		}
	}

	/**
	 * First-time add_option signature: ($option, $value) not ($old, $new).
	 *
	 * @param string $option Option name.
	 * @param string $value  Option value.
	 */
	public function on_flag_toggled_first_time( $option, $value ): void {
		if ( function_exists( 'wc_admin_record_tracks_event' ) ) {
			wc_admin_record_tracks_event(
				'navigation_v2_toggled',
				array( 'enabled' => 'yes' === $value )
			);
		}
	}

	/**
	 * If the flag is on and no daily beacon is scheduled, schedule one.
	 */
	private function maybe_schedule_daily_beacon(): void {
		if ( 'yes' !== get_option( self::OPTION_NAME, 'no' ) ) {
			return;
		}
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}
		if ( ! as_has_scheduled_action( self::DAILY_BEACON_ACTION ) ) {
			as_schedule_recurring_action( time(), DAY_IN_SECONDS, self::DAILY_BEACON_ACTION, array(), 'woocommerce-nav-v2' );
		}
	}

	/**
	 * Emit the daily-duration beacon.
	 */
	public function emit_daily_beacon(): void {
		if ( 'yes' !== get_option( self::OPTION_NAME, 'no' ) ) {
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( self::DAILY_BEACON_ACTION );
			}
			return;
		}

		if ( function_exists( 'wc_admin_record_tracks_event' ) ) {
			wc_admin_record_tracks_event( 'navigation_v2_duration_days', array() );
		}
	}
}
