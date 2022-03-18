/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { getResourceName } from '../utils';
import { getTotalCountResourceName } from './utils';

const reducer = (
	state = {
		items: {},
		errors: {},
		data: {},
	},
	{ type, id, itemType, query, item, items, totalCount, error }
) => {
	switch ( type ) {
		case TYPES.SET_ITEM:
			const itemData = state.data[ itemType ] || {};
			return {
				...state,
				data: {
					...state.data,
					[ itemType ]: {
						...itemData,
						[ id ]: {
							...( itemData[ id ] || {} ),
							...item,
						},
					},
				},
			};
		case TYPES.SET_ITEMS:
			const ids = [];
			const nextItems = items.reduce( ( result, theItem ) => {
				ids.push( theItem.id );
				result[ theItem.id ] = theItem;
				return result;
			}, {} );
			const resourceName = getResourceName( itemType, query );
			return {
				...state,
				items: {
					...state.items,
					[ resourceName ]: { data: ids },
				},
				data: {
					...state.data,
					[ itemType ]: {
						...state.data[ itemType ],
						...nextItems,
					},
				},
			};
		case TYPES.SET_ITEMS_TOTAL_COUNT:
			const totalResourceName = getTotalCountResourceName(
				itemType,
				query
			);
			return {
				...state,
				items: {
					...state.items,
					[ totalResourceName ]: totalCount,
				},
			};
		case TYPES.SET_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					[ getResourceName( itemType, query ) ]: error,
				},
			};
		default:
			return state;
	}
};

export default reducer;
