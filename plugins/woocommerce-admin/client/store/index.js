/** @format */
/**
 * External dependencies
 */
import { combineReducers, registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { applyMiddleware, addThunks } from './middleware';
import coupons from 'store/coupons';
import orders from 'store/orders';
import products from 'store/products';
import reports from 'store/reports';
import notes from 'store/notes';

const store = registerStore( 'wc-admin', {
	reducer: combineReducers( {
		coupons: coupons.reducer,
		orders: orders.reducer,
		products: products.reducer,
		reports: reports.reducer,
		notes: notes.reducer,
	} ),

	actions: {
		...coupons.actions,
		...orders.actions,
		...products.actions,
		...reports.actions,
		...notes.actions,
	},

	selectors: {
		...coupons.selectors,
		...orders.selectors,
		...products.selectors,
		...reports.selectors,
		...notes.selectors,
	},

	resolvers: {
		...coupons.resolvers,
		...orders.resolvers,
		...products.resolvers,
		...reports.resolvers,
		...notes.resolvers,
	},
} );

applyMiddleware( store, [ addThunks ] );
