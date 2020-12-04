<?php
/**
 * Onboarding - set up automated tax calculation.
 *
 * @package Woocommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Features;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks;
use Automattic\WooCommerce\Admin\Notes\ConfirmTaxSettings;

/**
 * This contains logic for setting up shipping when the profiler completes.
 */
class OnboardingAutomateTaxes {
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
	 * Set up automated taxes.
	 */
	public static function on_onboarding_profile_completed() {
		$jetpack_connected = null;
		$wcs_version       = null;
		$wcs_tos_accepted  = null;

		if ( class_exists( '\Jetpack_Data' ) ) {
			$user_token        = \Jetpack_Data::get_access_token( JETPACK_MASTER_USER );
			$jetpack_connected = isset( $user_token->external_user_id );
		}

		if ( class_exists( '\WC_Connect_Loader' ) ) {
			$wcs_version = \WC_Connect_Loader::get_wcs_version();
		}

		if ( class_exists( '\WC_Connect_Options' ) ) {
			$wcs_tos_accepted = \WC_Connect_Options::get_option( 'tos_accepted' );
		}

		if ( $jetpack_connected && $wcs_version && $wcs_tos_accepted && self::automated_tax_is_supported() ) {
			update_option( 'wc_connect_taxes_enabled', 'yes' );
			update_option( 'woocommerce_calc_taxes', 'yes' );
			self::track_tax_automation();
			ConfirmTaxSettings::possibly_add_note();
		}
	}

	/**
	 * Check if automated taxes are supported.
	 */
	private static function automated_tax_is_supported() {
		return in_array( WC()->countries->get_base_country(), OnboardingTasks::get_automated_tax_supported_countries(), true );
	}

	/**
	 * Track when a user has tax automation enabled.
	 */
	private static function track_tax_automation() {
		wc_admin_record_tracks_event( 'tasklist_task_completed', array( 'task_name' => 'tax_automated' ) );
	}
}
