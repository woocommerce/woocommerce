/* eslint-disable import/no-unresolved */
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

export function homePage() {
	let response;

	group( 'Home Page', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get( `${ base_url }/`, {
			headers: requestHeaders,
			tags: { name: 'Shopper - Site Root' },
		} );

		check( response, {
			'is status 200': ( r ) => r.status === 200,
			[ `title is: "${ STORE_NAME }"` ]: ( r ) =>
				r.html().find( 'head title' ).text() === STORE_NAME,
			'footer contains: Built with WooCommerce': ( r ) =>
				r.html().find( 'body footer' ).text().includes( FOOTER_TEXT ),
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	homePage();
}
