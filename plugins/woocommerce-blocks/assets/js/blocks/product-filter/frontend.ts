/**
 * External dependencies
 */
import { navigate as navigateFn } from '@woocommerce/interactivity';
import { getSetting } from '@woocommerce/settings';

const isBlockTheme = getSetting< boolean >( 'isBlockTheme' );
const isProductArchive = getSetting< boolean >( 'isProductArchive' );

export function navigate( href: string, options = {} ) {
	if ( ! isBlockTheme && isProductArchive ) {
		return ( window.location.href = href );
	}
	return navigateFn( href, options );
}
