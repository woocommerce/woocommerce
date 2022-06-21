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

export const createSelectors = ( resourceName: string ) => {
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
		const resourceQuery = getResourceName( resourceName, query );
		return state.errors[ resourceQuery ];
	};

	return {
		[ `get${ resourceName }` ]: getResources,
		[ `get${ resourceName }Error` ]: getResourcesError,
	};
};
