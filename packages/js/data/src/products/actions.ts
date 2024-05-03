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

export function getProductError( productId: number, error: unknown ) {
	return {
		type: TYPES.GET_PRODUCT_ERROR as const,
		productId,
		error,
	};
}

function createProductStart() {
	return {
		type: TYPES.CREATE_PRODUCT_START as const,
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

function updateProductStart( id: number ) {
	return {
		type: TYPES.UPDATE_PRODUCT_START as const,
		id,
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

export function* createProduct(
	data: Partial< Omit< Product, ReadOnlyProperties > >
): Generator< unknown, Product, Product > {
	yield createProductStart();
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
	data: Partial< Omit< Product, ReadOnlyProperties > >
): Generator< unknown, Product, Product > {
	yield updateProductStart( id );
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

export function deleteProductStart( id: number ) {
	return {
		type: TYPES.DELETE_PRODUCT_START as const,
		id,
	};
}

export function deleteProductSuccess(
	id: number,
	product: PartialProduct,
	force: boolean
) {
	return {
		type: TYPES.DELETE_PRODUCT_SUCCESS as const,
		id,
		product,
		force,
	};
}

export function deleteProductError( id: number, error: unknown ) {
	return {
		type: TYPES.DELETE_PRODUCT_ERROR as const,
		id,
		error,
	};
}

export function* deleteProduct(
	id: number,
	force = false
): Generator< unknown, Product, Product > {
	yield deleteProductStart( id );
	try {
		const url = force
			? `${ WC_PRODUCT_NAMESPACE }/${ id }?force=true`
			: `${ WC_PRODUCT_NAMESPACE }/${ id }`;

		const product: Product = yield apiFetch( {
			path: url,
			method: 'DELETE',
		} );

		yield deleteProductSuccess( product.id, product, force );
		return product;
	} catch ( error ) {
		yield deleteProductError( id, error );
		throw error;
	}
}

export function setSuggestedProductAction( key: string, items: Product[] ) {
	return {
		type: TYPES.SET_SUGGESTED_PRODUCTS as const,
		key,
		items,
	};
}

export type Actions = ReturnType<
	| typeof createProductStart
	| typeof createProductError
	| typeof createProductSuccess
	| typeof getProductSuccess
	| typeof getProductError
	| typeof getProductsSuccess
	| typeof getProductsError
	| typeof getProductsTotalCountSuccess
	| typeof getProductsTotalCountError
	| typeof updateProductStart
	| typeof updateProductError
	| typeof updateProductSuccess
	| typeof deleteProductStart
	| typeof deleteProductSuccess
	| typeof deleteProductError
	| typeof setSuggestedProductAction
>;

export type ActionDispatchers = DispatchFromMap< {
	createProduct: typeof createProduct;
	updateProduct: typeof updateProduct;
	deleteProduct: typeof deleteProduct;
} >;
