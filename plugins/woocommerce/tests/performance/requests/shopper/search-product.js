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
	product_search_term,
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

export function searchProduct() {
	let response;

	group( 'Search Product', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(
			`${ base_url }/?s=${ product_search_term }&post_type=product`,
			{
				headers: requestHeaders,
				tags: { name: 'Shopper - Search Products' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			[ `title matches: Search Results for {product_search_term} – ${ STORE_NAME }` ]:
				( r ) => {
					const title_actual = r.html().find( 'head title' ).text();
					const title_expected = new RegExp(
						`Search Results for .${ product_search_term }. – ${ STORE_NAME }`
					);
					return title_actual.match( title_expected );
				},
			"body contains: 'Search results' title": ( r ) =>
				r.body.includes( 'Search results:' ),
			'footer contains: Built with WooCommerce': ( r ) =>
				r.html().find( 'body footer' ).text().includes( FOOTER_TEXT ),
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	searchProduct();
}
