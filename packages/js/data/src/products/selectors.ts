/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import {
	getProductResourceName,
	getTotalProductCountResourceName,
} from './utils';
import { WPDataSelector, WPDataSelectors } from '../types';
import { ProductState } from './reducer';
import { ProductQuery } from './types';

export const getProducts = createSelector(
	( state: ProductState, query: ProductQuery, defaultValue = undefined ) => {
		const resourceName = getProductResourceName( query );
		const ids = state.products[ resourceName ]
			? state.products[ resourceName ].data
			: undefined;
		if ( ! ids ) {
			return defaultValue;
		}
		return ids.map( ( id ) => {
			return state.data[ id ];
		} );
	},
	( state, query ) => {
		const resourceName = getProductResourceName( query );
		return [ state.products[ resourceName ] ];
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

export type ProductsSelectors = {
	getProducts: WPDataSelector< typeof getProducts >;
	getProductsTotalCount: WPDataSelector< typeof getProductsTotalCount >;
	getProductsError: WPDataSelector< typeof getProductsError >;
} & WPDataSelectors;
