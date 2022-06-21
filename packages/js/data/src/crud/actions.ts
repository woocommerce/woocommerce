/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Item } from './types';

export function getItemsSuccess( query: unknown, items: Item[] ) {
	return {
		type: TYPES.GET_ITEMS_SUCCESS as const,
		items,
		query,
	};
}

export function getItemsError( query: unknown, error: unknown ) {
	return {
		type: TYPES.GET_ITEMS_ERROR as const,
		query,
		error,
	};
}

export function getItemSuccess( id: number, item: Item ) {
	return {
		type: TYPES.GET_ITEM_SUCCESS as const,
		id,
		item,
	};
}

export function getItemError( id: unknown, error: unknown ) {
	return {
		type: TYPES.GET_ITEM_ERROR as const,
		id,
		error,
	};
}

export type Actions = ReturnType<
	| typeof getItemsSuccess
	| typeof getItemsError
	| typeof getItemSuccess
	| typeof getItemError
>;
