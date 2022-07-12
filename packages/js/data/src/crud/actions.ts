/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { getRestPath, parseId } from './utils';
import CRUD_ACTIONS from './crud-actions';
import TYPES from './action-types';
import { IdType, IdQuery, Item, ItemQuery } from './types';

type ResolverOptions = {
	resourceName: string;
	namespace: string;
};

export function createItemError( query: Partial< ItemQuery >, error: unknown ) {
	return {
		type: TYPES.CREATE_ITEM_ERROR as const,
		query,
		error,
		errorType: CRUD_ACTIONS.CREATE_ITEM,
	};
}

export function createItemSuccess( idQuery: IdQuery, item: Item ) {
	const { key } = parseId( idQuery );
	return {
		type: TYPES.CREATE_ITEM_SUCCESS as const,
		key,
		item,
	};
}

export function deleteItemError( idQuery: IdQuery, error: unknown ) {
	const { key } = parseId( idQuery );
	return {
		type: TYPES.DELETE_ITEM_ERROR as const,
		key,
		error,
		errorType: CRUD_ACTIONS.DELETE_ITEM,
	};
}

export function deleteItemSuccess(
	idQuery: IdQuery,
	force: boolean,
	item: Item
) {
	const { key } = parseId( idQuery );
	return {
		type: TYPES.DELETE_ITEM_SUCCESS as const,
		key,
		force,
		item,
	};
}

export function getItemError( idQuery: IdQuery, error: unknown ) {
	const { key } = parseId( idQuery );
	return {
		type: TYPES.GET_ITEM_ERROR as const,
		key,
		error,
		errorType: CRUD_ACTIONS.GET_ITEM,
	};
}

export function getItemSuccess( idQuery: IdQuery, item: Item ) {
	const { key } = parseId( idQuery );
	return {
		type: TYPES.GET_ITEM_SUCCESS as const,
		key,
		item,
	};
}

export function getItemsError( query: Partial< ItemQuery >, error: unknown ) {
	return {
		type: TYPES.GET_ITEMS_ERROR as const,
		query,
		error,
		errorType: CRUD_ACTIONS.GET_ITEMS,
	};
}

export function getItemsSuccess( query: Partial< ItemQuery >, items: Item[] ) {
	const parent_id =
		query && typeof query === 'object'
			? ( query.parent_id as IdType )
			: null;
	return {
		type: TYPES.GET_ITEMS_SUCCESS as const,
		items,
		query,
		parent_id,
	};
}

export function updateItemError( idQuery: IdQuery, error: unknown ) {
	const { key } = parseId( idQuery );
	return {
		type: TYPES.UPDATE_ITEM_ERROR as const,
		key,
		error,
		errorType: CRUD_ACTIONS.UPDATE_ITEM,
	};
}

export function updateItemSuccess( idQuery: IdQuery, item: Item ) {
	const { key } = parseId( idQuery );
	return {
		type: TYPES.UPDATE_ITEM_SUCCESS as const,
		key,
		item,
	};
}

export const createDispatchActions = ( {
	namespace,
	resourceName,
}: ResolverOptions ) => {
	const createItem = function* ( query: Partial< ItemQuery > ) {
		try {
			const item: Item = yield apiFetch( {
				path: getRestPath( namespace, query ),
				method: 'POST',
			} );

			yield createItemSuccess( item.id, item );
			return item;
		} catch ( error ) {
			yield createItemError( query, error );
			throw error;
		}
	};

	const deleteItem = function* ( idQuery: IdQuery, force = true ) {
		try {
			const { id, parent_id } = parseId( idQuery );
			const item: Item = yield apiFetch( {
				path: getRestPath(
					`${ namespace }/${ id }`,
					{ force },
					parent_id ? [ parent_id ] : []
				),
				method: 'DELETE',
			} );

			yield deleteItemSuccess( idQuery, force, item );
			return item;
		} catch ( error ) {
			yield deleteItemError( idQuery, error );
			throw error;
		}
	};

	const updateItem = function* (
		idQuery: IdQuery,
		query: Partial< ItemQuery >
	) {
		try {
			const { id, parent_id } = parseId( idQuery );
			const item: Item = yield apiFetch( {
				path: getRestPath(
					`${ namespace }/${ id }`,
					query,
					parent_id ? [ parent_id ] : []
				),
				method: 'PUT',
			} );

			yield updateItemSuccess( idQuery, item );
			return item;
		} catch ( error ) {
			yield updateItemError( idQuery, error );
			throw error;
		}
	};

	return {
		[ `create${ resourceName }` ]: createItem,
		[ `delete${ resourceName }` ]: deleteItem,
		[ `update${ resourceName }` ]: updateItem,
	};
};

export type Actions = ReturnType<
	| typeof createItemError
	| typeof createItemSuccess
	| typeof deleteItemError
	| typeof deleteItemSuccess
	| typeof getItemError
	| typeof getItemSuccess
	| typeof getItemsError
	| typeof getItemsSuccess
	| typeof updateItemError
	| typeof updateItemSuccess
>;
