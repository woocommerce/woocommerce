/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { WC_ORDER_NAMESPACE } from './constants';
import { setError, setOrders, setOrdersTotalCount } from './actions';
import { Order, OrderQuery } from './types';
import { fetchWithHeaders } from '../controls';

type OrderGetResponse = Order[] | ( { data: Order[] } & Response );

type RequestError = {
	code: string;
	data: {
		status: number;
	};
	message: string;
};

function* request( query: Partial< OrderQuery > ) {
	const url: string = addQueryArgs( `${ WC_ORDER_NAMESPACE }`, query );
	const isUnboundedRequest = query.per_page === -1;
	const fetch = isUnboundedRequest ? apiFetch : fetchWithHeaders;
	const response: OrderGetResponse = yield fetch( {
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

export function* getOrders( query: Partial< OrderQuery > ) {
	try {
		const {
			items,
			totalCount,
		}: { items: Order[]; totalCount: number } = yield request( query );
		yield setOrdersTotalCount( query, totalCount );
		yield setOrders( query, items, totalCount );
		return items;
	} catch ( error ) {
		yield setError( query, error );
		return error;
	}
}

export function* getOrdersTotalCount( query: Partial< OrderQuery > ) {
	try {
		const totalsQuery = {
			...query,
			page: 1,
			per_page: 1,
		};
		const { totalCount } = yield request( totalsQuery );
		yield setOrdersTotalCount( query, totalCount );
		return totalCount;
	} catch ( error ) {
		yield setError( query, error );
		return error;
	}
}
