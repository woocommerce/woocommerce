/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { PartialProduct, ProductQuery } from './types';

export function setProduct( id: number, product: PartialProduct ) {
	return {
		type: TYPES.SET_PRODUCT as const,
		id,
		product,
	};
}

export function setProducts(
	query: Partial< ProductQuery >,
	products: PartialProduct[],
	totalCount: number
) {
	return {
		type: TYPES.SET_PRODUCTS as const,
		products,
		query,
		totalCount,
	};
}

export function setProductsTotalCount(
	query: Partial< ProductQuery >,
	totalCount: number
) {
	return {
		type: TYPES.SET_PRODUCTS_TOTAL_COUNT as const,
		query,
		totalCount,
	};
}

export function setError( query: Partial< ProductQuery >, error: unknown ) {
	return {
		type: TYPES.SET_ERROR as const,
		query,
		error,
	};
}

export type Actions = ReturnType<
	| typeof setProduct
	| typeof setProducts
	| typeof setProductsTotalCount
	| typeof setError
>;
