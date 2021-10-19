<?php
/**
 * WC Admin Onboarding Traits
 *
 * WC Admin Onboarding Traits class that houses shared functionality useful for checking status of onboarding.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * OnboardingTraits class, encapsulates onboarding checks and functionality
 * that are useful to determining whether to display notes or not.
 */
trait OnboardingTraits {
	/**
	 * Get access to the onboarding profile option.
	 */
	private static function get_onboarding_profile() {
		return get_option( 'woocommerce_onboarding_profile', array() );
	}

	/**
	 * Check if user has started the onboarding profile wizard.
	 *
	 * @return bool Whether or not the onboarding profile has been started.
	 */
	public static function onboarding_profile_started() {
		$onboarding_profile = self::get_onboarding_profile();
		return ! empty( $onboarding_profile );
	}

	/**
	 * Check if onboarding profile revenue is between 2 amounts
	 *
	 * @param int $min_dollars Minimum amount the range must fall within (inclusive).
	 * @param int $max_dollars Maximum amount the range must fall within (inclusive).
	 * @return bool Whether the revenue falls within the min and max (inclusive).
	 */
	public static function revenue_is_within( $min_dollars, $max_dollars ) {
		$onboarding_profile = self::get_onboarding_profile();

		if ( empty( $onboarding_profile ) || ! isset( $onboarding_profile['revenue'] ) ) {
			return false;
		}

		return $onboarding_profile['revenue'] >= $min_dollars && $onboarding_profile['revenue'] <= $max_dollars;
	}

	/**
	 * Check if the store was marked as being setup for a client in onboarding. (Returns false if onboarding
	 * was not completed).
	 *
	 * @return bool Whether or not the store is being setup for a client.
	 */
	public static function store_setup_for_client() {
		$onboarding_profile = self::get_onboarding_profile();
		return ! empty( $onboarding_profile ) && isset( $onboarding_profile['setup_client'] ) && $onboarding_profile['setup_client'];
	}
}
