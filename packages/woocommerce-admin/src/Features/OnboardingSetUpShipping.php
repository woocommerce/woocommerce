<?php
/**
 * Onboarding - set up shipping.
 *
 * @package Woocommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Features;

use \Automattic\WooCommerce\Admin\PluginsHelper;
use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes_Review_Shipping_Settings;

/**
 * This contains logic for setting up shipping when the profiler completes.
 */
class OnboardingSetUpShipping {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action(
			'woocommerce_onboarding_profile_completed',
			array(
				__CLASS__,
				'on_onboarding_profile_completed',
			)
		);

		add_action(
			'jetpack_authorize_ending_authorized',
			array(
				__CLASS__,
				'on_onboarding_profile_completed',
			)
		);
	}

	/**
	 * Set up shipping.
	 */
	public static function on_onboarding_profile_completed() {
		if ( ! self::has_physical_products() ) {
			return;
		}

		if ( self::has_existing_shipping_zones() ) {
			return;
		}

		$country_code = WC()->countries->get_base_country();

		// Corrolary to the logic in /client/task-list/tasks.js.
		// Skip for countries we don't recommend WCS for.
		if ( in_array( $country_code, array( 'AU', 'CA', 'GB' ), true ) ) {
			return;
		}

		if (
			! class_exists( '\Jetpack_Data' ) ||
			! class_exists( '\WC_Connect_Loader' ) ||
			! class_exists( '\WC_Connect_Options' )
		) {
			return;
		}

		$user_token        = \Jetpack_Data::get_access_token( JETPACK_MASTER_USER );
		$jetpack_connected = isset( $user_token->external_user_id );
		$wcs_version       = \WC_Connect_Loader::get_wcs_version();
		$wcs_tos_accepted  = \WC_Connect_Options::get_option( 'tos_accepted' );

		if ( ! $jetpack_connected || ! $wcs_version || ! $wcs_tos_accepted ) {
			return;
		}

		self::set_up_free_local_shipping();
		WC_Admin_Notes_Review_Shipping_Settings::possibly_add_note();
		wc_admin_record_tracks_event( 'shipping_automatically_set_up' );
	}

	/**
	 * Are there existing shipping zones?
	 *
	 * @return bool
	 */
	private static function has_existing_shipping_zones() {
		$zone_count = count( \WC_Shipping_Zones::get_zones() );

		return $zone_count > 0;
	}

	/**
	 * Is 'physical' selected as a product type?
	 *
	 * @return bool
	 */
	private static function has_physical_products() {
		$onboarding_data = get_option( Onboarding::PROFILE_DATA_OPTION );

		if ( ! isset( $onboarding_data['product_types'] ) ) {
			return false;
		}

		if ( ! in_array( 'physical', $onboarding_data['product_types'], true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Set up free local shipping.
	 */
	public static function set_up_free_local_shipping() {
		$country_code = WC()->countries->get_base_country();

		if ( ! $country_code ) {
			return;
		}

		$zone = new \WC_Shipping_Zone();
		$zone->add_location( $country_code, 'country' );

		$countries_service = new \WC_Countries();
		$countries         = $countries_service->get_countries();
		$zone_name         = isset( $countries[ $country_code ] )
			? $countries[ $country_code ]
			: $country_code;

		$zone->set_zone_name( $zone_name );

		$zone->save();
		$zone->add_shipping_method( 'free_shipping' );
	}
}
