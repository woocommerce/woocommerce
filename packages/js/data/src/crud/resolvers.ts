/**
 * Internal dependencies
 */
import { getResourcesError, getResourcesSuccess } from './actions';
import { request } from '../utils';
import { Resource, ResourceQuery } from './types';

export const createResolvers = ( resourceName: string, endpoint: string ) => {
	const getResources = function*( query: Partial< ResourceQuery > ) {
		// Require ID when requesting specific fields to later update the resource data.
		const resourceQuery = { ...query };

		if (
			resourceQuery &&
			resourceQuery._fields &&
			! resourceQuery._fields.includes( 'id' )
		) {
			resourceQuery._fields = [ 'id', ...resourceQuery._fields ];
		}

		try {
			const { items }: { items: Resource[] } = yield request<
				ResourceQuery,
				Resource
			>( endpoint, resourceQuery );

			yield getResourcesSuccess( query, items );
			return items;
		} catch ( error ) {
			yield getResourcesError( query, error );
			throw error;
		}
	}

    return {
		[ `get${ resourceName }` ]: getResources,
	};
};
