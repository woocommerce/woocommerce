/**
 * External dependencies
 */
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import { Actions } from './actions';
import CRUD_ACTIONS from './crud-actions';
import { getKey, getRequestIdentifier } from './utils';
import { getTotalCountResourceName } from '../utils';
import { IdType, Item, ItemQuery } from './types';
import { TYPES } from './action-types';

export type Data = Record< IdType, Item >;
export type ResourceState = {
	items: Record<
		string,
		{
			data: IdType[];
		}
	>;
	data: Data;
	itemsCount: Record< string, number >;
	errors: Record< string, unknown >;
	requesting: Record< string, boolean >;
};

export const createReducer = (
	additionalReducer?: Reducer< ResourceState >
) => {
	const reducer: Reducer< ResourceState, Actions > = (
		state = {
			items: {},
			data: {},
			itemsCount: {},
			errors: {},
			requesting: {},
		},
		payload
	) => {
		const itemData = state.data || {};

		if ( payload && 'type' in payload ) {
			switch ( payload.type ) {
				case TYPES.CREATE_ITEM_ERROR:
					const createItemErrorRequestId = getRequestIdentifier(
						payload.errorType,
						payload.query || {}
					);
					return {
						...state,
						errors: {
							...state.errors,
							[ createItemErrorRequestId ]: payload.error,
						},
						requesting: {
							...state.requesting,
							[ createItemErrorRequestId ]: false,
						},
					};
				case TYPES.GET_ITEMS_TOTAL_COUNT_ERROR:
				case TYPES.GET_ITEMS_ERROR:
					return {
						...state,
						errors: {
							...state.errors,
							[ getRequestIdentifier(
								payload.errorType,
								( payload.query || {} ) as ItemQuery
							) ]: payload.error,
						},
					};
				case TYPES.GET_ITEMS_TOTAL_COUNT_SUCCESS:
					return {
						...state,
						itemsCount: {
							...state.itemsCount,
							[ getTotalCountResourceName(
								CRUD_ACTIONS.GET_ITEMS,
								( payload.query || {} ) as ItemQuery
							) ]: payload.totalCount,
						},
					};

				case TYPES.CREATE_ITEM_SUCCESS: {
					const createItemSuccessRequestId = getRequestIdentifier(
						CRUD_ACTIONS.CREATE_ITEM,
						payload.key,
						payload.query
					);

					const { options } = payload;
					const data = {
						...itemData,
						[ payload.key ]: {
							...( itemData[ payload.key ] || {} ),
							...payload.item,
						},
					};

					let items = state.items;
					let queryItems = Object.keys( data ).map( ( key ) => +key );

					let itemsCount = state.itemsCount;

					/*
					 * Check it needs to update the store with the new item,
					 * optimistically.
					 */
					if ( options?.optimisticQueryUpdate ) {
						/*
						 * If the query has an order_by property, sort the items
						 * by the order_by property.
						 *
						 * The sort criteria could be different from the
						 * the server side.
						 * Ensure to keep in sync with the server side, for instance,
						 * by invalidating the cache.
						 *
						 * Todo: Add a mechanism to use the server side sorting criteria.
						 */
						if ( options.optimisticQueryUpdate?.order_by ) {
							type OrderBy = keyof Item;
							const order_by = options.optimisticQueryUpdate
								?.order_by as OrderBy;

							let sortingData = Object.values( data );
							sortingData = sortingData.sort( ( a, b ) =>
								( a[ order_by ] as string )
									.toLowerCase()
									.localeCompare(
										(
											b[ order_by ] as string
										 ).toLowerCase()
									)
							);

							queryItems = sortingData.map( ( item ) =>
								Number( item.id )
							);
						}

						const getItemQuery = getRequestIdentifier(
							CRUD_ACTIONS.GET_ITEMS,
							options.optimisticQueryUpdate
						);

						const getItemCountQuery = getTotalCountResourceName(
							CRUD_ACTIONS.GET_ITEMS,
							options.optimisticQueryUpdate
						);

						items = {
							...state.items,
							[ getItemQuery ]: {
								...state.items[ getItemQuery ],
								data: queryItems,
							},
						};

						itemsCount = {
							...state.itemsCount,
							[ getItemCountQuery ]: Object.keys( data ).length,
						};
					}

					return {
						...state,
						items,
						itemsCount,
						data,
						requesting: {
							...state.requesting,
							[ createItemSuccessRequestId ]: false,
						},
					};
				}

				case TYPES.GET_ITEM_SUCCESS:
					return {
						...state,
						data: {
							...itemData,
							[ payload.key ]: {
								...( itemData[ payload.key ] || {} ),
								...payload.item,
							},
						},
					};

				case TYPES.UPDATE_ITEM_SUCCESS:
					const updateItemSuccessRequestId = getRequestIdentifier(
						CRUD_ACTIONS.UPDATE_ITEM,
						payload.key,
						payload.query
					);
					return {
						...state,
						data: {
							...itemData,
							[ payload.key ]: {
								...( itemData[ payload.key ] || {} ),
								...payload.item,
							},
						},
						requesting: {
							...state.requesting,
							[ updateItemSuccessRequestId ]: false,
						},
					};

				case TYPES.DELETE_ITEM_SUCCESS:
					const deleteItemSuccessRequestId = getRequestIdentifier(
						CRUD_ACTIONS.DELETE_ITEM,
						payload.key,
						payload.force
					);
					const itemKeys = Object.keys( state.data );
					const nextData = itemKeys.reduce< Data >(
						( items: Data, key: string ) => {
							if ( key !== payload.key.toString() ) {
								items[ key ] = state.data[ key ];
								return items;
							}
							if ( payload.force ) {
								return items;
							}
							items[ key ] = payload.item;
							return items;
						},
						{} as Data
					);

					return {
						...state,
						data: nextData,
						requesting: {
							...state.requesting,
							[ deleteItemSuccessRequestId ]: false,
						},
					};

				case TYPES.DELETE_ITEM_ERROR:
					const deleteItemErrorRequestId = getRequestIdentifier(
						payload.errorType,
						payload.key,
						payload.force
					);
					return {
						...state,
						errors: {
							...state.errors,
							[ deleteItemErrorRequestId ]: payload.error,
						},
						requesting: {
							...state.requesting,
							[ deleteItemErrorRequestId ]: false,
						},
					};

				case TYPES.GET_ITEM_ERROR:
					return {
						...state,
						errors: {
							...state.errors,
							[ getRequestIdentifier(
								payload.errorType,
								payload.key
							) ]: payload.error,
						},
					};

				case TYPES.UPDATE_ITEM_ERROR:
					const upateItemErrorRequestId = getRequestIdentifier(
						payload.errorType,
						payload.key,
						payload.query
					);
					return {
						...state,
						errors: {
							...state.errors,
							[ upateItemErrorRequestId ]: payload.error,
						},
						requesting: {
							...state.requesting,
							[ upateItemErrorRequestId ]: false,
						},
					};

				case TYPES.GET_ITEMS_SUCCESS:
					const ids: IdType[] = [];

					const nextResources = payload.items.reduce<
						Record< string, Item >
					>( ( result, item ) => {
						const key = getKey( item.id, payload.urlParameters );
						ids.push( key );
						result[ key ] = {
							...( state.data[ key ] || {} ),
							...item,
						};
						return result;
					}, {} );

					const itemQuery = getRequestIdentifier(
						CRUD_ACTIONS.GET_ITEMS,
						( payload.query || {} ) as ItemQuery
					);

					return {
						...state,
						items: {
							...state.items,
							[ itemQuery ]: { data: ids },
						},
						data: {
							...state.data,
							...nextResources,
						},
					};

				case TYPES.CREATE_ITEM_REQUEST:
					return {
						...state,
						requesting: {
							...state.requesting,
							[ getRequestIdentifier(
								CRUD_ACTIONS.CREATE_ITEM,
								payload.query
							) ]: true,
						},
					};

				case TYPES.DELETE_ITEM_REQUEST:
					return {
						...state,
						requesting: {
							...state.requesting,
							[ getRequestIdentifier(
								CRUD_ACTIONS.DELETE_ITEM,
								payload.key,
								payload.force
							) ]: true,
						},
					};

				case TYPES.UPDATE_ITEM_REQUEST:
					return {
						...state,
						requesting: {
							...state.requesting,
							[ getRequestIdentifier(
								CRUD_ACTIONS.UPDATE_ITEM,
								payload.key,
								payload.query
							) ]: true,
						},
					};
			}
		}
		if ( additionalReducer ) {
			return additionalReducer( state, payload );
		}
		return state;
	};

	return reducer;
};
