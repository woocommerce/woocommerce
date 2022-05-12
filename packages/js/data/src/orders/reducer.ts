/**
 * External dependencies
 */
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Actions } from './actions';
import { PartialOrder } from './types';
import { getOrderResourceName, getTotalOrderCountResourceName } from './utils';

export type OrderState = {
	orders: Record<
		string,
		{
			data: number[];
		}
	>;
	ordersCount: Record< string, number >;
	errors: Record< string, unknown >;
	data: Record< number, PartialOrder >;
};

const reducer: Reducer< OrderState, Actions > = (
	state = {
		orders: {},
		ordersCount: {},
		errors: {},
		data: {},
	},
	payload
) => {
	if ( payload && 'type' in payload ) {
		switch ( payload.type ) {
			case TYPES.SET_ORDER:
				const orderData = state.data || {};
				return {
					...state,
					data: {
						...orderData,
						[ payload.id ]: {
							...( orderData[ payload.id ] || {} ),
							...payload.order,
						},
					},
				};
			case TYPES.SET_ORDERS:
				const ids: number[] = [];
				const nextOrders = payload.orders.reduce<
					Record< number, PartialOrder >
				>( ( result, order ) => {
					ids.push( order.id );
					result[ order.id ] = order;
					return result;
				}, {} );
				const resourceName = getOrderResourceName( payload.query );
				return {
					...state,
					orders: {
						...state.orders,
						[ resourceName ]: { data: ids },
					},
					data: {
						...state.data,
						...nextOrders,
					},
				};
			case TYPES.SET_ORDERS_TOTAL_COUNT:
				const totalResourceName = getTotalOrderCountResourceName(
					payload.query
				);
				return {
					...state,
					ordersCount: {
						...state.ordersCount,
						[ totalResourceName ]: payload.totalCount,
					},
				};
			case TYPES.SET_ERROR:
				return {
					...state,
					errors: {
						...state.errors,
						[ getOrderResourceName(
							payload.query
						) ]: payload.error,
					},
				};
			default:
				return state;
		}
	}
	return state;
};

export type State = ReturnType< typeof reducer >;
export default reducer;
