/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * WooCommerce dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Returns an in-app-purchase URL.
 *
 * @param {string} url
 * @param {Object} queryArgs
 * @return {string} url with in-app-purchase query parameters
 */
export const getInAppPurchaseUrl = ( url, queryArgs = {} ) => {
	const { pathname, search } = window.location;
	const connectNonce = getSetting( 'connectNonce', '' );
	queryArgs = {
		'wccom-site': getSetting( 'siteUrl' ),
		// If the site is installed in a directory the directory must be included in the back param path.
		'wccom-back': pathname + search,
		'wccom-woo-version': getSetting( 'wcVersion' ),
		'wccom-connect-nonce': connectNonce,
		...queryArgs,
	};

	return addQueryArgs( url, queryArgs );
};
