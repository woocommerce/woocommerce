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
import taxes from 'store/taxes';

const store = registerStore( 'wc-admin', {
	reducer: combineReducers( {
		coupons: coupons.reducer,
		orders: orders.reducer,
		products: products.reducer,
		reports: reports.reducer,
		notes: notes.reducer,
		taxes: taxes.reducer,
	} ),

	actions: {
		...coupons.actions,
		...orders.actions,
		...products.actions,
		...reports.actions,
		...notes.actions,
		...taxes.actions,
	},

	selectors: {
		...coupons.selectors,
		...orders.selectors,
		...products.selectors,
		...reports.selectors,
		...notes.selectors,
		...taxes.selectors,
	},

	resolvers: {
		...coupons.resolvers,
		...orders.resolvers,
		...products.resolvers,
		...reports.resolvers,
		...notes.resolvers,
		...taxes.resolvers,
	},
} );

applyMiddleware( store, [ addThunks ] );
