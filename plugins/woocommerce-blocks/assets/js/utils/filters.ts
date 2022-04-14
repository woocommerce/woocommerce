/**
 * External dependencies
 */
import { getQueryArg } from '@wordpress/url';

/**
 * Returns specified parameter from URL
 *
 * @param {string} name Parameter you want the value of.
 */
export function getUrlParameter( name: string ) {
	if ( ! window ) {
		return null;
	}
	return getQueryArg( window.location.href, name );
}
