/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { createDeprecatedPropertiesProxy } from './index';

// Remove mutable data from settings object to prevent access. Data stores should be used instead.
const mutableSources = [ 'wcAdminSettings', 'preloadSettings' ];
const adminSettings = getSetting( 'admin', {} );
const ADMIN_SETTINGS_SOURCE = Object.keys( adminSettings ).reduce(
	( source, key ) => {
		if ( ! mutableSources.includes( key ) ) {
			source[ key ] = adminSettings[ key ];
		}
		return source;
	},
	{}
);

export const deprecatedAdminProperties = {
	onboarding: {
		profile:
			'Deprecated: wcSettings.admin.onboarding.profile is deprecated. It is planned to be released in WooCommerce 10.0.0. Please use `getProfileItems` from the onboarding store. See https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/data/src/onboarding for more information.',
		euCountries:
			'Deprecated: wcSettings.admin.onboarding.euCountries is deprecated. Please use `/wc/v3/data/continents/eu` from the REST API. See https://woocommerce.github.io/woocommerce-rest-api-docs/#list-all-continents for more information.',
		localInfo:
			'Deprecated: wcSettings.admin.onboarding.localInfo is deprecated. Please use `include WC()->plugin_path() . "/i18n/locale-info.php"` instead.',
		currencySymbols:
			'"Deprecated: wcSettings.admin.onboarding.currencySymbols is deprecated. Please use get_woocommerce_currency_symbols() function instead.',
	},
};

/**
 * Retrieves a setting value from the setting state.
 *
 * @param {string}   name                    The identifier for the setting.
 * @param {*}        [fallback=false]        The value to use as a fallback
 *                                           if the setting is not in the
 *                                           state.
 * @param {Function} [filter=( val ) => val] A callback for filtering the
 *                                           value before it's returned.
 *                                           Receives both the found value
 *                                           (if it exists for the key) and
 *                                           the provided fallback arg.
 *
 * @return {*}  The value present in the settings state for the given
 *                   name.
 */
export function getAdminSetting(
	name,
	fallback = false,
	filter = ( val ) => val,
	deprecatedProperties = deprecatedAdminProperties
) {
	if ( mutableSources.includes( name ) ) {
		throw new Error(
			__(
				'Mutable settings should be accessed via data store.',
				'woocommerce'
			)
		);
	}

	const value = ADMIN_SETTINGS_SOURCE.hasOwnProperty( name )
		? ADMIN_SETTINGS_SOURCE[ name ]
		: fallback;
	const filtered = filter( value, fallback );

	// Return proxied object if the requested object has deprecated properties.
	return deprecatedProperties?.[ name ] &&
		process.env.NODE_ENV === 'development'
		? createDeprecatedPropertiesProxy(
				filtered,
				deprecatedProperties[ name ]
		  )
		: filtered;
}

export const ADMIN_URL = getSetting( 'adminUrl' );
export const COUNTRIES = getSetting( 'countries' );
export const CURRENCY = getSetting( 'currency' );
export const LOCALE = getSetting( 'locale' );
export const SITE_TITLE = getSetting( 'siteTitle' );
export const WC_ASSET_URL = getSetting( 'wcAssetUrl' );
export const ORDER_STATUSES = getAdminSetting( 'orderStatuses' );

/**
 * Sets a value to a property on the settings state.
 *
 * NOTE: This feature is to be removed in favour of data stores when a full migration
 * is complete.
 *
 * @deprecated
 *
 * @param {string}   name                    The setting property key for the
 *                                           setting being mutated.
 * @param {*}        value                   The value to set.
 * @param {Function} [filter=( val ) => val] Allows for providing a callback
 *                                           to sanitize the setting (eg.
 *                                           ensure it's a number)
 */
export function setAdminSetting( name, value, filter = ( val ) => val ) {
	if ( mutableSources.includes( name ) ) {
		throw new Error(
			__(
				'Mutable settings should be mutated via data store.',
				'woocommerce'
			)
		);
	}
	ADMIN_SETTINGS_SOURCE[ name ] = filter( value );
}
