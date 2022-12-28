/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { cleanQuery, getUrlParameters, getRestPath, parseId } from './utils';
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

export function createItemSuccess( key: IdType, item: Item ) {
	return {
		type: TYPES.CREATE_ITEM_SUCCESS as const,
		key,
		item,
	};
}

export function deleteItemError( key: IdType, error: unknown ) {
	return {
		type: TYPES.DELETE_ITEM_ERROR as const,
		key,
		error,
		errorType: CRUD_ACTIONS.DELETE_ITEM,
	};
}

export function deleteItemSuccess( key: IdQuery, force: boolean, item: Item ) {
	return {
		type: TYPES.DELETE_ITEM_SUCCESS as const,
		key,
		force,
		item,
	};
}

export function getItemError( key: IdType, error: unknown ) {
	return {
		type: TYPES.GET_ITEM_ERROR as const,
		key,
		error,
		errorType: CRUD_ACTIONS.GET_ITEM,
	};
}

export function getItemSuccess( key: IdType, item: Item ) {
	return {
		type: TYPES.GET_ITEM_SUCCESS as const,
		key,
		item,
	};
}

export function getItemsError(
	query: Partial< ItemQuery > | undefined,
	error: unknown
) {
	return {
		type: TYPES.GET_ITEMS_ERROR as const,
		query,
		error,
		errorType: CRUD_ACTIONS.GET_ITEMS,
	};
}

export function getItemsSuccess(
	query: Partial< ItemQuery > | undefined,
	items: Item[],
	urlParameters: IdType[]
) {
	return {
		type: TYPES.GET_ITEMS_SUCCESS as const,
		items,
		query,
		urlParameters,
	};
}

export function getItemsTotalCountSuccess(
	query: Partial< ItemQuery > | undefined,
	totalCount: number
) {
	return {
		type: TYPES.GET_ITEMS_TOTAL_COUNT_SUCCESS as const,
		query,
		totalCount,
	};
}

export function getItemsTotalCountError(
	query: Partial< ItemQuery > | undefined,
	error: unknown
) {
	return {
		type: TYPES.GET_ITEMS_TOTAL_COUNT_ERROR as const,
		query,
		error,
		errorType: CRUD_ACTIONS.GET_ITEMS_TOTAL_COUNT,
	};
}

export function updateItemError( key: IdType, error: unknown ) {
	return {
		type: TYPES.UPDATE_ITEM_ERROR as const,
		key,
		error,
		errorType: CRUD_ACTIONS.UPDATE_ITEM,
	};
}

export function updateItemSuccess( key: IdType, item: Item ) {
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
		const urlParameters = getUrlParameters( namespace, query );

		try {
			const item: Item = yield apiFetch( {
				path: getRestPath(
					namespace,
					cleanQuery( query, namespace ),
					urlParameters
				),
				method: 'POST',
			} );
			const { key } = parseId( item.id, urlParameters );

			yield createItemSuccess( key, item );
			return item;
		} catch ( error ) {
			yield createItemError( query, error );
			throw error;
		}
	};

	const deleteItem = function* ( idQuery: IdQuery, force = true ) {
		const urlParameters = getUrlParameters( namespace, idQuery );
		const { id, key } = parseId( idQuery, urlParameters );

		try {
			const item: Item = yield apiFetch( {
				path: getRestPath(
					`${ namespace }/${ id }`,
					{ force },
					urlParameters
				),
				method: 'DELETE',
			} );

			yield deleteItemSuccess( key, force, item );
			return item;
		} catch ( error ) {
			yield deleteItemError( key, error );
			throw error;
		}
	};

	const updateItem = function* (
		idQuery: IdQuery,
		query: Partial< ItemQuery >
	) {
		const urlParameters = getUrlParameters( namespace, idQuery );
		const { id, key } = parseId( idQuery, urlParameters );

		try {
			const item: Item = yield apiFetch( {
				path: getRestPath(
					`${ namespace }/${ id }`,
					{},
					urlParameters
				),
				method: 'PUT',
				data: query,
			} );

			yield updateItemSuccess( key, item );
			return item;
		} catch ( error ) {
			yield updateItemError( key, error );
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
	| typeof getItemsTotalCountSuccess
	| typeof getItemsTotalCountError
	| typeof updateItemError
	| typeof updateItemSuccess
>;
