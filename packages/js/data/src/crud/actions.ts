/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { apiFetch } from '@wordpress/data-controls';
import { DispatchFromMap } from '@automattic/data-stores';
 
/**
 * Internal dependencies
 */
import CRUD_ACTIONS from './crud-actions';
import TYPES from './action-types';
import { Item, ItemQuery } from './types';


export function* createItem( query: Partial< ItemQuery > ) {
    try {
        const item: Item = yield apiFetch( {
            path: addQueryArgs( namespace, query ),
            method: 'POST',
        } );

        yield createItemSuccess( item.id, item );
        return item;
    } catch ( error ) {
        yield createItemError( query, error );
        throw error;
    }
};

export function createItemError( query: unknown, error: unknown ) {
	return {
		type: TYPES.CREATE_ITEM_ERROR as const,
		query,
		error,
        errorType: CRUD_ACTIONS.CREATE_ITEM,
	};
}

export function createItemSuccess( id: number, item: Item ) {
	return {
		type: TYPES.CREATE_ITEM_SUCCESS as const,
		id,
		item,
	};
}

export function getItemError( id: unknown, error: unknown ) {
	return {
		type: TYPES.GET_ITEM_ERROR as const,
		id,
		error,
        errorType: CRUD_ACTIONS.GET_ITEM,
	};
}

export function getItemSuccess( id: number, item: Item ) {
	return {
		type: TYPES.GET_ITEM_SUCCESS as const,
		id,
		item,
	};
}

export function getItemsError( query: unknown, error: unknown ) {
	return {
		type: TYPES.GET_ITEMS_ERROR as const,
		query,
		error,
        errorType: CRUD_ACTIONS.GET_ITEMS,
	};
}

export function getItemsSuccess( query: unknown, items: Item[] ) {
	return {
		type: TYPES.GET_ITEMS_SUCCESS as const,
		items,
		query,
	};
}

export function* updateItem( id: number, query: Partial< ItemQuery > ) {
    try {
        const item: Item = yield apiFetch( {
            path: addQueryArgs( `${ namespace }/${ id }`, query ),
            method: 'PUT',
        } );

        yield updateItemSuccess( item.id, item );
        return item;
    } catch ( error ) {
        yield updateItemError( query, error );
        throw error;
    }
};


export function updateItemError( id: unknown, error: unknown ) {
	return {
		type: TYPES.UPDATE_ITEM_ERROR as const,
		id,
		error,
        errorType: CRUD_ACTIONS.UPDATE_ITEM,
	};
}

export function updateItemSuccess( id: number, item: Item ) {
	return {
		type: TYPES.UPDATE_ITEM_SUCCESS as const,
		id,
		item,
	};
}

export type Actions = ReturnType<
	| typeof createItemError
	| typeof createItemSuccess
	| typeof getItemError
	| typeof getItemSuccess
	| typeof getItemsError
	| typeof getItemsSuccess
	| typeof updateItemError
	| typeof updateItemSuccess
>;

export type ActionDispatchers = DispatchFromMap< {
	createItem: typeof createItem;
	updateItem: typeof updateItem;
} >;
