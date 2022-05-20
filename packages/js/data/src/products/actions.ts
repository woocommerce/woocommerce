/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { PartialProduct, ProductQuery } from './types';

export function getProductSuccess( id: number, product: PartialProduct ) {
	return {
		type: TYPES.GET_PRODUCT_SUCCESS as const,
		id,
		product,
	};
}

export function getProductError(
	query: Partial< ProductQuery >,
	error: unknown
) {
	return {
		type: TYPES.GET_PRODUCT_ERROR as const,
		query,
		error,
	};
}

export function getProductsSuccess(
	query: Partial< ProductQuery >,
	products: PartialProduct[],
	totalCount: number
) {
	return {
		type: TYPES.GET_PRODUCTS_SUCCESS as const,
		products,
		query,
		totalCount,
	};
}

export function getProductsError(
	query: Partial< ProductQuery >,
	error: unknown
) {
	return {
		type: TYPES.GET_PRODUCTS_ERROR as const,
		query,
		error,
	};
}

export function getProductsTotalCountSuccess(
	query: Partial< ProductQuery >,
	totalCount: number
) {
	return {
		type: TYPES.GET_PRODUCTS_TOTAL_COUNT_SUCCESS as const,
		query,
		totalCount,
	};
}

export function getProductsTotalCountError(
	query: Partial< ProductQuery >,
	error: unknown
) {
	return {
		type: TYPES.GET_PRODUCTS_TOTAL_COUNT_ERROR as const,
		query,
		error,
	};
}

export type Actions = ReturnType<
	| typeof getProductSuccess
	| typeof getProductError
	| typeof getProductsSuccess
	| typeof getProductsError
	| typeof getProductsTotalCountSuccess
	| typeof getProductsTotalCountError
>;
