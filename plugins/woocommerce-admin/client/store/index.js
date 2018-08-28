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
import products from 'store/products';
import reports from 'store/reports';

const store = registerStore( 'wc-admin', {
	reducer: combineReducers( {
		orders: orders.reducer,
		products: products.reducer,
		reports: reports.reducer,
	} ),

	actions: {
		...orders.actions,
		...products.actions,
		...reports.actions,
	},

	selectors: {
		...orders.selectors,
		...products.selectors,
		...reports.selectors,
	},

	resolvers: {
		...orders.resolvers,
		...products.resolvers,
		...reports.resolvers,
	},
} );

applyMiddleware( store, [ addThunks ] );
