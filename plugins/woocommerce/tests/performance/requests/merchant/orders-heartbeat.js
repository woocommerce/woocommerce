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
	hpos_status,
	admin_orders_base_url,
	hpos_admin_orders_base_url,
	think_time_min,
	think_time_max,
} from '../../config.js';
import {
	htmlRequestHeader,
	jsonRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	contentTypeRequestHeader,
	commonPostRequestHeaders,
	commonNonStandardHeaders,
} from '../../headers.js';

// Nonce and cookie jar to be used in subsequent iterations
let heartbeat_nonce;
let jar;

// Change URL if HPOS is enabled and being used
let admin_orders_base;
if ( hpos_status === true ) {
	admin_orders_base = hpos_admin_orders_base_url;
} else {
	admin_orders_base = admin_orders_base_url;
}

export function ordersHeartbeat() {
	let response;

	// Request orders list only on first iteration to correlate heartbeat nonce
	// eslint-disable-next-line no-undef, eqeqeq
	if ( __ITER == 0 ) {
		group( 'Orders Page', function () {
			const requestHeaders = Object.assign(
				{},
				htmlRequestHeader,
				commonRequestHeaders,
				commonGetRequestHeaders,
				commonNonStandardHeaders
			);

			response = http.get(
				`${ base_url }/wp-admin/${ admin_orders_base }`,
				{
					headers: requestHeaders,
					tags: { name: 'Merchant - All Orders' },
				}
			);
			check( response, {
				'is status 200': ( r ) => r.status === 200,
				// eslint-disable-next-line no-shadow
				"body contains: 'Orders' header": ( response ) =>
					response.body.includes( 'Orders</h1>' ),
			} );

			// Correlate nonce values for use in subsequent requests.
			heartbeat_nonce = findBetween(
				response.body,
				'heartbeatSettings = {"nonce":"',
				'"};'
			);

			// Cookie jar for subsequent iterations so cookies won't be reset
			jar = http.cookieJar();
		} );

		sleep(
			randomIntBetween( `${ think_time_min }`, `${ think_time_max }` )
		);
	}

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
				jar,
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	ordersHeartbeat();
}
