/**
 * External dependencies
 */
import { SETTINGS_STORE_NAME, ITEMS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { DEFAULT_ACTIONABLE_STATUSES } from '../../../analytics/settings/config';

export function getUnreadOrders( select ) {
	const { getItemsTotalCount, getItemsError, isResolving } = select(
		ITEMS_STORE_NAME
	);
	const { getSetting: getMutableSetting } = select( SETTINGS_STORE_NAME );
	const {
		woocommerce_actionable_order_statuses: orderStatuses = DEFAULT_ACTIONABLE_STATUSES,
	} = getMutableSetting( 'wc_admin', 'wcAdminSettings', {} );

	if ( ! orderStatuses.length ) {
		return false;
	}

	const ordersQuery = {
		page: 1,
		per_page: 1, // Core endpoint requires per_page > 0.
		status: orderStatuses,
		_fields: [ 'id' ],
	};

	// Disable eslint rule requiring `latestNote` to be defined below because the next two statements
	// depend on `getItemsTotalCount` to have been called.
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const totalOrders = getItemsTotalCount( 'orders', ordersQuery );
	const isError = Boolean( getItemsError( 'orders', ordersQuery ) );
	const isRequesting = isResolving( 'getItemsTotalCount', [
		'orders',
		ordersQuery,
	] );

	if ( isError || isRequesting ) {
		return null;
	}

	return totalOrders;
}
