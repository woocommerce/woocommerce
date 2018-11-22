/** @format */

/**
 * External dependencies
 */
import { combineReducers } from '@wordpress/data';

/**
 * Internal dependencies
 */
import items from './items';
import stats from './stats';

export default {
	reducer: combineReducers( {
		items: items.reducer,
		stats: stats.reducer,
	} ),
	actions: {
		...items.actions,
		...stats.actions,
	},
	selectors: {
		...items.selectors,
		...stats.selectors,
	},
	resolvers: {
		...items.resolvers,
		...stats.resolvers,
	},
};
