/** @format */

const defaults = {
	adminUrl: '',
	countries: [],
	currency: {
		code: 'USD',
		precision: 2,
		symbol: '$',
		symbolPosition: 'left',
		decimalSeparator: '.',
		priceFormat: '%1$s%2$s',
		thousandSeparator: ',',
	},
	defaultDateRange: 'period=month&compare=previous_year',
	locale: {
		siteLocale: 'en_US',
		userLocale: 'en_US',
		weekdaysShort: [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ],
	},
	orderStatuses: [],
	siteTitle: '',
	wcAssetUrl: '',
};

const globalSharedSettings = typeof wcSettings === 'object' ? wcSettings : {};

// Use defaults or global settings, depending on what is set.
const allSettings = {
	...defaults,
	...globalSharedSettings,
};

allSettings.currency = {
	...defaults.currency,
	...allSettings.currency,
};

allSettings.locale = {
	...defaults.locale,
	...allSettings.locale,
};

// for anything you want exposed as non-mutable outside of its use in a module,
// import the constant. Otherwise use getSetting/setSetting for the value
// reference.
export const ADMIN_URL = allSettings.adminUrl;
export const COUNTRIES = allSettings.countries;
export const CURRENCY = allSettings.currency;
export const LOCALE = allSettings.locale;
export const ORDER_STATUSES = allSettings.orderStatuses;
export const SITE_TITLE = allSettings.siteTitle;
export const WC_ASSET_URL = allSettings.wcAssetUrl;
export const DEFAULT_DATE_RANGE = allSettings.defaultDateRange;

/**
 * Retrieves a setting value from the setting state.
 *
 * @export
 * @param {string}   name                         The identifier for the setting.
 * @param {mixed}    [fallback=false]             The value to use as a fallback
 *                                                if the setting is not in the
 *                                                state.
 * @param {function} [filter=( val ) => val]  	  A callback for filtering the
 *                                                value before it's returned.
 *                                                Receives both the found value
 *                                                (if it exists for the key) and
 *                                                the provided fallback arg.
 *
 * @returns {mixed}  The value present in the settings state for the given
 *                   name.
 */
export function getSetting( name, fallback = false, filter = val => val ) {
	const value = allSettings.hasOwnProperty( name ) ? allSettings[ name ] : fallback;
	return filter( value, fallback );
}

/**
 * Sets a value to a property on the settings state.
 *
 * @export
 * @param {string}   name                        The setting property key for the
 *                                               setting being mutated.
 * @param {mixed}    value                       The value to set.
 * @param {function} [filter=( val ) => val]     Allows for providing a callback
 *                                               to sanitize the setting (eg.
 *                                               ensure it's a number)
 */
export function setSetting( name, value, filter = val => val ) {
	allSettings[ name ] = filter( value );
}
