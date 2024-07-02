/**
 * External dependencies
 */
import { getQueryArg, getQueryArgs, addQueryArgs } from '@wordpress/url';
import { getSettingWithCoercion } from '@woocommerce/settings';
import { isBoolean } from '@woocommerce/types';

const filteringForPhpTemplate = getSettingWithCoercion(
	'isRenderingPhpTemplate',
	false,
	isBoolean
);

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

/**
 * Change the URL and reload the page if filtering for PHP templates.
 *
 * @param {string} newUrl New URL to be set.
 */
export function changeUrl( newUrl: string ) {
	if ( filteringForPhpTemplate ) {
		/**
		 * We may need to reset the current page when changing filters.
		 * This is because the current page may not exist for this set
		 * of filters and will 404 when the user navigates to it.
		 *
		 * There are different pagination formats to consider, as documented here:
		 * https://github.com/WordPress/gutenberg/blob/317eb8f14c8e1b81bf56972cca2694be250580e3/packages/block-library/src/query-pagination-numbers/index.php#L22-L85
		 */
		const url = new URL( newUrl );
		// When pretty permalinks are enabled, the page number may be in the path name.
		url.pathname = url.pathname.replace( /\/page\/[0-9]+/i, '' );
		// When plain permalinks are enabled, the page number may be in the "paged" query parameter.
		url.searchParams.delete( 'paged' );
		// On posts and pages the page number will be in a query parameter that
		// identifies which block we are paginating.
		url.searchParams.forEach( ( _, key ) => {
			if ( key.match( /^query(?:-[0-9]+)?-page$/ ) ) {
				url.searchParams.delete( key );
			}
		} );

		window.location.href = url.href;
	} else {
		window.history.replaceState( {}, '', newUrl );
	}
}

/**
 * Run the query params through buildQueryString to normalise the params.
 *
 * @param {string} url URL to encode the search param from.
 */
export const normalizeQueryParams = ( url: string ) => {
	const queryArgs = getQueryArgs( url );
	return addQueryArgs( url, queryArgs );
};
