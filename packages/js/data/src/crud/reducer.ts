/**
 * External dependencies
 */
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import { Actions } from './actions';
import CRUD_ACTIONS from './crud-actions';
import { getResourceName } from '../utils';
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
	errors: Record< string, unknown >;
};

export const createReducer = () => {
	const reducer: Reducer< ResourceState, Actions > = (
		state = {
			items: {},
			data: {},
			errors: {},
		},
		payload
	) => {
		if ( payload && 'type' in payload ) {
			switch ( payload.type ) {
				case TYPES.CREATE_ITEM_ERROR:
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

				case TYPES.CREATE_ITEM_SUCCESS:
				case TYPES.GET_ITEM_SUCCESS:
				case TYPES.UPDATE_ITEM_SUCCESS:
					const itemData = state.data || {};
					return {
						...state,
						data: {
							...itemData,
							[ payload.id ]: {
								...( itemData[ payload.id ] || {} ),
								...payload.item,
							},
						},
					};

				case TYPES.DELETE_ITEM_SUCCESS:
					const itemIds = Object.keys( state.data );
					const nextData = itemIds.reduce< Data >(
						( items: Data, id: string ) => {
							if ( id !== payload.id.toString() ) {
								items[ id ] = state.data[ id ];
								return items;
							}
							if ( payload.force ) {
								return items;
							}
							items[ id ] = payload.item;
							return items;
						},
						{} as Data
					);

					return {
						...state,
						data: nextData,
					};

				case TYPES.DELETE_ITEM_ERROR:
				case TYPES.GET_ITEM_ERROR:
				case TYPES.UPDATE_ITEM_ERROR:
					return {
						...state,
						errors: {
							...state.errors,
							[ getResourceName( payload.errorType, {
								id: payload.id,
							} ) ]: payload.error,
						},
					};

				case TYPES.GET_ITEMS_SUCCESS:
					const ids: IdType[] = [];

					const nextResources = payload.items.reduce<
						Record< string, Item >
					>( ( result, item ) => {
						ids.push( item.id );
						result[ item.id ] = {
							...( state.data[ item.id ] || {} ),
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

				default:
					return state;
			}
		}
		return state;
	};

	return reducer;
};
