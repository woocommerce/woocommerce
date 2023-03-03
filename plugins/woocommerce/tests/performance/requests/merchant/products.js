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
	jsonRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	contentTypeRequestHeader,
	commonPostRequestHeaders,
	commonAPIGetRequestHeaders,
	commonNonStandardHeaders,
} from '../../headers.js';

export function products() {
	let response;
	let api_x_wp_nonce;
	let apiNonceHeader;
	let heartbeat_nonce;

	group( 'Products Page', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(
			`${ base_url }/wp-admin/edit.php?post_type=product`,
			{
				headers: requestHeaders,
				tags: { name: 'Merchant - All Products' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			"body contains: 'Products' header": ( response ) =>
				response.body.includes( 'Products</h1>' ),
		} );

		// Correlate nonce values for use in subsequent requests.
		heartbeat_nonce = findBetween(
			response.body,
			'heartbeatSettings = {"nonce":"',
			'"};'
		);
		api_x_wp_nonce = findBetween(
			response.body,
			'wp-json\\/","nonce":"',
			'",'
		);

		// Create request header with nonce value for use in subsequent requests.
		apiNonceHeader = {
			'x-wp-nonce': `${ api_x_wp_nonce }`,
		};
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );

	group( 'All Products - Other Requests', function () {
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

	group( 'WP Admin Heartbeat', function () {
		const requestHeaders = Object.assign(
			{},
			jsonRequestHeader,
			commonRequestHeaders,
			contentTypeRequestHeader,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.post(
			`${ base_url }/wp-admin/admin-ajax.php`,
			`_nonce=${ heartbeat_nonce }&action=heartbeat&has_focus=true&interval=15&screen_id=edit-product`,
			{
				headers: requestHeaders,
				tags: { name: 'Merchant - action=heartbeat' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	products();
}
