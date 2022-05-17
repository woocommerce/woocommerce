/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { setError, setItems, setItemsTotalCount } from './actions';
import { fetchWithHeaders } from '../controls';

function* request( itemType, query ) {
	const endpoint =
		itemType === 'categories' ? 'products/categories' : itemType;
	const url = addQueryArgs( `${ NAMESPACE }/${ endpoint }`, query );
	const isUnboundedRequest = query.per_page === -1;
	const fetch = isUnboundedRequest ? apiFetch : fetchWithHeaders;
	const response = yield fetch( {
		path: url,
		method: 'GET',
	} );

	if ( isUnboundedRequest ) {
		return { items: response, totalCount: response.length };
	}
	const totalCount = parseInt( response.headers.get( 'x-wp-total' ), 10 );

	return { items: response.data, totalCount };
}

export function* getItems( itemType, query ) {
	try {
		const { items, totalCount } = yield request( itemType, query );
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
		const { totalCount } = yield request( itemType, totalsQuery );
		yield setItemsTotalCount( itemType, query, totalCount );
	} catch ( error ) {
		yield setError( itemType, query, error );
	}
}
