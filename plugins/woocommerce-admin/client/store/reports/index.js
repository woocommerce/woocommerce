/** @format */

/**
 * External dependencies
 */
import { combineReducers } from 'redux';

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
