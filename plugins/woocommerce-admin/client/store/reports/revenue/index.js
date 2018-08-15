/** @format */

/**
 * External dependencies
 */
import { combineReducers } from 'redux';

/**
 * Internal dependencies
 */
import stats from './stats';
import statsReducer from './stats/reducer';

export default {
	reducer: combineReducers( {
		stats: statsReducer,
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
