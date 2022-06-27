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
import { Item, ItemQuery } from './types';
import { TYPES } from './action-types';

export type ResourceState = {
	items: Record<
		string,
		{
			data: number[];
		}
	>;
	data: Record< number, Item >;
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
								payload.query as ItemQuery
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
					const ids: number[] = [];

					const nextResources = payload.items.reduce<
						Record< number, Item >
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
						payload.query as ItemQuery
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
