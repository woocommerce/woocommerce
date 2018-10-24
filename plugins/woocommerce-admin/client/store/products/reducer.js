/** @format */
/**
 * External dependencies
 */
import { merge } from 'lodash';

/**
 * Internal dependencies
 */
import { ERROR } from 'store/constants';
import { getJsonString } from 'store/utils';

export const DEFAULT_STATE = {};

export default function productsReducer( state = DEFAULT_STATE, action ) {
	const queryKey = getJsonString( action.query );

	switch ( action.type ) {
		case 'SET_PRODUCTS':
			return merge( {}, state, {
				[ queryKey ]: action.products,
			} );

		case 'SET_PRODUCTS_ERROR':
			return merge( {}, state, {
				[ queryKey ]: ERROR,
			} );
	}

	return state;
}
