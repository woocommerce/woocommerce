/** @format */
/**
 * External dependencies
 */
import { get } from 'lodash';

/**
 * Internal dependencies
 */
import { ERROR } from 'store/constants';
import { getJsonString } from 'store/utils';

export const DEFAULT_STATE = {
	queries: {},
};

export default function productsReducer( state = DEFAULT_STATE, action ) {
	if ( 'SET_PRODUCTS' === action.type ) {
		const prevQueries = get( state, 'queries', {} );
		const queryKey = getJsonString( action.query );
		const queries = {
			...prevQueries,
			[ queryKey ]: [ ...action.products ],
		};
		return {
			...state,
			queries,
		};
	}
	if ( 'SET_PRODUCTS_ERROR' === action.type ) {
		const prevQueries = get( state, 'queries', {} );
		const queryKey = getJsonString( action.query );
		const queries = {
			...prevQueries,
			[ queryKey ]: ERROR,
		};
		return {
			...state,
			queries,
		};
	}
	return state;
}
