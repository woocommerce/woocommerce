/**
 * External dependencies
 */
import debug from 'debug';

/**
 * Internal dependencies
 */
import { isDevelopmentMode } from './utils';

/**
 * Module variables
 */
const tracksDebug = debug( 'wc-admin:tracks:stats' );

/**
 * Builds a querystring from the given group and name parameters.
 *
 * This will automatically add the prefix `x_woocommerce-` to the group name.
 *
 * @param {Record<string, string> | string} group  - The group of stats or a single stat name.
 * @param {string}                          [name] - The name of the stat if group is a string.
 * @return {string} The constructed querystring.
 */
function buildQuerystring(
	group: Record< string, string > | string,
	name?: string
): string {
	let uriComponent = '';

	if ( typeof group === 'object' ) {
		for ( const key in group ) {
			uriComponent +=
				'&x_woocommerce-' +
				encodeURIComponent( key ) +
				'=' +
				encodeURIComponent( group[ key ] );
		}
	} else {
		uriComponent =
			'&x_woocommerce-' +
			encodeURIComponent( group ) +
			'=' +
			encodeURIComponent( name as string );
	}

	return uriComponent;
}

/**
 * Bumps a stat or group of stats.
 *
 * @param {Record<string, string> | string} group  - The group of stats or a single stat name.
 * @param {string}                          [name] - The name of the stat if group is a string.
 * @return {boolean} True if the stat was successfully bumped, false otherwise.
 */
export function bumpStat(
	group: Record< string, string > | string,
	name?: string
): boolean {
	if ( typeof group === 'object' ) {
		tracksDebug( 'Bumping stats %o', group );
	} else {
		tracksDebug( 'Bumping stat %s:%s', group, name );
	}

	if ( isDevelopmentMode ) {
		return false;
	}

	const uriComponent = buildQuerystring( group, name );
	new window.Image().src =
		document.location.protocol +
		'//pixel.wp.com/g.gif?v=wpcom-no-pv' +
		uriComponent +
		'&t=' +
		Math.random();

	return true;
}
