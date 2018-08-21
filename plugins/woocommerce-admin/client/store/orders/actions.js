/** @format */
/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

export default {
	setOrders( orders ) {
		return {
			type: 'SET_ORDERS',
			orders,
		};
	},
	updateOrder( order ) {
		return {
			type: 'UPDATE_ORDER',
			order,
		};
	},
	requestUpdateOrder( order ) {
		return async () => {
			// Lets be optimistic
			dispatch( 'wc-admin' ).updateOrder( order );
			try {
				const updatedOrder = await apiFetch( {
					path: '/wc/v3/orders/' + order.id,
					method: 'PUT',
					data: order,
				} );

				dispatch( 'wc-admin' ).updateOrder( updatedOrder );
			} catch ( error ) {
				if ( error && error.responseJSON ) {
					alert( error.responseJSON.message );
				} else {
					alert( error );
				}
			}
		};
	},
};
