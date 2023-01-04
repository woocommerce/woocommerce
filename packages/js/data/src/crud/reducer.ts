/**
 * External dependencies
 */
import { camelCase } from 'lodash';
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import { Actions } from './actions';
import CRUD_ACTIONS from './crud-actions';
import { getKey, getRequestKey } from './utils';
import { getResourceName, getTotalCountResourceName } from '../utils';
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

export const createReducer = () => {
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
					const createItemErrorResourceName = getResourceName(
						payload.errorType,
						payload.query || {}
					);
					return {
						...state,
						errors: {
							...state.errors,
							[ createItemErrorResourceName ]: payload.error,
						},
						requesting: {
							...state.requesting,
							[ createItemErrorResourceName ]: false,
						},
					};
				case TYPES.GET_ITEMS_TOTAL_COUNT_ERROR:
				case TYPES.GET_ITEMS_ERROR:
					return {
						...state,
						errors: {
							...state.errors,
							[ getResourceName(
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

				case TYPES.CREATE_ITEM_SUCCESS:
					const createItemSuccessResourceName = getResourceName(
						CRUD_ACTIONS.CREATE_ITEM,
						{
							key: payload.key,
							query: payload.query,
						}
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
							[ createItemSuccessResourceName ]: false,
						},
					};

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
					const updateItemSuccessResourceName = getResourceName(
						CRUD_ACTIONS.UPDATE_ITEM,
						{
							key: payload.key,
							query: payload.query,
						}
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
							[ updateItemSuccessResourceName ]: false,
						},
					};

				case TYPES.DELETE_ITEM_SUCCESS:
					const deleteItemSuccessResourceName = getResourceName(
						CRUD_ACTIONS.DELETE_ITEM,
						{
							key: payload.key,
							force: payload.force,
						}
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
							[ deleteItemSuccessResourceName ]: false,
						},
					};

				case TYPES.DELETE_ITEM_ERROR:
					const deleteItemErrorResourceName = getResourceName(
						payload.errorType,
						{
							key: payload.key,
							force: payload.force,
						}
					);
					return {
						...state,
						errors: {
							...state.errors,
							[ deleteItemErrorResourceName ]: payload.error,
						},
						requesting: {
							...state.requesting,
							[ deleteItemErrorResourceName ]: false,
						},
					};

				case TYPES.GET_ITEM_ERROR:
					return {
						...state,
						errors: {
							...state.errors,
							[ getResourceName( payload.errorType, {
								key: payload.key,
							} ) ]: payload.error,
						},
					};

				case TYPES.UPDATE_ITEM_ERROR:
					const upateItemErrorResourceName = getResourceName(
						payload.errorType,
						{
							key: payload.key,
							query: payload.query,
						}
					);
					return {
						...state,
						errors: {
							...state.errors,
							[ upateItemErrorResourceName ]: payload.error,
						},
						requesting: {
							...state.requesting,
							[ upateItemErrorResourceName ]: false,
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

					const itemQuery = getResourceName(
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
							[ getRequestKey(
								camelCase( CRUD_ACTIONS.CREATE_ITEM ),
								payload.query
							) ]: true,
						},
					};

				case TYPES.DELETE_ITEM_REQUEST:
					return {
						...state,
						requesting: {
							...state.requesting,
							[ getRequestKey(
								camelCase( CRUD_ACTIONS.DELETE_ITEM ),
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
							[ getRequestKey(
								camelCase( CRUD_ACTIONS.UPDATE_ITEM ),
								payload.key,
								payload.query
							) ]: true,
						},
					};

				default:
					return state;
			}
		}
		return state;
	};

	return reducer;
};
