/** @format */

/**
 * External dependencies
 */
import { combineReducers } from 'redux';

/**
 * Internal dependencies
 */

import revenue from './revenue';
import stats from './stats';

export default {
	reducer: combineReducers( {
		revenue: revenue.reducer,
		stats: stats.reducer,
	} ),
	actions: {
		...revenue.actions,
		...stats.actions,
	},
	selectors: {
		...revenue.selectors,
		...stats.selectors,
	},
	resolvers: {
		...revenue.resolvers,
		...stats.resolvers,
	},
};
