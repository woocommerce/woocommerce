/** @format */

export default {
	setOrders( orders, query ) {
		return {
			type: 'SET_ORDERS',
			orders,
			query: query || {},
		};
	},
	setOrdersError( query ) {
		return {
			type: 'SET_ORDERS_ERROR',
			query: query || {},
		};
	},
};
