/* eslint-disable import/no-unresolved */
/**
 * External dependencies
 */
import { sleep, group } from 'k6';
import http from 'k6/http';
import {
	randomIntBetween,
	findBetween,
} from 'https://jslib.k6.io/k6-utils/1.1.0/index.js';

/**
 * Internal dependencies
 */
import {
	base_url,
	think_time_min,
	think_time_max,
	STORE_NAME,
	FOOTER_TEXT,
} from '../../config.js';
import {
	htmlRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonNonStandardHeaders,
} from '../../headers.js';
import { checkResponse } from '../../utils.js';

export function myAccountOrders() {
	let my_account_order_id;

	group( 'My Account', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		const response = http.get( `${ base_url }/my-account`, {
			headers: requestHeaders,
			tags: { name: 'Shopper - My Account' },
		} );
		checkResponse( response, 200, {
			title: `My account – ${ STORE_NAME }`,
			body: 'From your account dashboard you can view',
			footer: FOOTER_TEXT,
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

		const response = http.get( `${ base_url }/my-account/orders/`, {
			headers: requestHeaders,
			tags: { name: 'Shopper - My Account Orders' },
		} );

		checkResponse( response, 200, {
			title: `Orders – ${ STORE_NAME }`,
			body: '>Orders</h1>',
			footer: FOOTER_TEXT,
		} );

		my_account_order_id = findBetween(
			response.body,
			'my-account/view-order/',
			'/"'
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

		const response = http.get(
			`${ base_url }/my-account/view-order/${ my_account_order_id }`,
			{
				headers: requestHeaders,
				tags: { name: 'Shopper - My Account Open Order' },
			}
		);

		checkResponse( response, 200, {
			title: `My account – ${ STORE_NAME }`,
			body: my_account_order_id,
			footer: FOOTER_TEXT,
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	myAccountOrders();
}
