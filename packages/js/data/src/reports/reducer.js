/**
 * Internal dependencies
 */
import TYPES from './action-types';

const reports = (
	state = {
		itemErrors: {},
		items: {},
		statErrors: {},
		stats: {},
	},
	{ type, items, stats, error, resourceName }
) => {
	switch ( type ) {
		case TYPES.SET_REPORT_ITEMS:
			return {
				...state,
				items: { ...state.items, [ resourceName ]: items },
			};
		case TYPES.SET_REPORT_STATS:
			return {
				...state,
				stats: { ...state.stats, [ resourceName ]: stats },
			};
		case TYPES.SET_ITEM_ERROR:
			return {
				...state,
				itemErrors: {
					...state.itemErrors,
					[ resourceName ]: error,
				},
			};
		case TYPES.SET_STAT_ERROR:
			return {
				...state,
				statErrors: {
					...state.statErrors,
					[ resourceName ]: error,
				},
			};
		default:
			return state;
	}
};

export default reports;
