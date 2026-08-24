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
		case TYPES.SET_ITEM: {
			const itemData = state.data[ action.itemType ] || {};
			// Leaderboards are query-scoped. Update every cached query containing
			// the public ID, or retain the raw-ID behavior when none exists yet.
			const matchingIds =
				action.itemType === 'leaderboards'
					? Object.keys( itemData ).filter(
							( id ) => itemData[ id ].id === action.id
					  )
					: [];
			const ids = matchingIds.length ? matchingIds : [ action.id ];
			return {
				...state,
				data: {
					...state.data,
					[ action.itemType ]: ids.reduce(
						( data, id ) => ( {
							...data,
							[ id ]: {
								...( itemData[ id ] || {} ),
								...action.item,
							},
						} ),
						itemData
					),
				},
			};
		}
		case TYPES.SET_ITEMS: {
			const ids: Array< ItemID > = [];
			const resourceName = getResourceName(
				action.itemType,
				action.query
			);
			const nextItems = action.items.reduce< Record< ItemID, Item > >(
				( result, theItem ) => {
					const id =
						action.itemType === 'leaderboards'
							? `${ resourceName }:${ theItem.id }`
							: theItem.id;
					ids.push( id );
					result[ id ] = theItem;
					return result;
				},
				{}
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
		}
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
