/**
 * External dependencies
 */

import { getAdminLink } from '@woocommerce/settings';

export const redirectToWCSSettings = () => {
	if ( window?.location ) {
		window.location.href = getAdminLink(
			'admin.php?page=wc-settings&tab=shipping&section=woocommerce-services-settings'
		);
	}
};
