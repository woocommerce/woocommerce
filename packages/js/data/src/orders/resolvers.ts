/**
 * Internal dependencies
 */
import { WC_ORDERS_NAMESPACE } from './constants';
import { Order, OrdersQuery } from './types';
import {
	getOrdersError,
	getOrdersSuccess,
	getOrdersTotalCountError,
	getOrdersTotalCountSuccess,
} from './actions';
import { request } from '../utils';

export function* getOrders( query: Partial< OrdersQuery > ) {
	// id is always required.
	const ordersQuery = {
		...query,
	};
	if (
		ordersQuery &&
		ordersQuery._fields &&
		! ordersQuery._fields.includes( 'id' )
	) {
		ordersQuery._fields = [ 'id', ...ordersQuery._fields ];
	}
	try {
		const { items, totalCount }: { items: Order[]; totalCount: number } =
			yield request< OrdersQuery, Order >(
				WC_ORDERS_NAMESPACE,
				ordersQuery
			);
		yield getOrdersTotalCountSuccess( query, totalCount );
		yield getOrdersSuccess( query, items, totalCount );
		return items;
	} catch ( error ) {
		yield getOrdersError( query, error );
		return error;
	}
}

export function* getOrdersTotalCount( query: Partial< OrdersQuery > ) {
	try {
		const totalsQuery = {
			...query,
			page: 1,
			per_page: 1,
		};
		const { totalCount } = yield request< OrdersQuery, Order >(
			WC_ORDERS_NAMESPACE,
			totalsQuery
		);
		yield getOrdersTotalCountSuccess( query, totalCount );
		return totalCount;
	} catch ( error ) {
		yield getOrdersTotalCountError( query, error );
		return error;
	}
}
