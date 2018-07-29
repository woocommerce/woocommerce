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

const store = registerStore( 'wc-admin', {
	reducer: combineReducers( {
		orders: orders.reducer,
	} ),

	actions: {
		...orders.actions,
	},

	selectors: {
		...orders.selectors,
	},

	resolvers: {
		...orders.resolvers,
	},
} );

applyMiddleware( store, [ addThunks ] );
