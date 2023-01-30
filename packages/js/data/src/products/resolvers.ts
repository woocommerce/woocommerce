/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { WC_PRODUCT_NAMESPACE } from './constants';
import { Product, ProductQuery } from './types';
import {
	getProductError,
	getProductsError,
	getProductsSuccess,
	getProductsTotalCountError,
	getProductsTotalCountSuccess,
	getProductSuccess,
} from './actions';
import { request } from '../utils';

export function* getProducts( query: Partial< ProductQuery > ) {
	// id is always required.
	const productsQuery = {
		...query,
	};
	if (
		productsQuery &&
		productsQuery._fields &&
		! productsQuery._fields.includes( 'id' )
	) {
		productsQuery._fields = [ 'id', ...productsQuery._fields ];
	}
	try {
		const { items, totalCount }: { items: Product[]; totalCount: number } =
			yield request< ProductQuery, Product >(
				WC_PRODUCT_NAMESPACE,
				productsQuery
			);
		yield getProductsTotalCountSuccess( query, totalCount );
		yield getProductsSuccess( query, items, totalCount );
		return items;
	} catch ( error ) {
		yield getProductsError( query, error );
		throw error;
	}
}

export function* getProduct( productId: number ) {
	try {
		const product: Product = yield apiFetch( {
			path: addQueryArgs( `${ WC_PRODUCT_NAMESPACE }/${ productId }`, {
				context: 'edit',
			} ),
			method: 'GET',
		} );

		yield getProductSuccess( productId, product );
		return product;
	} catch ( error ) {
		yield getProductError( productId, error );
		throw error;
	}
}

export function* getProductsTotalCount( query: Partial< ProductQuery > ) {
	try {
		const totalsQuery = {
			...query,
			page: 1,
			per_page: 1,
		};
		const { totalCount } = yield request< ProductQuery, Product >(
			WC_PRODUCT_NAMESPACE,
			totalsQuery
		);
		yield getProductsTotalCountSuccess( query, totalCount );
		return totalCount;
	} catch ( error ) {
		yield getProductsTotalCountError( query, error );
		throw error;
	}
}
