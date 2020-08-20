/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { getResourceName } from '../utils';

const reducer = (
	state = {
		items: {},
		errors: {},
		data: {},
	},
	{ type, itemType, query, items, totalCount, error }
) => {
	switch ( type ) {
		case TYPES.SET_ITEMS:
			const ids = [];
			const nextItems = items.reduce( ( result, item ) => {
				ids.push( item.id );
				result[ item.id ] = item;
				return result;
			}, {} );
			const resourceName = getResourceName( itemType, query );
			return {
				...state,
				items: {
					...state.items,
					[ resourceName ]: { data: ids, totalCount },
				},
				data: {
					...state.data,
					[ itemType ]: {
						...state.data[ itemType ],
						...nextItems,
					},
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
