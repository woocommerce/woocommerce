/**
 * External dependencies
 */
import { SETTINGS_STORE_NAME, ITEMS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { DEFAULT_ACTIONABLE_STATUSES } from '../../../analytics/settings/config';

export function getUnreadOrders( select, orderStatuses ) {
	const { getItemsTotalCount, getItemsError, isResolving } = select(
		ITEMS_STORE_NAME
	);

	if ( ! orderStatuses.length ) {
		return 0;
	}

	const ordersQuery = {
		page: 1,
		per_page: 1, // Core endpoint requires per_page > 0.
		status: orderStatuses,
		_fields: [ 'id' ],
	};

	const defaultValue = null;

	// Disable eslint rule requiring `totalOrders` to be defined below because the next two statements
	// depend on `getItemsTotalCount` to have been called.
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const totalOrders = getItemsTotalCount(
		'orders',
		ordersQuery,
		defaultValue
	);
	const isError = Boolean( getItemsError( 'orders', ordersQuery ) );
	const isRequesting = isResolving( 'getItemsTotalCount', [
		'orders',
		ordersQuery,
		defaultValue,
	] );

	if ( isError || isRequesting ) {
		return null;
	}

	return totalOrders;
}

export function getOrderStatuses( select ) {
	const { getSetting: getMutableSetting } = select( SETTINGS_STORE_NAME );
	const {
		woocommerce_actionable_order_statuses: orderStatuses = DEFAULT_ACTIONABLE_STATUSES,
	} = getMutableSetting( 'wc_admin', 'wcAdminSettings', {} );
	return orderStatuses;
}

export function getLowStockCount( select ) {
	const { getItemsTotalCount, getItemsError, isResolving } = select(
		ITEMS_STORE_NAME
	);

	const productsQuery = {
		page: 1,
		per_page: 1,
		low_in_stock: true,
		status: 'publish',
		_fields: [ 'id' ],
	};

	const defaultValue = 0;

	// Disable eslint rule requiring `totalLowStockProducts` to be defined below because the next two statements
	// depend on `getItemsTotalCount` to have been called.
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const totalLowStockProducts = getItemsTotalCount(
		'products',
		productsQuery,
		defaultValue
	);

	const isError = Boolean( getItemsError( 'products', productsQuery ) );
	const isRequesting = isResolving( 'getItemsTotalCount', [
		'products',
		productsQuery,
		defaultValue,
	] );

	if ( isError || isRequesting ) {
		return null;
	}

	return totalLowStockProducts;
}
