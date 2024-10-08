/**
 * External dependencies
 */
import compareVersions from 'compare-versions';
import type { SymbolPosition } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { allSettings } from './settings-init';

/**
 * Retrieves a setting value from the setting state.
 *
 * If a setting with key `name` does not exist or is undefined,
 * the `fallback` will be returned instead. An optional `filter`
 * callback can be passed to format the returned value.
 */
export const getSetting = < T >(
	name: string,
	fallback: unknown = false,
	filter = ( val: unknown, fb: unknown ) =>
		typeof val !== 'undefined' ? val : fb
): T => {
	let value = fallback;

	if ( name in allSettings ) {
		value = allSettings[ name ];
	} else if ( name.includes( '_data' ) ) {
		// This handles back compat with payment data _data properties after the move to camelCase and the dedicated
		// paymentMethodData setting.
		const nameWithoutData = name.replace( '_data', '' );
		const paymentMethodData = getSetting(
			'paymentMethodData',
			{}
		) as Record< string, unknown >;

		value =
			nameWithoutData in paymentMethodData
				? paymentMethodData[ nameWithoutData ]
				: fallback;
	}

	return filter( value, fallback ) as T;
};

export const getSettingWithCoercion = < T >(
	name: string,
	fallback: T,
	typeguard: ( val: unknown, fb: unknown ) => val is T
): T => {
	const value = name in allSettings ? allSettings[ name ] : fallback;
	return typeguard( value, fallback ) ? value : fallback;
};

/**
 * Note: this attempts to coerce the wpVersion to a semver for comparison
 * This will result in dropping any beta/rc values.
 *
 * `5.3-beta1-4252` would get converted to `5.3.0-rc.4252`
 * `5.3-beta1` would get converted to `5.3.0-rc`.
 * `5.3` would not be touched.
 *
 * For the purpose of these comparisons all pre-release versions are normalized
 * to `rc`.
 *
 * @param {string}                          setting  Setting name (e.g. wpVersion or wcVersion).
 * @param {string}                          version  Version to compare.
 * @param {compareVersions.CompareOperator} operator Comparison operator.
 */
const compareVersionSettingIgnorePrerelease = (
	setting: string,
	version: string,
	operator: compareVersions.CompareOperator
): boolean => {
	const settingValue = getSetting( setting, '' ) as string;
	let replacement = settingValue.replace( /-[a-zA-Z0-9]*[\-]*/, '.0-rc.' );
	replacement = replacement.endsWith( '.' )
		? replacement.substring( 0, replacement.length - 1 )
		: replacement;
	return compareVersions.compare( replacement, version, operator );
};

/**
 * Compare the current WP version with the provided `version` param using the
 * `operator`.
 *
 * For example `isWpVersion( '5.6', '<=' )` returns true if the site WP version
 * is smaller or equal than `5.6` .
 */
export const isWpVersion = (
	version: string,
	operator: compareVersions.CompareOperator = '='
): boolean => {
	return compareVersionSettingIgnorePrerelease(
		'wpVersion',
		version,
		operator
	);
};

/**
 * Compare the current WC version with the provided `version` param using the
 * `operator`.
 *
 * For example `isWcVersion( '4.9.0', '<=' )` returns true if the site WC version
 * is smaller or equal than `4.9`.
 */
export const isWcVersion = (
	version: string,
	operator: compareVersions.CompareOperator = '='
): boolean => {
	return compareVersionSettingIgnorePrerelease(
		'wcVersion',
		version,
		operator
	);
};

/**
 * Returns a string with the site's wp-admin URL appended. JS version of `admin_url`.
 *
 * @param {string} path Relative path.
 * @return {string} Full admin URL.
 */
export const getAdminLink = ( path: string ): string =>
	getSetting( 'adminUrl' ) + path;

/**
 * Get payment method data from the paymentMethodData setting.
 */
export const getPaymentMethodData = (
	paymentMethodId: string,
	defaultValue: null | unknown = null
) => {
	const paymentMethodData = getSetting( 'paymentMethodData', {} ) as Record<
		string,
		unknown
	>;
	return paymentMethodData[ paymentMethodId ] ?? defaultValue;
};

/**
 * Get currency prefix.
 */
export const getCurrencyPrefix = (
	symbol: string,
	symbolPosition: SymbolPosition
): string => {
	const prefixes = {
		left: symbol,
		left_space: symbol + ' ',
		right: '',
		right_space: '',
	};
	return prefixes[ symbolPosition ] || '';
};

/**
 * Get currency suffix.
 */
export const getCurrencySuffix = (
	symbol: string,
	symbolPosition: SymbolPosition
): string => {
	const suffixes = {
		left: '',
		left_space: '',
		right: symbol,
		right_space: ' ' + symbol,
	};
	return suffixes[ symbolPosition ] || '';
};
