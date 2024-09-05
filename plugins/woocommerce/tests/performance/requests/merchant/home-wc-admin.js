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
	jsonAPIRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonAPIGetRequestHeaders,
	commonNonStandardHeaders,
} from '../../headers.js';

export function homeWCAdmin( includeTests = {} ) {
	let response;
	let api_x_wp_nonce;
	let apiNonceHeader;
	const includedTests = Object.assign(
		{
			orders: true,
			other: true,
			products: true,
			reviews: true,
		},
		includeTests
	);

	group( 'WC Home Page', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get( `${ base_url }/wp-admin/admin.php?page=wc-admin`, {
			headers: requestHeaders,
			tags: { name: 'Merchant - WC-Admin' },
		} );
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			"body contains: current page is 'Home'": ( r ) =>
				r.body.includes( 'aria-current="page">Home' ),
		} );

		// Correlate nonce values for use in subsequent requests.
		api_x_wp_nonce = findBetween(
			response.body,
			'wp.apiFetch.createNonceMiddleware( "',
			'"'
		);

		// Create request header with nonce value for use in subsequent requests.
		apiNonceHeader = {
			'x-wp-nonce': `${ api_x_wp_nonce }`,
		};
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );

	if ( includedTests.other ) {
		group( 'WC Admin - Other Requests', function () {
			const requestHeaders = Object.assign(
				{},
				jsonAPIRequestHeader,
				commonRequestHeaders,
				commonAPIGetRequestHeaders,
				apiNonceHeader,
				commonNonStandardHeaders
			);

			response = http.get(
				`${ base_url }/wp-json/wc-admin/onboarding/tasks?_locale=user`,
				{
					headers: requestHeaders,
					tags: { name: 'Merchant - wc-admin/onboarding/tasks?' },
				}
			);
			check( response, {
				'is status 200': ( r ) => r.status === 200,
			} );

			response = http.get(
				`${ base_url }/wp-json/wc-analytics/admin/notes?page=1&per_page=25&` +
					`type=error%2Cupdate&status=unactioned&_locale=user`,
				{
					headers: requestHeaders,
					tags: { name: 'Merchant - wc-analytics/admin/notes?' },
				}
			);
			check( response, {
				'is status 200': ( r ) => r.status === 200,
			} );

			response = http.get(
				`${ base_url }/wp-json/wc-admin/options?options=woocommerce_ces_tracks_queue&_locale=user`,
				{
					headers: requestHeaders,
					tags: {
						name: 'Merchant - wc-admin/options?options=woocommerce_ces_tracks_queue',
					},
				}
			);
			check( response, {
				'is status 200': ( r ) => r.status === 200,
			} );
		} );
	}

	if ( includedTests.orders ) {
		group( 'Orders Activity', function () {
			const requestHeaders = Object.assign(
				{},
				jsonAPIRequestHeader,
				commonRequestHeaders,
				commonAPIGetRequestHeaders,
				apiNonceHeader,
				commonNonStandardHeaders
			);

			response = http.get(
				`${ base_url }/wp-json/wc-analytics/orders?page=1&per_page=1&status%5B0%5D=processing&` +
					`status%5B1%5D=on-hold&_fields%5B0%5D=id&_locale=user`,
				{
					headers: requestHeaders,
					tags: { name: 'Merchant - wc-analytics/orders?' },
				}
			);
			check( response, {
				'is status 200': ( r ) => r.status === 200,
			} );
		} );
	}

	if ( includedTests.reviews ) {
		group( 'Reviews Activity', function () {
			const requestHeaders = Object.assign(
				{},
				jsonAPIRequestHeader,
				commonRequestHeaders,
				commonAPIGetRequestHeaders,
				apiNonceHeader,
				commonNonStandardHeaders
			);

			response = http.get(
				`${ base_url }/wp-json/wc-analytics/products/reviews?page=1&per_page=1&status=hold&` +
					`_embed=1&_fields%5B0%5D=id&_locale=user`,
				{
					headers: requestHeaders,
					tags: { name: 'Merchant - wc-analytics/products/reviews?' },
				}
			);
			check( response, {
				'is status 200': ( r ) => r.status === 200,
			} );
		} );
	}

	if ( includedTests.products ) {
		group( 'Products Activity', function () {
			const requestHeaders = Object.assign(
				{},
				jsonAPIRequestHeader,
				commonRequestHeaders,
				commonAPIGetRequestHeaders,
				apiNonceHeader,
				commonNonStandardHeaders
			);

			response = http.get(
				`${ base_url }/wp-json/wc-analytics/products/low-in-stock?page=1&per_page=1&` +
					`low_in_stock=true&status=publish&_fields%5B0%5D=id&_locale=user`,
				{
					headers: requestHeaders,
					tags: {
						name: 'Merchant - wc-analytics/products/low-in-stock?',
					},
				}
			);
			check( response, {
				'is status 200': ( r ) => r.status === 200,
			} );
		} );
	}

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	homeWCAdmin();
}
