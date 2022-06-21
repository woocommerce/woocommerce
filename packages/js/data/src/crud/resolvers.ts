/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	getResourceSuccess,
	getResourceError,
	getResourcesError,
	getResourcesSuccess,
} from './actions';
import { request } from '../utils';
import { Resource, ResourceQuery } from './types';

export const createResolvers = (
	resourceName: string,
	pluralResourceName: string,
	endpoint: string
) => {
	const getResources = function* ( query: Partial< ResourceQuery > ) {
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
	};

	const getResource = function* ( id: number ) {
		try {
			const item: Resource = yield apiFetch( {
				path: `${ endpoint }/${ id }`,
				method: 'GET',
			} );

			yield getResourceSuccess( item.id, item );
			return item;
		} catch ( error ) {
			yield getResourceError( id, error );
			throw error;
		}
	};

	return {
		[ `get${ pluralResourceName }` ]: getResources,
		[ `get${ resourceName }` ]: getResource,
	};
};
