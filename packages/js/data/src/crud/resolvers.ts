/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { cleanQuery, getUrlParameters, getRestPath, parseId } from './utils';
import {
	getItemError,
	getItemSuccess,
	getItemsError,
	getItemsSuccess,
} from './actions';
import { IdQuery, Item, ItemQuery } from './types';
import { request } from '../utils';

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
		const urlParameters = getUrlParameters( namespace, idQuery );
		const { id, key } = parseId( idQuery, urlParameters );
		try {
			const item: Item = yield apiFetch( {
				path: getRestPath(
					`${ namespace }/${ id }`,
					{},
					urlParameters
				),
				method: 'GET',
			} );

			yield getItemSuccess( key, item );
			return item;
		} catch ( error ) {
			yield getItemError( key, error );
			throw error;
		}
	};

	const getItems = function* ( query?: Partial< ItemQuery > ) {
		const urlParameters = getUrlParameters( namespace, query || {} );
		const resourceQuery = cleanQuery( query || {}, namespace );

		// Require ID when requesting specific fields to later update the resource data.
		if (
			resourceQuery &&
			resourceQuery._fields &&
			! resourceQuery._fields.includes( 'id' )
		) {
			resourceQuery._fields = [ 'id', ...resourceQuery._fields ];
		}

		try {
			const path = getRestPath( namespace, {}, urlParameters );
			const { items }: { items: Item[] } = yield request<
				ItemQuery,
				Item
			>( path, resourceQuery );

			yield getItemsSuccess( query, items, urlParameters );
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
