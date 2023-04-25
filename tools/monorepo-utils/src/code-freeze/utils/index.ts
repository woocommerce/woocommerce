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
