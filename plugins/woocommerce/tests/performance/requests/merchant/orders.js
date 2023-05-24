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
import {
	base_url,
	cot_status,
	admin_orders_base_url,
	cot_admin_orders_base_url,
	think_time_min,
	think_time_max,
} from '../../config.js';
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

// Change URL if COT is enabled and being used
let admin_orders_base;
let admin_orders_completed;
if ( cot_status === true ) {
	admin_orders_base = cot_admin_orders_base_url;
	admin_orders_completed = 'status=wc-completed';
} else {
	admin_orders_base = admin_orders_base_url;
	admin_orders_completed = 'post_status=wc-completed';
}

export function orders( includeTests = {} ) {
	let response;
	let api_x_wp_nonce;
	let apiNonceHeader;
	let heartbeat_nonce;
	let includedTests = Object.assign( {
			completed: true,
			heartbeat: true,
			other: true,
		},
		includeTests
	);

	group( 'All Orders', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get( `${ base_url }/wp-admin/${ admin_orders_base }`, {
			headers: requestHeaders,
			tags: { name: 'Merchant - All Orders' },
		} );
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			"body contains: 'Orders' header": ( response ) =>
				response.body.includes( 'Orders</h1>' ),
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

	if ( includedTests.other ) {
		group( 'All Orders - Other Requests', function () {
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

	if ( includedTests.heartbeat ) {
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
				`_nonce=${ heartbeat_nonce }&action=heartbeat&has_focus=true&interval=15&screen_id=shop_order`,
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

	if ( includedTests.completed ) {
		group( 'Completed Orders', function () {
			const requestHeaders = Object.assign(
				{},
				htmlRequestHeader,
				commonRequestHeaders,
				commonGetRequestHeaders,
				commonNonStandardHeaders
			);

			response = http.get(
				`${ base_url }/wp-admin/${ admin_orders_base }&${ admin_orders_completed }`,
				{
					headers: requestHeaders,
					tags: { name: 'Merchant - Completed Orders' },
				}
			);
			check( response, {
				'is status 200': ( r ) => r.status === 200,
				"body contains: 'Orders' header": ( response ) =>
					response.body.includes( 'Orders</h1>' ),
			} );
		} );

		sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
	}
}

export default function () {
	orders();
}
