/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	getItemError,
	getItemSuccess,
	getItemsError,
	getItemsSuccess,
} from './actions';
import { request } from '../utils';
import { Item, ItemQuery } from './types';

type ResolverOptions = {
	resourceName: string;
	pluralResourceName: string;
	namespace: string;
};

export const createResolvers = ( {
	resourceName,
	pluralResourceName,
	namespace,
}: ResolverOptions ) => {
	const getItem = function* ( id: number ) {
		try {
			const item: Item = yield apiFetch( {
				path: `${ namespace }/${ id }`,
				method: 'GET',
			} );

			yield getItemSuccess( item.id, item );
			return item;
		} catch ( error ) {
			yield getItemError( id, error );
			throw error;
		}
	};

	const getItems = function* ( query?: Partial< ItemQuery > ) {
		// Require ID when requesting specific fields to later update the resource data.
		const resourceQuery = query ? { ...query } : {};

		if (
			resourceQuery &&
			resourceQuery._fields &&
			! resourceQuery._fields.includes( 'id' )
		) {
			resourceQuery._fields = [ 'id', ...resourceQuery._fields ];
		}

		try {
			const { items }: { items: Item[] } = yield request<
				ItemQuery,
				Item
			>( namespace, resourceQuery );

			yield getItemsSuccess( query, items );
			return items;
		} catch ( error ) {
			yield getItemsError( query, error );
			throw error;
		}
	};

	return {
		[ `get${ resourceName }` ]: getItem,
		[ `get${ pluralResourceName }` ]: getItems,
	};
};
