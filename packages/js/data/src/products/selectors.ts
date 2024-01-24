/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import {
	createIdFromOptions,
	getProductResourceName,
	getTotalProductCountResourceName,
} from './utils';
import { WPDataSelector, WPDataSelectors } from '../types';
import { ProductState } from './reducer';
import {
	GetSuggestedProductsOptions,
	PartialProduct,
	ProductQuery,
} from './types';
import { ActionDispatchers } from './actions';
import { PERMALINK_PRODUCT_REGEX } from './constants';

export const getProduct = (
	state: ProductState,
	productId: number,
	defaultValue = undefined
) => {
	return state.data[ productId ] || defaultValue;
};

export const getProducts = createSelector(
	( state: ProductState, query: ProductQuery, defaultValue = undefined ) => {
		const resourceName = getProductResourceName( query );
		const ids = state.products[ resourceName ]
			? state.products[ resourceName ].data
			: undefined;
		if ( ! ids ) {
			return defaultValue;
		}
		if ( query && typeof query._fields !== 'undefined' ) {
			const fields = query._fields;

			return ids.map( ( id ) => {
				return fields.reduce(
					(
						product: PartialProduct,
						field: keyof PartialProduct
					) => {
						return {
							...product,
							[ field ]: state.data[ id ][ field ],
						};
					},
					{} as PartialProduct
				);
			} );
		}
		return ids.map( ( id ) => {
			return state.data[ id ];
		} );
	},
	( state, query ) => {
		const resourceName = getProductResourceName( query );
		const ids = state.products[ resourceName ]
			? state.products[ resourceName ].data
			: undefined;
		return [
			state.products[ resourceName ],
			...( ids || [] ).map( ( id: number ) => {
				return state.data[ id ];
			} ),
		];
	}
);

export const getProductsTotalCount = (
	state: ProductState,
	query: ProductQuery,
	defaultValue = undefined
) => {
	const resourceName = getTotalProductCountResourceName( query );
	const totalCount = state.productsCount.hasOwnProperty( resourceName )
		? state.productsCount[ resourceName ]
		: defaultValue;
	return totalCount;
};

export const getProductsError = (
	state: ProductState,
	query: ProductQuery
) => {
	const resourceName = getProductResourceName( query );
	return state.errors[ resourceName ];
};

export const getCreateProductError = (
	state: ProductState,
	query: ProductQuery
) => {
	const resourceName = getProductResourceName( query );
	return state.errors[ resourceName ];
};

export const getUpdateProductError = (
	state: ProductState,
	id: number,
	query: ProductQuery
) => {
	const resourceName = getProductResourceName( query );
	return state.errors[ `update/${ id }/${ resourceName }` ];
};

export const getDeleteProductError = ( state: ProductState, id: number ) => {
	return state.errors[ `delete/${ id }` ];
};

export const isPending = (
	state: ProductState,
	action: keyof ActionDispatchers,
	productId?: number
) => {
	if ( productId !== undefined && action !== 'createProduct' ) {
		return state.pending[ action ]?.[ productId ] || false;
	} else if ( action === 'createProduct' ) {
		return state.pending[ action ] || false;
	}
	return false;
};

export const getPermalinkParts = createSelector(
	( state: ProductState, productId: number ) => {
		const product = state.data[ productId ];

		if ( product && product.permalink_template ) {
			const postName = product.slug || product.generated_slug;

			const [ prefix, suffix ] = product.permalink_template.split(
				PERMALINK_PRODUCT_REGEX
			);

			return {
				prefix,
				postName,
				suffix,
			};
		}
		return null;
	},
	( state, productId ) => {
		return [ state.data[ productId ] ];
	}
);

/**
 * Returns an array of related products for a given product ID.
 *
 * @param {ProductState} state     - The current state.
 * @param {number}       productId - The product ID.
 * @return {PartialProduct[]}        The related products.
 */
export const getRelatedProducts = createSelector(
	( state: ProductState, productId: number ): PartialProduct[] => {
		const product = state.data[ productId ];
		if ( ! product?.related_ids ) {
			return [];
		}

		const relatedProducts = getProducts( state, {
			include: product.related_ids,
		} );

		return relatedProducts || [];
	},
	( state, productId ) => {
		return [ state.data[ productId ] ];
	}
);

/**
 * Return an array of suggested products the
 * given options.
 *
 * @param {ProductState}                state   - The current state.
 * @param {GetSuggestedProductsOptions} options - The options.
 * @return {PartialProduct[]}                     The suggested products.
 */
export function getSuggestedProducts(
	state: ProductState,
	options: GetSuggestedProductsOptions
): PartialProduct[] {
	const key = createIdFromOptions( options );
	if ( ! state.suggestedProducts[ key ] ) {
		return [];
	}

	return state.suggestedProducts[ key ].items;
}

export type ProductsSelectors = {
	getCreateProductError: WPDataSelector< typeof getCreateProductError >;
	getProduct: WPDataSelector< typeof getProduct >;
	getProducts: WPDataSelector< typeof getProducts >;
	getProductsTotalCount: WPDataSelector< typeof getProductsTotalCount >;
	getProductsError: WPDataSelector< typeof getProductsError >;
	isPending: WPDataSelector< typeof isPending >;
	getPermalinkParts: WPDataSelector< typeof getPermalinkParts >;
	getRelatedProducts: WPDataSelector< typeof getRelatedProducts >;
	getSuggestedProducts: WPDataSelector< typeof getSuggestedProducts >;
} & WPDataSelectors;
