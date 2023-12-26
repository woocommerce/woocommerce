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
		 * We want to remove page number from URL whenever filters are changed.
		 * This will move the user to the first page of results.
		 *
		 * There are following page number formats:
		 * 1. query-{number}-page={number} (ex. query-1-page=2)
		 * 	- ref: https://github.com/WordPress/gutenberg/blob/5693a62214b6c76d3dc5f3f69d8aad187748af79/packages/block-library/src/query-pagination-numbers/index.php#L18
		 * 2. query-page={number} (ex. query-page=2)
		 * 	- ref: same as above
		 * 3. page/{number} (ex. page/2) (Default WordPress pagination format)
		 */
		newUrl = newUrl.replace(
			/(?:query-(?:\d+-)?page=(\d+))|(?:page\/(\d+))/g,
			''
		);

		/**
		 * If the URL ends with '?', we remove the trailing '?' from the URL.
		 * The trailing '?' in a URL is unnecessary and can cause the page to
		 * reload, which can negatively affect performance. By removing the '?',
		 * we prevent this unnecessary reload. This is safe to do even if there
		 * are query parameters, as they will not be affected by the removal
		 * of a trailing '?'.
		 */
		if ( newUrl.endsWith( '?' ) ) {
			newUrl = newUrl.slice( 0, -1 );
		}

		window.location.href = newUrl;
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
