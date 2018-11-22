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

const DEFAULT_STATE = {};

export default function reportItemsReducer( state = DEFAULT_STATE, action ) {
	const queryKey = getJsonString( action.query );

	switch ( action.type ) {
		case 'SET_REPORT_ITEMS':
			return merge( {}, state, {
				[ action.endpoint ]: {
					[ queryKey ]: action.items,
				},
			} );

		case 'SET_REPORT_ITEMS_ERROR':
			return merge( {}, state, {
				[ action.endpoint ]: {
					[ queryKey ]: ERROR,
				},
			} );
	}

	return state;
}
