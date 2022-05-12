/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import { getOrderResourceName, getTotalOrderCountResourceName } from './utils';
import { OrderState } from './reducer';
import { OrderQuery } from './types';
import { WPDataSelector, WPDataSelectors } from '../types';

export const getOrders = createSelector(
	( state: OrderState, query: OrderQuery, defaultValue = undefined ) => {
		const resourceName = getOrderResourceName( query );
		const ids = state.orders[ resourceName ]
			? state.orders[ resourceName ].data
			: undefined;
		if ( ! ids ) {
			return defaultValue;
		}
		return ids.map( ( id ) => {
			return state.data[ id ];
		} );
	},
	( state, query ) => {
		const resourceName = getOrderResourceName( query );
		return [ state.orders[ resourceName ] ];
	}
);

export const getOrdersTotalCount = (
	state: OrderState,
	query: OrderQuery,
	defaultValue = undefined
) => {
	const resourceName = getTotalOrderCountResourceName( query );
	const totalCount = state.ordersCount.hasOwnProperty( resourceName )
		? state.ordersCount[ resourceName ]
		: defaultValue;
	return totalCount;
};

export const getOrdersError = ( state: OrderState, query: OrderQuery ) => {
	const resourceName = getOrderResourceName( query );
	return state.errors[ resourceName ];
};

export type OrdersSelectors = {
	getOrders: WPDataSelector< typeof getOrders >;
	getOrdersTotalCount: WPDataSelector< typeof getOrdersTotalCount >;
	getOrdersError: WPDataSelector< typeof getOrdersError >;
} & WPDataSelectors;
