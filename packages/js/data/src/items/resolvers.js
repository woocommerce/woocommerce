/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { setError, setItems, setItemsTotalCount } from './actions';
import { request } from '../utils';

export function* getItems( itemType, query ) {
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

export function* getReviewsTotalCount( itemType, query ) {
	yield getItemsTotalCount( itemType, query );
}

export function* getItemsTotalCount( itemType, query ) {
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
