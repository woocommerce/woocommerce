/**
 * External dependencies
 */
import { navigate as navigateFn } from '@woocommerce/interactivity';
import { getSetting } from '@woocommerce/settings';

const isBlockTheme = getSetting< boolean >( 'isBlockTheme' );
const isProductArchive = getSetting< boolean >( 'isProductArchive' );
const needsRefresh = getSetting< boolean >( 'needsRefresh' );

export function navigate( href: string, options = {} ) {
	if ( needsRefresh || ( ! isBlockTheme && isProductArchive ) ) {
		return ( window.location.href = href );
	}
	return navigateFn( href, options );
}
