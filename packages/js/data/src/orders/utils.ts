/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import { OrdersQuery } from './types';

const PRODUCT_PREFIX = 'order';

/**
 * Generate a resource name for orders.
 *
 * @param {Object} query Query for orders.
 * @return {string} Resource name for orders.
 */
export function getOrderResourceName( query: Partial< OrdersQuery > ) {
	return getResourceName( PRODUCT_PREFIX, query );
}

/**
 * Generate a resource name for order totals count.
 *
 * It omits query parameters from the identifier that don't affect
 * totals values like pagination and response field filtering.
 *
 * @param {Object} query Query for order totals count.
 * @return {string} Resource name for order totals.
 */
export function getTotalOrderCountResourceName(
	query: Partial< OrdersQuery >
) {
	const { _fields, page, per_page, ...totalsQuery } = query;

	return getOrderResourceName( totalsQuery );
}
