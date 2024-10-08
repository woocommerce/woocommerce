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
	product_sku,
	product_id,
	think_time_min,
	think_time_max,
	STORE_NAME,
	FOOTER_TEXT,
} from '../../config.js';
import {
	htmlRequestHeader,
	jsonRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonPostRequestHeaders,
	commonNonStandardHeaders,
} from '../../headers.js';

export function cart() {
	group( 'Product Page Add to cart', function () {
		const requestheaders = Object.assign(
			{},
			jsonRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		const response = http.post(
			`${ base_url }/?wc-ajax=add_to_cart`,
			{
				product_sku: `${ product_sku }`,
				product_id: `${ product_id }`,
				quantity: '1',
			},
			{
				headers: requestheaders,
				tags: { name: 'Shopper - wc-ajax=add_to_cart' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );

	group( 'View Cart', function () {
		const requestheaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		const response = http.get( `${ base_url }/cart`, {
			headers: requestheaders,
			tags: { name: 'Shopper - View Cart' },
		} );
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			[ `title is: "Cart – ${ STORE_NAME }"` ]: ( r ) =>
				r.html().find( 'head title' ).text() ===
				`Cart – ${ STORE_NAME }`,
			"body does not contain: 'your cart is currently empty'": ( r ) =>
				! r.body.includes( 'Your cart is currently empty.' ),
			'footer contains: Built with WooCommerce': ( r ) =>
				r.html().find( 'body footer' ).text().includes( FOOTER_TEXT ),
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	cart();
}
