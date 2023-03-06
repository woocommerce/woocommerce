/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import { ProductQuery } from './types';

const PRODUCT_PREFIX = 'product';

/**
 * Generate a resource name for products.
 *
 * @param {Object} query Query for products.
 * @return {string} Resource name for products.
 */
export function getProductResourceName( query: Partial< ProductQuery > ) {
	return getResourceName( PRODUCT_PREFIX, query );
}

/**
 * Generate a resource name for product totals count.
 *
 * It omits query parameters from the identifier that don't affect
 * totals values like pagination and response field filtering.
 *
 * @param {Object} query Query for product totals count.
 * @return {string} Resource name for product totals.
 */
export function getTotalProductCountResourceName(
	query: Partial< ProductQuery >
) {
	const { _fields, page, per_page, ...totalsQuery } = query;
	return getProductResourceName( totalsQuery );
}
