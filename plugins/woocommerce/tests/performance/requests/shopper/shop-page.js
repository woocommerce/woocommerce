/* eslint-disable import/no-unresolved */
/**
 * External dependencies
 */
import { sleep, group } from 'k6';
import http from 'k6/http';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.1.0/index.js';

/**
 * Internal dependencies
 */
import {
	base_url,
	think_time_min,
	think_time_max,
	FOOTER_TEXT,
	STORE_NAME,
} from '../../config.js';
import {
	htmlRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonNonStandardHeaders,
} from '../../headers.js';
import { checkResponse } from '../../utils.js';

export function shopPage() {
	let response;

	group( 'Shop Page', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get( `${ base_url }/shop`, {
			headers: requestHeaders,
			tags: { name: 'Shopper - Shop Page' },
		} );
		checkResponse( response, 200, {
			title: `Shop – ${ STORE_NAME }`,
			body: '<header class="woocommerce-products-header">',
			footer: FOOTER_TEXT,
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	shopPage();
}
