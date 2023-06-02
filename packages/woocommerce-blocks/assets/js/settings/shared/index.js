/**
 * External dependencies
 */
import compareVersions from 'compare-versions';

/**
 * Internal dependencies
 */
import { getSetting } from './get-setting';

export * from './default-constants';
import '../../filters/exclude-draft-status-from-analytics';

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
 * @param {string} setting Setting name (e.g. wpVersion or wcVersion).
 * @param {string} version Version to compare.
 * @param {string} operator Comparison operator.
 */
const compareVersionSettingIgnorePrerelease = (
	setting,
	version,
	operator
) => {
	let replacement = getSetting( setting, '' ).replace(
		/-[a-zA-Z0-9]*[\-]*/,
		'.0-rc.'
	);
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
 *
 * @param {string} version Version to use to compare against the current wpVersion.
 * @param {string} [operator='='] Operator to use in the comparison.
 */
export const isWpVersion = ( version, operator = '=' ) => {
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
 *
 * @param {string} version Version to use to compare against the current wcVersion.
 * @param {string} [operator='='] Operator to use in the comparison.
 */
export const isWcVersion = ( version, operator = '=' ) => {
	return compareVersionSettingIgnorePrerelease(
		'wcVersion',
		version,
		operator
	);
};

export { compareVersions, getSetting };

/**
 * Returns a string with the site's wp-admin URL appended. JS version of `admin_url`.
 *
 * @param {string} path Relative path.
 * @return {string} Full admin URL.
 */
export const getAdminLink = ( path ) => getSetting( 'adminUrl' ) + path;
