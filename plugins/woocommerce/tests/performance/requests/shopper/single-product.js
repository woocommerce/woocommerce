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
	product_url,
	product_sku,
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

export function singleProduct() {
	let response;

	group( 'Product Page', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get( `${ base_url }/product/${ product_url }`, {
			headers: requestHeaders,
			tags: { name: 'Shopper - Product Page' },
		} );
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			[ `title is: ${ product_url } – ${ STORE_NAME }` ]: ( r ) => {
				const title_actual = r.html().find( 'head title' ).text();
				const title_expected = new RegExp(
					`${ product_url } – ${ STORE_NAME }`,
					'i'
				);
				return title_actual.match( title_expected );
			},
			'body contains: product SKU': ( r ) =>
				r.body.includes( `class="sku">${ product_sku }` ),
			'footer contains: Built with WooCommerce': ( r ) =>
				r.html().find( 'body footer' ).text().includes( FOOTER_TEXT ),
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	singleProduct();
}
