/** @format */
/**
 * Internal dependencies
 */

import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Gets the country code from a country:state value string.
 *
 * @format
 * @param {string} countryState Country state string, e.g. US:GA.
 * @return {string} Country string.
 */

export function getCountryCode( countryState ) {
	if ( ! countryState ) {
		return null;
	}

	return countryState.split( ':' )[ 0 ];
}

/**
 * Returns if the onboarding feature of WooCommerce Admin should be enabled.
 *
 * While we preform an a/b test of onboarding, the feature will be enabled within the plugin build,
 * but only if the user recieved the test/opted in.
 *
 * @return {bool} True if the onboarding is enabled.
 */
export function isOnboardingEnabled() {
	if ( ! window.wcAdminFeatures.onboarding ) {
		return false;
	}

	return getSetting( 'onboardingEnabled', false );
}
