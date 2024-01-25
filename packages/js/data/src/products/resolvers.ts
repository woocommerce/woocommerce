/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

import {
	apiFetch as controlsApiFetch,
	dispatch as deprecatedDispatch,
	select,
} from '@wordpress/data-controls';
import { controls } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	STORE_NAME,
	WC_PRODUCT_NAMESPACE,
	WC_V3_ENDPOINT_SUGGESTED_PRODUCTS,
} from './constants';
import { GetSuggestedProductsOptions, Product, ProductQuery } from './types';
import {
	getProductError,
	getProductsError,
	getProductsSuccess,
	getProductsTotalCountError,
	getProductsTotalCountSuccess,
	getProductSuccess,
} from './actions';
import { request } from '../utils';
import { createIdFromOptions } from './utils';

const dispatch =
	controls && controls.dispatch ? controls.dispatch : deprecatedDispatch;
const resolveSelect =
	controls && controls.resolveSelect ? controls.resolveSelect : select;

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
		const product: Product = yield controlsApiFetch( {
			path: addQueryArgs( `${ WC_PRODUCT_NAMESPACE }/${ productId }`, {
				context: 'edit',
			} ),
			method: 'GET',
		} );

		yield getProductSuccess( productId, product );

		yield dispatch( STORE_NAME, 'finishResolution', 'getPermalinkParts', [
			productId,
		] );

		return product;
	} catch ( error ) {
		yield getProductError( productId, error );
		throw error;
	}
}

export function* getRelatedProducts( productId: number ) {
	try {
		// Get the product.
		const product: Product = yield resolveSelect(
			STORE_NAME,
			'getProduct',
			productId
		);

		// Pick the related products IDs.
		const relatedProductsIds = product.related_ids;
		if ( ! relatedProductsIds?.length ) {
			return [];
		}

		// Get the related products.
		const relatedProducts: Product[] = yield resolveSelect(
			STORE_NAME,
			'getProducts',
			{
				include: relatedProductsIds,
			}
		);

		return relatedProducts;
	} catch ( error ) {
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

export function* getPermalinkParts( productId: number ) {
	yield resolveSelect( STORE_NAME, 'getProduct', [ productId ] );
}

export const getSuggestedProducts =
	( options: GetSuggestedProductsOptions ) =>
	// @ts-expect-error There are no types for this.
	async ( { dispatch: contextualDispatch } ) => {
		const key = createIdFromOptions( options );

		const data = await apiFetch( {
			path: addQueryArgs( WC_V3_ENDPOINT_SUGGESTED_PRODUCTS, options ),
		} );

		contextualDispatch.setSuggestedProductAction( key, data );
	};
