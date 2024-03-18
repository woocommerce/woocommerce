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
	// Remove the page number if we're changing the filters to prevent
	// a 404 if the page does not exist with a given set of filters.
	if ( href.includes( 'paged' ) ) {
		const url = new URL( href );
		url.searchParams.delete( 'paged' );
		href = url.href;
	}

	if ( needsRefresh || ( ! isBlockTheme && isProductArchive ) ) {
		return ( window.location.href = href );
	}
	return navigateFn( href, options );
}
