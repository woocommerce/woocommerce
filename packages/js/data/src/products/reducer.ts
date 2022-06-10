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
			case TYPES.CREATE_PRODUCT_SUCCESS:
			case TYPES.GET_PRODUCT_SUCCESS:
			case TYPES.UPDATE_PRODUCT_SUCCESS:
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
			case TYPES.GET_PRODUCTS_SUCCESS:
				const ids: number[] = [];
				const nextProducts = payload.products.reduce<
					Record< number, PartialProduct >
				>( ( result, product ) => {
					ids.push( product.id );
					result[ product.id ] = {
						...( state.data[ product.id ] || {} ),
						...product,
					};
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
			case TYPES.GET_PRODUCTS_TOTAL_COUNT_SUCCESS:
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
			case TYPES.GET_PRODUCT_ERROR:
			case TYPES.GET_PRODUCTS_ERROR:
			case TYPES.GET_PRODUCTS_TOTAL_COUNT_ERROR:
			case TYPES.CREATE_PRODUCT_ERROR:
				return {
					...state,
					errors: {
						...state.errors,
						[ getProductResourceName(
							payload.query
						) ]: payload.error,
					},
				};
			case TYPES.UPDATE_PRODUCT_ERROR:
				return {
					...state,
					errors: {
						...state.errors,
						[ `update/${ payload.id }` ]: payload.error,
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
