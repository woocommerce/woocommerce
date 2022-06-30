/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { PartialOrder, OrdersQuery } from './types';

export function getOrderSuccess( id: number, order: PartialOrder ) {
	return {
		type: TYPES.GET_ORDER_SUCCESS as const,
		id,
		order,
	};
}

export function getOrderError( query: Partial< OrdersQuery >, error: unknown ) {
	return {
		type: TYPES.GET_ORDER_ERROR as const,
		query,
		error,
	};
}

export function getOrdersSuccess(
	query: Partial< OrdersQuery >,
	orders: PartialOrder[],
	totalCount: number
) {
	return {
		type: TYPES.GET_ORDERS_SUCCESS as const,
		orders,
		query,
		totalCount,
	};
}

export function getOrdersError(
	query: Partial< OrdersQuery >,
	error: unknown
) {
	return {
		type: TYPES.GET_ORDERS_ERROR as const,
		query,
		error,
	};
}

export function getOrdersTotalCountSuccess(
	query: Partial< OrdersQuery >,
	totalCount: number
) {
	return {
		type: TYPES.GET_ORDERS_TOTAL_COUNT_SUCCESS as const,
		query,
		totalCount,
	};
}

export function getOrdersTotalCountError(
	query: Partial< OrdersQuery >,
	error: unknown
) {
	return {
		type: TYPES.GET_ORDERS_TOTAL_COUNT_ERROR as const,
		query,
		error,
	};
}

export type Actions = ReturnType<
	| typeof getOrderSuccess
	| typeof getOrderError
	| typeof getOrdersSuccess
	| typeof getOrdersError
	| typeof getOrdersTotalCountSuccess
	| typeof getOrdersTotalCountError
>;
