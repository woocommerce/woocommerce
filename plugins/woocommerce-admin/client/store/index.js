/** @format */
/**
 * External dependencies
 */
import { combineReducers, registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { applyMiddleware, addThunks } from './middleware';
import orders from 'store/orders';
import reports from 'store/reports';
import notes from 'store/notes';

const store = registerStore( 'wc-admin', {
	reducer: combineReducers( {
		orders: orders.reducer,
		reports: reports.reducer,
		notes: notes.reducer,
	} ),

	actions: {
		...orders.actions,
		...reports.actions,
		...notes.actions,
	},

	selectors: {
		...orders.selectors,
		...reports.selectors,
		...notes.selectors,
	},

	resolvers: {
		...orders.resolvers,
		...reports.resolvers,
		...notes.resolvers,
	},
} );

applyMiddleware( store, [ addThunks ] );
