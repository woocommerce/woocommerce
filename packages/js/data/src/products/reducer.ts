/**
 * External dependencies
 */
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Actions } from './actions';
import { PartialProduct } from './types';
import {
	getProductResourceName,
	getTotalProductCountResourceName,
} from './utils';

export type ProductState = {
	products: Record<
		string,
		{
			data: number[];
		}
	>;
	productsCount: Record< string, number >;
	errors: Record< string, unknown >;
	data: Record< number, PartialProduct >;
};

const reducer: Reducer< ProductState, Actions > = (
	state = {
		products: {},
		productsCount: {},
		errors: {},
		data: {},
	},
	payload
) => {
	if ( payload && 'type' in payload ) {
		switch ( payload.type ) {
			case TYPES.SET_PRODUCT:
				const productData = state.data || {};
				return {
					...state,
					data: {
						...productData,
						[ payload.id ]: {
							...( productData[ payload.id ] || {} ),
							...payload.product,
						},
					},
				};
			case TYPES.SET_PRODUCTS:
				const ids: number[] = [];
				const nextProducts = payload.products.reduce<
					Record< number, PartialProduct >
				>( ( result, product ) => {
					ids.push( product.id );
					result[ product.id ] = product;
					return result;
				}, {} );
				const resourceName = getProductResourceName( payload.query );
				return {
					...state,
					products: {
						...state.products,
						[ resourceName ]: { data: ids },
					},
					data: {
						...state.data,
						...nextProducts,
					},
				};
			case TYPES.SET_PRODUCTS_TOTAL_COUNT:
				const totalResourceName = getTotalProductCountResourceName(
					payload.query
				);
				return {
					...state,
					productsCount: {
						...state.productsCount,
						[ totalResourceName ]: payload.totalCount,
					},
				};
			case TYPES.SET_ERROR:
				return {
					...state,
					errors: {
						...state.errors,
						[ getProductResourceName(
							payload.query
						) ]: payload.error,
					},
				};
			default:
				return state;
		}
	}
	return state;
};

export type State = ReturnType< typeof reducer >;
export default reducer;
