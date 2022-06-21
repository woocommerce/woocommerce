/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import { Resource, ResourceQuery } from './types';
import { ResourceState } from './reducer';
import CRUD_ACTIONS from './crud-actions';

export const createSelectors = (
	resourceName: string,
	pluralResourceName: string
) => {
	const getResources = createSelector(
		( state: ResourceState, query: ResourceQuery ) => {
			const resourceQuery = getResourceName( resourceName, query );

			const ids = state.resources[ resourceQuery ]
				? state.resources[ resourceQuery ].data
				: undefined;

			if ( ! ids ) {
				return null;
			}

			if ( query._fields ) {
				return ids.map( ( id: number ) => {
					return query._fields.reduce(
						( resource: Partial< Resource >, field: string ) => {
							return {
								...resource,
								[ field ]: state.data[ id ][ field ],
							};
						},
						{} as Partial< Resource >
					);
				} );
			}

			return ids.map( ( id: number ) => {
				return state.data[ id ];
			} );
		},
		( state, query ) => {
			const resourceQuery = getResourceName( resourceName, query );
			const ids = state.resources[ resourceQuery ]
				? state.resources[ resourceQuery ].data
				: undefined;
			return [
				state.resources[ resourceQuery ],
				...( ids || [] ).map( ( id: number ) => {
					return state.data[ id ];
				} ),
			];
		}
	);

	const getResourcesError = (
		state: ResourceState,
		query: ResourceQuery
	) => {
		const resourceQuery = getResourceName( CRUD_ACTIONS.GET_ITEMS, query );
		return state.errors[ resourceQuery ];
	};

	const getResource = ( state: ResourceState, id: number ) => {
		return state.data[ id ];
	};

	const getResourceError = ( state: ResourceState, id: number ) => {
		const resourceQuery = getResourceName( CRUD_ACTIONS.GET_ITEM, { id } );
		return state.errors[ resourceQuery ];
	};

	return {
		[ `get${ resourceName }` ]: getResource,
		[ `get${ pluralResourceName }` ]: getResources,
		[ `get${ resourceName }Error` ]: getResourceError,
		[ `get${ pluralResourceName }Error` ]: getResourcesError,
	};
};
