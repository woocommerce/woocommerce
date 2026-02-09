/**
 * External dependencies
 */
import { parse, inc } from 'semver';

/**
 * Bumps the version according to WP rules.
 *
 * @param {string} version Version to increment
 * @return {string} Incremented version
 */
export const WPIncrement = ( version: string ): string => {
	const parsedVersion = parse( version );
	return inc( parsedVersion, parsedVersion.minor === 9 ? 'major' : 'minor' );
};

/**
 * Gets the major-minor of a given version number.
 *
 * @param {string} version Version to gather major minor from.
 * @return {string} major minor
 */
export const getMajorMinor = ( version: string ) => {
	const parsedVersion = parse( version );
	return `${ parsedVersion.major }.${ parsedVersion.minor }`;
};
