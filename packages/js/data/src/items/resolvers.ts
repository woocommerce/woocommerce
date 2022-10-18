/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { setError, setItems, setItemsTotalCount } from './actions';
import { request } from '../utils';
import { ItemType, Query } from './types';

export function* getItems( itemType: ItemType, query: Query ) {
	try {
		const endpoint =
			itemType === 'categories' ? 'products/categories' : itemType;
		const { items, totalCount } = yield request(
			`${ NAMESPACE }/${ endpoint }`,
			query
		);

		yield setItemsTotalCount( itemType, query, totalCount );
		yield setItems( itemType, query, items );
	} catch ( error ) {
		yield setError( itemType, query, error );
	}
}

export function* getItemsTotalCount( itemType: ItemType, query: Query ) {
	try {
		const totalsQuery = {
			...query,
			page: 1,
			per_page: 1,
		};
		const endpoint =
			itemType === 'categories' ? 'products/categories' : itemType;
		const { totalCount } = yield request(
			`${ NAMESPACE }/${ endpoint }`,
			totalsQuery
		);
		yield setItemsTotalCount( itemType, query, totalCount );
	} catch ( error ) {
		yield setError( itemType, query, error );
	}
}

export function* getReviewsTotalCount( itemType: ItemType, query: Query ) {
	yield getItemsTotalCount( itemType, query );
}
