/**
 * External dependencies
 */
import { navigate as navigateFn } from '@woocommerce/interactivity';
import { getSetting } from '@woocommerce/settings';

const isBlockTheme = getSetting< boolean >( 'isBlockTheme' );
const isProductArchive = getSetting< boolean >( 'isProductArchive' );
const needsRefresh = getSetting< boolean >(
	'needsRefreshForInteractivityAPI',
	false
);

export function navigate( href: string, options = {} ) {
	/**
	 * We may need to reset the current page when changing filters.
	 * This is because the current page may not exist for this set
	 * of filters and will 404 when the user navigates to it.
	 *
	 * There are different pagination formats to consider, as documented here:
	 * https://github.com/WordPress/gutenberg/blob/317eb8f14c8e1b81bf56972cca2694be250580e3/packages/block-library/src/query-pagination-numbers/index.php#L22-L85
	 */
	const url = new URL( href );
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
	// Make sure to update the href with the changes.
	href = url.href;

	if ( needsRefresh || ( ! isBlockTheme && isProductArchive ) ) {
		return ( window.location.href = href );
	}
	return navigateFn( href, options );
}
