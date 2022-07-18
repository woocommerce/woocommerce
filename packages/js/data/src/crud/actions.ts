/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { getParamsFromQuery, getRestPath, parseId } from './utils';
import CRUD_ACTIONS from './crud-actions';
import TYPES from './action-types';
import { IdType, IdQuery, Item, ItemQuery } from './types';

type ResolverOptions = {
	resourceName: string;
	namespace: string;
	urlParameters: IdType[];
};

export function createItemError(
	query: Partial< ItemQuery >,
	error: unknown,
	urlParameters: IdType[]
) {
	const params = getParamsFromQuery( query, urlParameters );
	return {
		type: TYPES.CREATE_ITEM_ERROR as const,
		query,
		error,
		errorType: CRUD_ACTIONS.CREATE_ITEM,
		params,
	};
}

export function createItemSuccess(
	idQuery: IdQuery,
	item: Item,
	urlParameters: IdType[]
) {
	const params = parseId( idQuery, urlParameters );
	return {
		type: TYPES.CREATE_ITEM_SUCCESS as const,
		key: params.key,
		item,
		params,
	};
}

export function deleteItemError(
	idQuery: IdQuery,
	error: unknown,
	urlParameters: IdType[]
) {
	const params = parseId( idQuery, urlParameters );
	return {
		type: TYPES.DELETE_ITEM_ERROR as const,
		key: params.key,
		error,
		errorType: CRUD_ACTIONS.DELETE_ITEM,
		params,
	};
}

export function deleteItemSuccess(
	idQuery: IdQuery,
	force: boolean,
	item: Item,
	urlParameters: IdType[]
) {
	const params = parseId( idQuery, urlParameters );
	return {
		type: TYPES.DELETE_ITEM_SUCCESS as const,
		key: params.key,
		force,
		item,
		params,
	};
}

export function getItemError(
	idQuery: IdQuery,
	error: unknown,
	urlParameters: IdType[]
) {
	const params = parseId( idQuery, urlParameters );
	return {
		type: TYPES.GET_ITEM_ERROR as const,
		key: params.key,
		error,
		errorType: CRUD_ACTIONS.GET_ITEM,
		params,
	};
}

export function getItemSuccess(
	idQuery: IdQuery,
	item: Item,
	urlParameters: IdType[]
) {
	const params = parseId( idQuery, urlParameters );
	return {
		type: TYPES.GET_ITEM_SUCCESS as const,
		key: params.key,
		item,
		params,
	};
}

export function getItemsError(
	query: Partial< ItemQuery >,
	error: unknown,
	urlParameters: IdType[]
) {
	const params = getParamsFromQuery( query, urlParameters );
	return {
		type: TYPES.GET_ITEMS_ERROR as const,
		query,
		error,
		errorType: CRUD_ACTIONS.GET_ITEMS,
		params,
	};
}

export function getItemsSuccess(
	query: Partial< ItemQuery >,
	items: Item[],
	urlParameters: IdType[]
) {
	const params = getParamsFromQuery( query, urlParameters );
	return {
		type: TYPES.GET_ITEMS_SUCCESS as const,
		items,
		query,
		params,
	};
}

export function updateItemError(
	idQuery: IdQuery,
	error: unknown,
	urlParameters: IdType[]
) {
	const params = parseId( idQuery, urlParameters );
	return {
		type: TYPES.UPDATE_ITEM_ERROR as const,
		key: params.key,
		error,
		errorType: CRUD_ACTIONS.UPDATE_ITEM,
		params,
	};
}

export function updateItemSuccess(
	idQuery: IdQuery,
	item: Item,
	urlParameters: IdType[]
) {
	const params = parseId( idQuery, urlParameters );
	return {
		type: TYPES.UPDATE_ITEM_SUCCESS as const,
		key: params.key,
		item,
		params,
	};
}

export const createDispatchActions = ( {
	namespace,
	resourceName,
	urlParameters = [],
}: ResolverOptions ) => {
	const createItem = function* ( query: Partial< ItemQuery > ) {
		const params = getParamsFromQuery( query, urlParameters );

		try {
			const item: Item = yield apiFetch( {
				path: getRestPath( namespace, query, params ),
				method: 'POST',
			} );

			yield createItemSuccess( item.id, item, urlParameters );
			return item;
		} catch ( error ) {
			yield createItemError( query, error, urlParameters );
			throw error;
		}
	};

	const deleteItem = function* ( idQuery: IdQuery, force = true ) {
		try {
			const params = parseId( idQuery, urlParameters );
			const { id } = params;

			const item: Item = yield apiFetch( {
				path: getRestPath(
					`${ namespace }/${ id }`,
					{ force },
					params
				),
				method: 'DELETE',
			} );

			yield deleteItemSuccess( idQuery, force, item, urlParameters );
			return item;
		} catch ( error ) {
			yield deleteItemError( idQuery, error, urlParameters );
			throw error;
		}
	};

	const updateItem = function* (
		idQuery: IdQuery,
		query: Partial< ItemQuery >
	) {
		try {
			const params = parseId( idQuery, urlParameters );
			const { id } = params;

			const item: Item = yield apiFetch( {
				path: getRestPath( `${ namespace }/${ id }`, query, params ),
				method: 'PUT',
			} );

			yield updateItemSuccess( idQuery, item, urlParameters );
			return item;
		} catch ( error ) {
			yield updateItemError( idQuery, error, urlParameters );
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
