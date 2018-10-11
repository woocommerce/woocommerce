/** @format */

const DEFAULT_STATE = {
	orders: {},
	ids: [],
};

export default function ordersReducer( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case 'SET_ORDERS':
			const { orders } = action;
			const ordersMap = orders.reduce( ( map, order ) => {
				map[ order.id ] = order;
				return map;
			}, {} );
			return {
				...state,
				orders: Object.assign( {}, state.orders, ordersMap ),
			};
		case 'UPDATE_ORDER':
			const updatedOrders = { ...state.orders };
			updatedOrders[ action.order.id ] = action.order;
			return {
				...state,
				orders: updatedOrders,
			};
	}

	return state;
}
