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

export function coupons() {
	let response;
	let api_x_wp_nonce;
	let apiNonceHeader;
	let heartbeat_nonce;

	group( 'Coupons Page', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(
			`${ base_url }/wp-admin/edit.php?post_type=shop_coupon`,
			{
				headers: requestHeaders,
				tags: { name: 'Merchant - Coupons' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			"body contains: 'Coupons' header": ( response ) =>
				response.body.includes( 'Coupons</h1>' ),
		} );

		// Correlate nonce values for use in subsequent requests.
		heartbeat_nonce = findBetween(
			response.body,
			'heartbeatSettings = {"nonce":"',
			'"};'
		);
		api_x_wp_nonce = findBetween(
			response.body,
			'wp.apiFetch.createNonceMiddleware( "',
			'" )'
		);

		// Create request header with nonce value for use in subsequent requests.
		apiNonceHeader = {
			'x-wp-nonce': `${ api_x_wp_nonce }`,
		};
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );

	group( 'Coupons - Other Requests', function () {
		const requestHeaders = Object.assign(
			{},
			jsonAPIRequestHeader,
			commonRequestHeaders,
			commonAPIGetRequestHeaders,
			apiNonceHeader,
			commonNonStandardHeaders
		);

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
			`_nonce=${ heartbeat_nonce }&action=heartbeat&has_focus=true&interval=15&screen_id=shop_coupon`,
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
	coupons();
}
