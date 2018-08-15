/** @format */

/**
 * External dependencies
 */
import { combineReducers } from 'redux';

/**
 * Internal dependencies
 */

import revenue from './revenue';

export default {
	reducer: combineReducers( {
		revenue: revenue.reducer,
	} ),
	actions: {
		...revenue.actions,
	},
	selectors: {
		...revenue.selectors,
	},
	resolvers: {
		...revenue.resolvers,
	},
};
