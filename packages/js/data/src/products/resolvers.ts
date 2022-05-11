/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { WC_PRODUCT_NAMESPACE } from './constants';
import { setError, setProducts, setProductsTotalCount } from './actions';
import { Product, ProductQuery } from './types';
import { fetchWithHeaders } from '../controls';

type ProductGetResponse = Product[] | ( { data: Product[] } & Response );

function* request( query: Partial< ProductQuery > ) {
	const url: string = addQueryArgs( `${ WC_PRODUCT_NAMESPACE }`, query );
	const isUnboundedRequest = query.per_page === -1;
	const fetch = isUnboundedRequest ? apiFetch : fetchWithHeaders;
	const response: ProductGetResponse = yield fetch( {
		path: url,
		method: 'GET',
	} );

	if ( isUnboundedRequest && ! ( 'data' in response ) ) {
		return { items: response, totalCount: response.length };
	}
	if ( ! isUnboundedRequest && 'data' in response ) {
		const totalCount = parseInt(
			response.headers.get( 'x-wp-total' ) || '',
			10
		);

		return { items: response.data, totalCount };
	}
}

export function* getProducts( query: Partial< ProductQuery > ) {
	try {
		const {
			items,
			totalCount,
		}: { items: Product[]; totalCount: number } = yield request( query );
		yield setProductsTotalCount( query, totalCount );
		yield setProducts( query, items, totalCount );
		return items;
	} catch ( error ) {
		yield setError( query, error );
		return error;
	}
}

export function* getProductsTotalCount( query: Partial< ProductQuery > ) {
	try {
		const totalsQuery = {
			...query,
			page: 1,
			per_page: 1,
		};
		const { totalCount } = yield request( totalsQuery );
		yield setProductsTotalCount( query, totalCount );
		return totalCount;
	} catch ( error ) {
		yield setError( query, error );
	}
}
