/* eslint-disable no-shadow */
/* eslint-disable import/no-unresolved */
/**
 * External dependencies
 */
import { sleep, check, group } from 'k6';
import http from 'k6/http';
import {
	randomIntBetween,
	findBetween,
} from 'https://jslib.k6.io/k6-utils/1.1.0/index.js';

/**
 * Internal dependencies
 */
import { base_url, think_time_min, think_time_max } from '../../config.js';
import {
	htmlRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonNonStandardHeaders,
} from '../../headers.js';

export function myAccountOrders() {
	let response;
	let my_account_order_id;

	group( 'My Account', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get( `${ base_url }/my-account`, {
			headers: requestHeaders,
			tags: { name: 'Shopper - My Account' },
		} );
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			'body contains: my account welcome message': ( response ) =>
				response.body.includes(
					'From your account dashboard you can view'
				),
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );

	group( 'My Account Orders', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get( `${ base_url }/my-account/orders/`, {
			headers: requestHeaders,
			tags: { name: 'Shopper - My Account Orders' },
		} );
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			"body contains: 'Orders' title": ( response ) =>
				response.body.includes( '>Orders</h1>' ),
		} );
		my_account_order_id = findBetween(
			response.body,
			'my-account/view-order/',
			'/">'
		);
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );

	group( 'My Account Open Order', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(
			`${ base_url }/my-account/view-order/${ my_account_order_id }`,
			{
				headers: requestHeaders,
				tags: { name: 'Shopper - My Account Open Order' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			"body contains: 'Order number' title": ( response ) =>
				response.body.includes( `${ my_account_order_id }</h1>` ),
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	myAccountOrders();
}
