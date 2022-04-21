/**
 * External dependencies
 */
import { getQueryArg } from '@wordpress/url';

/**
 * Returns specified parameter from URL
 *
 * @param {string} name Parameter you want the value of.
 */

export const PREFIX_QUERY_ARG_QUERY_TYPE = 'query_type_';
export const PREFIX_QUERY_ARG_FILTER_TYPE = 'filter_';

export function getUrlParameter( name: string ) {
	if ( ! window ) {
		return null;
	}
	return getQueryArg( window.location.href, name );
}
