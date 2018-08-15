/** @format */
/**
 * External dependencies
 */
import { registerStore } from '@wordpress/data';
import { combineReducers } from 'redux';

/**
 * Internal dependencies
 */
import { applyMiddleware, addThunks } from './middleware';
import orders from 'store/orders';
import reports from 'store/reports';

const store = registerStore( 'wc-admin', {
	reducer: combineReducers( {
		orders: orders.reducer,
		reports: reports.reducer,
	} ),

	actions: {
		...orders.actions,
		...reports.actions,
	},

	selectors: {
		...orders.selectors,
		...reports.selectors,
	},

	resolvers: {
		...orders.resolvers,
		...reports.resolvers,
	},
} );

applyMiddleware( store, [ addThunks ] );
