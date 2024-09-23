/**
 * External dependencies
 */
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import { Actions } from './actions';
import CRUD_ACTIONS from './crud-actions';
import {
	filterDataByKeys,
	getRequestIdentifier,
	organizeItemsById,
} from './utils';
import { getTotalCountResourceName } from '../utils';
import { TYPES } from './action-types';
import type { IdType, Item, ItemQuery } from './types';

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
					const { options = {} } = payload;

					const { objItems, ids } = organizeItemsById(
						[ payload.item ],
						options.optimisticUrlParameters,
						itemData
					);

					const data = {
						...itemData,
						...objItems,
					};

					const createItemSuccessRequestId = getRequestIdentifier(
						CRUD_ACTIONS.CREATE_ITEM,
						ids[ 0 ],
						payload.query
					);

					const getItemQueryId = getRequestIdentifier(
						CRUD_ACTIONS.GET_ITEMS,
						options.optimisticQueryUpdate
					);

					const getItemCountQueryId = getTotalCountResourceName(
						CRUD_ACTIONS.GET_ITEMS,
						options?.optimisticQueryUpdate || {}
					);

					let currentItems = state.items;

					const currentItemsByQueryId =
						currentItems[ getItemQueryId ]?.data || [];

					let nextItemsData = [ ...currentItemsByQueryId, ...ids ];

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

							/*
							 * Pick the data to sort by the order_by property,
							 * from the data store,
							 * based on the nextItemsData ids.
							 */
							let sourceDataToOrderBy = Object.values(
								filterDataByKeys( data, nextItemsData )
							) as Item[];

							sourceDataToOrderBy = sourceDataToOrderBy.sort(
								( a, b ) =>
									String( a[ order_by ] as IdType )
										.toLowerCase()
										.localeCompare(
											String(
												b[ order_by ] as IdType
											).toLowerCase()
										)
							);

							// Pick the ids from the sorted data.
							const { ids: sortedIds } = organizeItemsById(
								sourceDataToOrderBy,
								options.optimisticUrlParameters
							);

							// Update the items data with the sorted ids.
							nextItemsData = sortedIds;
						}

						currentItems = {
							...currentItems,
							[ getItemQueryId ]: {
								data: nextItemsData,
							},
						};

						itemsCount = {
							...state.itemsCount,
							[ getItemCountQueryId ]: nextItemsData.length,
						};
					}

					return {
						...state,
						items: currentItems,
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
					const updateItemErrorRequestId = getRequestIdentifier(
						payload.errorType,
						payload.key,
						payload.query
					);
					return {
						...state,
						errors: {
							...state.errors,
							[ updateItemErrorRequestId ]: payload.error,
						},
						requesting: {
							...state.requesting,
							[ updateItemErrorRequestId ]: false,
						},
					};

				case TYPES.GET_ITEMS_SUCCESS:
					const { objItems, ids } = organizeItemsById(
						payload.items,
						payload.urlParameters,
						itemData
					);

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
							...objItems,
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
