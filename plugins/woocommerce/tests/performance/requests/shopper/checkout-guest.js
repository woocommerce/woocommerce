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
	addresses_guest_billing_first_name,
	addresses_guest_billing_last_name,
	addresses_guest_billing_company,
	addresses_guest_billing_country,
	addresses_guest_billing_address_1,
	addresses_guest_billing_address_2,
	addresses_guest_billing_city,
	addresses_guest_billing_state,
	addresses_guest_billing_postcode,
	addresses_guest_billing_phone,
	addresses_guest_billing_email,
	payment_method,
	think_time_min,
	think_time_max,
	FOOTER_TEXT,
	STORE_NAME,
} from '../../config.js';
import {
	htmlRequestHeader,
	jsonAPIRequestHeader,
	allRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonPostRequestHeaders,
	commonNonStandardHeaders,
} from '../../headers.js';
import { checkResponse } from '../../utils.js';

export function checkoutGuest() {
	let storeApiNonce;

	group( 'Proceed to checkout', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		const response = http.get( `${ base_url }/checkout`, {
			headers: requestHeaders,
			tags: { name: 'Shopper - View Checkout' },
		} );
		checkResponse( response, 200, {
			title: `Checkout – ${ STORE_NAME }`,
			body: 'wp-block-woocommerce-checkout',
			footer: FOOTER_TEXT,
		} );

		// Extract Store API nonce for checkout request.
		storeApiNonce = findBetween( response.body, "storeApiNonce: '", "'" );
		if ( ! storeApiNonce ) {
			storeApiNonce = findBetween(
				response.body,
				'storeApiNonce":"',
				'"'
			);
		}
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );

	group( 'Update customer billing address via Store API', function () {
		const requestHeaders = Object.assign(
			{},
			{ 'content-type': 'application/json', Nonce: storeApiNonce },
			jsonAPIRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		const response = http.post(
			`${ base_url }/wp-json/wc/store/v1/cart/update-customer`,
			JSON.stringify( {
				billing_address: {
					first_name: addresses_guest_billing_first_name,
					last_name: addresses_guest_billing_last_name,
					company: addresses_guest_billing_company,
					address_1: addresses_guest_billing_address_1,
					address_2: addresses_guest_billing_address_2,
					city: addresses_guest_billing_city,
					state: addresses_guest_billing_state,
					postcode: addresses_guest_billing_postcode,
					country: addresses_guest_billing_country,
					email: addresses_guest_billing_email,
					phone: addresses_guest_billing_phone,
				},
				shipping_address: {
					first_name: addresses_guest_billing_first_name,
					last_name: addresses_guest_billing_last_name,
					company: addresses_guest_billing_company,
					address_1: addresses_guest_billing_address_1,
					address_2: addresses_guest_billing_address_2,
					city: addresses_guest_billing_city,
					state: addresses_guest_billing_state,
					postcode: addresses_guest_billing_postcode,
					country: addresses_guest_billing_country,
				},
			} ),
			{
				headers: requestHeaders,
				tags: { name: 'Shopper - Store API update-customer' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );

	group( 'Place Order via Store API', function () {
		const requestHeaders = Object.assign(
			{},
			{ 'content-type': 'application/json', Nonce: storeApiNonce },
			jsonAPIRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		const response = http.post(
			`${ base_url }/wp-json/wc/store/v1/checkout`,
			JSON.stringify( {
				billing_address: {
					first_name: addresses_guest_billing_first_name,
					last_name: addresses_guest_billing_last_name,
					company: addresses_guest_billing_company,
					address_1: addresses_guest_billing_address_1,
					address_2: addresses_guest_billing_address_2,
					city: addresses_guest_billing_city,
					state: addresses_guest_billing_state,
					postcode: addresses_guest_billing_postcode,
					country: addresses_guest_billing_country,
					email: addresses_guest_billing_email,
					phone: addresses_guest_billing_phone,
				},
				shipping_address: {
					first_name: addresses_guest_billing_first_name,
					last_name: addresses_guest_billing_last_name,
					company: addresses_guest_billing_company,
					address_1: addresses_guest_billing_address_1,
					address_2: addresses_guest_billing_address_2,
					city: addresses_guest_billing_city,
					state: addresses_guest_billing_state,
					postcode: addresses_guest_billing_postcode,
					country: addresses_guest_billing_country,
				},
				payment_method,
			} ),
			{
				headers: requestHeaders,
				tags: { name: 'Shopper - Store API checkout' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			'body contains: order_id': ( r ) => {
				const data = r.json();
				return data && data.order_id > 0;
			},
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );

	group( 'Order received', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		const response = http.get( `${ base_url }/checkout/order-received/`, {
			headers: requestHeaders,
			tags: { name: 'Shopper - Order Received' },
		} );
		checkResponse( response, 200, {
			title: `Order received – ${ STORE_NAME }`,
			body: 'Thank you. Your order has been received.',
			footer: FOOTER_TEXT,
		} );

		const requestHeadersPost = Object.assign(
			{},
			allRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		const refreshResponse = http.post(
			`${ base_url }/?wc-ajax=get_refreshed_fragments`,
			{
				headers: requestHeadersPost,
				tags: { name: 'Shopper - wc-ajax=get_refreshed_fragments' },
			}
		);
		check( refreshResponse, {
			'is status 200': ( r ) => r.status === 200,
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	checkoutGuest();
}
