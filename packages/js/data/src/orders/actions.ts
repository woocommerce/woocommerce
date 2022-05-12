/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { PartialOrder, OrderQuery } from './types';

export function setOrder( id: number, order: PartialOrder ) {
	return {
		type: TYPES.SET_ORDER as const,
		id,
		order,
	};
}

export function setOrders(
	query: Partial< OrderQuery >,
	orders: PartialOrder[],
	totalCount: number
) {
	return {
		type: TYPES.SET_ORDERS as const,
		orders,
		query,
		totalCount,
	};
}

export function setOrdersTotalCount(
	query: Partial< OrderQuery >,
	totalCount: number
) {
	return {
		type: TYPES.SET_ORDERS_TOTAL_COUNT as const,
		query,
		totalCount,
	};
}

export function setError( query: Partial< OrderQuery >, error: unknown ) {
	return {
		type: TYPES.SET_ERROR as const,
		query,
		error,
	};
}

export type Actions = ReturnType<
	| typeof setOrder
	| typeof setOrders
	| typeof setOrdersTotalCount
	| typeof setError
>;
