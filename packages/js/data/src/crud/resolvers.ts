/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { getParamsFromQuery, getRestPath, parseId } from './utils';
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
	urlParameters: string[];
};

export const createResolvers = ( {
	resourceName,
	pluralResourceName,
	namespace,
	urlParameters,
}: ResolverOptions ) => {
	const getItem = function* ( idQuery: IdQuery ) {
		const params = parseId( idQuery, urlParameters );
		const { id } = params;
		try {
			const item: Item = yield apiFetch( {
				path: getRestPath( `${ namespace }/${ id }`, {}, params ),
				method: 'GET',
			} );

			yield getItemSuccess( item.id, item, urlParameters );
			return item;
		} catch ( error ) {
			yield getItemError( id, error, urlParameters );
			throw error;
		}
	};

	const getItems = function* ( query?: Partial< ItemQuery > ) {
		// Require ID when requesting specific fields to later update the resource data.
		const resourceQuery = query ? { ...query } : {};
		const params = getParamsFromQuery( resourceQuery, urlParameters );

		if (
			resourceQuery &&
			resourceQuery._fields &&
			! resourceQuery._fields.includes( 'id' )
		) {
			resourceQuery._fields = [ 'id', ...resourceQuery._fields ];
		}

		try {
			const path = getRestPath( namespace, {}, params );
			const { items }: { items: Item[] } = yield request<
				ItemQuery,
				Item
			>( path, resourceQuery );

			yield getItemsSuccess( query, items, urlParameters );
			return items;
		} catch ( error ) {
			yield getItemsError( query, error, urlParameters );
			throw error;
		}
	};

	return {
		[ `get${ resourceName }` ]: getItem,
		[ `get${ pluralResourceName }` ]: getItems,
	};
};
