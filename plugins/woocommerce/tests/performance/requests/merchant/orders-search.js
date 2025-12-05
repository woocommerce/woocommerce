/* eslint-disable import/no-unresolved */
/* eslint-disable no-shadow */
/**
 * External dependencies
 */
import { sleep, check, group } from 'k6';
import http from 'k6/http';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.1.0/index.js';

/**
 * Internal dependencies
 */
import {
	base_url,
	hpos_status,
	admin_orders_base_url,
	hpos_admin_orders_base_url,
	think_time_min,
	think_time_max,
	product_search_term,
	addresses_customer_billing_email,
	addresses_customer_billing_postcode,
} from '../../config.js';
import {
	htmlRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonNonStandardHeaders,
} from '../../headers.js';

// Change URL if HPOS is enabled and being used
let admin_orders_base;
let admin_search_assert;
if ( hpos_status === true ) {
	admin_orders_base = hpos_admin_orders_base_url;
	admin_search_assert = 'tbody id="the-list"';
} else {
	admin_orders_base = `${ admin_orders_base_url }&post_status=all`;
	admin_search_assert = 'Search results for:';
}

export function ordersSearch() {
	let response;

	group( 'Orders Search', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(
			`${ base_url }/wp-admin/${ admin_orders_base }` +
				`&s=${ product_search_term }&action=-1&m=0&_customer_user&` +
				`paged=1&action2=-1`,
			{
				headers: requestHeaders,
				tags: { name: 'Merchant - Search Orders By Product' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			"body contains: 'Search results' subtitle": ( response ) =>
				response.body.includes( `${ admin_search_assert }` ),
		} );

		response = http.get(
			`${ base_url }/wp-admin/${ admin_orders_base }` +
				`&s=${ addresses_customer_billing_email }&action=-1&m=0&_customer_user&` +
				`paged=1&action2=-1`,
			{
				headers: requestHeaders,
				tags: { name: 'Merchant - Search Orders By Customer Email' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			"body contains: 'Search results' subtitle": ( response ) =>
				response.body.includes( `${ admin_search_assert }` ),
		} );

		response = http.get(
			`${ base_url }/wp-admin/${ admin_orders_base }` +
				`&s=${ addresses_customer_billing_postcode }&action=-1&m=0&_customer_user&` +
				`paged=1&action2=-1`,
			{
				headers: requestHeaders,
				tags: { name: 'Merchant - Search Orders By Customer Address' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			"body contains: 'Search results' subtitle": ( response ) =>
				response.body.includes( `${ admin_search_assert }` ),
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	ordersSearch();
}
