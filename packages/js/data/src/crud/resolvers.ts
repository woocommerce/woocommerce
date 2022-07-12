/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { getRestPath, parseId } from './utils';
import {
	getItemError,
	getItemSuccess,
	getItemsError,
	getItemsSuccess,
} from './actions';
import { request } from '../utils';
import { IdType, IdQuery, Item, ItemQuery } from './types';

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
	const getItem = function* ( idQuery: IdQuery ) {
		const { id, parent_id } = parseId( idQuery );
		try {
			const item: Item = yield apiFetch( {
				path: getRestPath(
					`${ namespace }/${ id }`,
					{},
					parent_id ? [ parent_id ] : []
				),
				method: 'GET',
			} );

			yield getItemSuccess( item.id, item );
			return item;
		} catch ( error ) {
			yield getItemError( id, error );
			throw error;
		}
	};

	const getItems = function* (
		query?: Partial< ItemQuery >,
		...urlParameters: string[]
	) {
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
			const parent_id = query.parent_id as IdType;
			const path = getRestPath(
				namespace,
				{},
				parent_id ? [ parent_id ] : []
			);
			const { items }: { items: Item[] } = yield request<
				ItemQuery,
				Item
			>( path, resourceQuery );

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
