/**
 * Internal dependencies
 */
import {
	getItemsError,
	getItemsSuccess,
	getItemsTotalCountError,
	getItemsTotalCountSuccess,
} from '../crud/actions';
import { getUrlParameters, getRestPath, cleanQuery } from '../crud/utils';
import { Item, ItemQuery } from '../crud/types';
import { request } from '../utils';
import { WC_TAX_CLASSES_NAMESPACE } from './constants';

export function* getTaxClasses( query?: Partial< ItemQuery > ) {
	const urlParameters = getUrlParameters(
		WC_TAX_CLASSES_NAMESPACE,
		query || {}
	);
	const resourceQuery = cleanQuery( query || {}, WC_TAX_CLASSES_NAMESPACE );

	try {
		const path = getRestPath(
			WC_TAX_CLASSES_NAMESPACE,
			query || {},
			urlParameters
		);
		const { items }: { items: Item[]; totalCount: number } = yield request<
			ItemQuery,
			Item
		>( path, resourceQuery );

		yield getItemsTotalCountSuccess( query, items.length );
		yield getItemsSuccess(
			query,
			items.map( ( item ) => ( { ...item, id: item.id ?? item.slug } ) ),
			urlParameters
		);
		return items;
	} catch ( error ) {
		yield getItemsTotalCountError( query, error );
		yield getItemsError( query, error );
		throw error;
	}
}
