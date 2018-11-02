/** @format */

/**
 * External dependencies
 */
import { combineReducers } from '@wordpress/data';

/**
 * Internal dependencies
 */
import stats from './stats';

export default {
	reducer: combineReducers( {
		stats: stats.reducer,
	} ),
	actions: {
		...stats.actions,
	},
	selectors: {
		...stats.selectors,
	},
	resolvers: {
		...stats.resolvers,
	},
};
