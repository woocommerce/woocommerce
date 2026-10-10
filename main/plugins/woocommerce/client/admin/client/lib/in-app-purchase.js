/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

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
		'wccom-site': getAdminSetting( 'siteUrl' ),
		// If the site is installed in a directory the directory must be included in the back param path.
		'wccom-back': pathname + search,
		'wccom-woo-version': getSetting( 'wcVersion' ),
		'wccom-connect-nonce': connectNonce,
		...queryArgs,
	};

	return addQueryArgs( url, queryArgs );
};
