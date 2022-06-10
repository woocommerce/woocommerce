/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import {
	ReadOnlyProperties,
	PartialProduct,
	Product,
	ProductQuery,
} from './types';
import { WC_PRODUCT_NAMESPACE } from './constants';

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

function createProductSuccess( id: number, product: Partial< Product > ) {
	return {
		type: TYPES.CREATE_PRODUCT_SUCCESS as const,
		id,
		product,
	};
}

export function createProductError(
	query: Partial< Product >,
	error: unknown
) {
	return {
		type: TYPES.CREATE_PRODUCT_ERROR as const,
		query,
		error,
	};
}

function updateProductSuccess( id: number, product: Partial< Product > ) {
	return {
		type: TYPES.UPDATE_PRODUCT_SUCCESS as const,
		id,
		product,
	};
}

export function updateProductError( id: number, error: unknown ) {
	return {
		type: TYPES.UPDATE_PRODUCT_ERROR as const,
		id,
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

export function* createProduct( data: Omit< Product, ReadOnlyProperties > ) {
	try {
		const product: Product = yield apiFetch( {
			path: WC_PRODUCT_NAMESPACE,
			method: 'POST',
			data,
		} );

		yield createProductSuccess( product.id, product );
		return product;
	} catch ( error ) {
		yield createProductError( data, error );
		throw error;
	}
}

export function* updateProduct(
	id: number,
	data: Omit< Product, ReadOnlyProperties >
) {
	try {
		const product: Product = yield apiFetch( {
			path: `${ WC_PRODUCT_NAMESPACE }/${ id }`,
			method: 'PUT',
			data,
		} );

		yield updateProductSuccess( product.id, product );
		return product;
	} catch ( error ) {
		yield updateProductError( id, error );
		throw error;
	}
}

export type Actions = ReturnType<
	| typeof createProductError
	| typeof createProductSuccess
	| typeof getProductSuccess
	| typeof getProductError
	| typeof getProductsSuccess
	| typeof getProductsError
	| typeof getProductsTotalCountSuccess
	| typeof getProductsTotalCountError
	| typeof updateProductError
	| typeof updateProductSuccess
>;

export type ActionDispatchers = DispatchFromMap< {
	createProduct: typeof createProduct;
	updateProduct: typeof updateProduct;
} >;
