/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { controls } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { cleanQuery, getUrlParameters, getRestPath, parseId } from './utils';
import {
	getItemError,
	getItemSuccess,
	getItemsError,
	getItemsSuccess,
	getItemsTotalCountSuccess,
	getItemsTotalCountError,
} from './actions';
import { IdQuery, Item, ItemQuery } from './types';
import { request } from '../utils';

type ResolverOptions = {
	storeName: string;
	resourceName: string;
	pluralResourceName: string;
	namespace: string;
};

export const createResolvers = ( {
	storeName,
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
			const path = getRestPath( namespace, query || {}, urlParameters );
			const { items, totalCount }: { items: Item[]; totalCount: number } =
				yield request< ItemQuery, Item >( path, resourceQuery );

			yield getItemsTotalCountSuccess( query, totalCount );
			yield controls.dispatch(
				storeName,
				'finishResolution',
				`get${ pluralResourceName }TotalCount`,
				[ query ]
			);
			yield getItemsSuccess( query, items, urlParameters );
			for ( const i of items ) {
				if ( i.id ) {
					yield controls.dispatch(
						storeName,
						'finishResolution',
						`get${ resourceName }`,
						[ i.id ]
					);
				}
			}
			return items;
		} catch ( error ) {
			yield getItemsTotalCountError( query, error );
			yield getItemsError( query, error );
			throw error;
		}
	};

	const getItemsTotalCount = function* ( query?: Partial< ItemQuery > ) {
		const totalsQuery = {
			...( query || {} ),
			page: 1,
			per_page: 1,
		};
		const urlParameters = getUrlParameters( namespace, totalsQuery );
		const resourceQuery = cleanQuery( totalsQuery, namespace );

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
			const { totalCount } = yield request< ItemQuery, Item >(
				path,
				totalsQuery
			);
			yield getItemsTotalCountSuccess( query, totalCount );
			return totalCount;
		} catch ( error ) {
			yield getItemsTotalCountError( query, error );
			return error;
		}
	};

	return {
		[ `get${ resourceName }` ]: getItem,
		[ `get${ pluralResourceName }` ]: getItems,
		[ `get${ pluralResourceName }TotalCount` ]: getItemsTotalCount,
	};
};
