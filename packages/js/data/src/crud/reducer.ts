/**
 * External dependencies
 */
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import { Actions } from './actions';
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
								resourceName,
								payload.query as ResourceQuery
							) ]: payload.error,
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
