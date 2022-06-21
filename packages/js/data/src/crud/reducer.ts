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
import { Resource, ResourceQuery } from './types';
import { TYPES } from './action-types';

export type ResourceState = {
	resources: Record<
		string,
		{
			data: number[];
		}
	>;
	errors: Record< string, unknown >;
	data: Record< number, Resource >;
};

export const createReducer = ( resourceName: string ) => {
	const reducer: Reducer< ResourceState, Actions > = (
		state = {
			resources: {},
			errors: {},
			data: {},
		},
		payload
	) => {
		if ( payload && 'type' in payload ) {
			switch ( payload.type ) {
				case TYPES.GET_RESOURCES_SUCCESS:
					const ids: number[] = [];

					const nextResources = payload.resources.reduce<
						Record< number, Resource >
					>( ( result, resource ) => {
						ids.push( resource.id );
						result[ resource.id ] = {
							...( state.data[ resource.id ] || {} ),
							...resource,
						};
						return result;
					}, {} );

					const resourceQuery = getResourceName(
						resourceName,
						payload.query as ResourceQuery
					);

					return {
						...state,
						resources: {
							...state.resources,
							[ resourceQuery ]: { data: ids },
						},
						data: {
							...state.data,
							...nextResources,
						},
					};

				case TYPES.GET_RESOURCES_ERROR:
					return {
						...state,
						errors: {
							...state.errors,
							[ getResourceName(
								CRUD_ACTIONS.GET_ITEMS,
								payload.query as ResourceQuery
							) ]: payload.error,
						},
					};

				case TYPES.GET_RESOURCE_SUCCESS:
					const resourceData = state.data || {};
					return {
						...state,
						data: {
							...resourceData,
							[ payload.id ]: {
								...( resourceData[ payload.id ] || {} ),
								...payload.resource,
							},
						},
					};

				case TYPES.GET_RESOURCE_ERROR:
					return {
						...state,
						errors: {
							...state.errors,
							[ getResourceName( CRUD_ACTIONS.GET_ITEM, {
								id: payload.id,
							} ) ]: payload.error,
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
