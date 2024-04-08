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
					let items = state.items;
					let itemsCount = state.itemsCount;

					if ( options.optimisticQueryUpdate ) {
						const getItemQuery = getRequestIdentifier(
							CRUD_ACTIONS.GET_ITEMS,
							options.optimisticQueryUpdate as ItemQuery
						);

						const getItemCountQuery = getTotalCountResourceName(
							CRUD_ACTIONS.GET_ITEMS,
							options.optimisticQueryUpdate as ItemQuery
						);

						items = {
							...state.items,
							[ getItemQuery ]: {
								...state.items[ getItemQuery ],
								data: [
									...( state.items[ getItemQuery ]?.data ||
										[] ),
									payload.key,
								],
							},
						};

						itemsCount = {
							...state.itemsCount,
							[ getItemCountQuery ]:
								( state.itemsCount[ getItemCountQuery ] || 0 ) +
								1,
						};
					}

					const newState = {
						...state,
						items,
						itemsCount,
						data: {
							...itemData,
							[ payload.key ]: {
								...( itemData[ payload.key ] || {} ),
								...payload.item,
							},
						},
						requesting: {
							...state.requesting,
							[ createItemSuccessRequestId ]: false,
						},
					};
					return newState;
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

					// console.log( '(reducer) Item Query: ', itemQuery );

					const nextItemsDos = {
						...state.items,
						[ itemQuery ]: { data: ids },
					};

					// console.log( '(reducer) Next Items: ', nextItemsDos );

					return {
						...state,
						items: nextItemsDos,
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
