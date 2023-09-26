/**
 * External dependencies
 */

import type { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { getResourceName } from '../utils';
import { getTotalCountResourceName } from './utils';
import { Action } from './actions';
import { ItemsState, Item, ItemID } from './types';

const initialState: ItemsState = {
	items: {},
	errors: {},
	data: {},
};

const reducer: Reducer< ItemsState, Action > = (
	state = initialState,
	action
) => {
	switch ( action.type ) {
		case TYPES.SET_ITEM:
			const itemData = state.data[ action.itemType ] || {};
			return {
				...state,
				data: {
					...state.data,
					[ action.itemType ]: {
						...itemData,
						[ action.id ]: {
							...( itemData[ action.id ] || {} ),
							...action.item,
						},
					},
				},
			};
		case TYPES.SET_ITEMS:
			const ids: Array< ItemID > = [];
			const nextItems = action.items.reduce< Record< ItemID, Item > >(
				( result, theItem ) => {
					ids.push( theItem.id );
					result[ theItem.id ] = theItem;
					return result;
				},
				{}
			);
			const resourceName = getResourceName(
				action.itemType,
				action.query
			);
			return {
				...state,
				items: {
					...state.items,
					[ resourceName ]: { data: ids },
				},
				data: {
					...state.data,
					[ action.itemType ]: {
						...state.data[ action.itemType ],
						...nextItems,
					},
				},
			};
		case TYPES.SET_ITEMS_TOTAL_COUNT:
			const totalResourceName = getTotalCountResourceName(
				action.itemType,
				action.query
			);
			return {
				...state,
				items: {
					...state.items,
					[ totalResourceName ]: action.totalCount,
				},
			};
		case TYPES.SET_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					[ getResourceName( action.itemType, action.query ) ]:
						action.error,
				},
			};
		default:
			return state;
	}
};

export type State = ReturnType< typeof reducer >;
export default reducer;
