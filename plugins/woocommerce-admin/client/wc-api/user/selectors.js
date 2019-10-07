/** @format */

/**
 * Internal dependencies
 */
import { DEFAULT_REQUIREMENT } from '../constants';

/**
 * WooCommerce dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';

const getCurrentUserData = ( getResource, requireResource ) => (
	requirement = DEFAULT_REQUIREMENT
) => {
	return getSetting(
		'currentUserData',
		{},
		cud => requireResource( requirement, 'current-user-data' ).data || cud
	);
};

export default {
	getCurrentUserData,
};
