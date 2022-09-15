/* eslint-disable no-shadow */
/* eslint-disable import/no-unresolved */
/**
 * External dependencies
 */
import encoding from 'k6/encoding';
import http from 'k6/http';
import { check } from 'k6';
import { findBetween } from 'https://jslib.k6.io/k6-utils/1.1.0/index.js';

/**
 * Internal dependencies
 */
import { base_url, admin_username, admin_password } from '../../config.js';

export function ordersAPI() {
	const credentials = `${ admin_username }:${ admin_password }`;
	const encodedCredentials = encoding.b64encode( credentials );
	const options = {
		headers: {
			Authorization: `Basic ${ encodedCredentials }`,
		},
	};
	let response;

	let data = JSON.stringify( {
		billing: {
			first_name: 'John',
			last_name: 'Doe',
			address_1: '969 Market',
			address_2: '',
			city: 'San Francisco',
			state: 'CA',
			postcode: '94103',
			country: 'US',
			email: 'john.doe@example.com',
			phone: '(555) 555-5555',
		},
		shipping: {
			first_name: 'John',
			last_name: 'Doe',
			address_1: '969 Market',
			address_2: '',
			city: 'San Francisco',
			state: 'CA',
			postcode: '94103',
			country: 'US',
		},
	} );

	console.log( data );

	response = http.post( `${ base_url }/wp-json/wc/v3/orders`, data, options );
	check( response, {
		'status is 201': ( r ) => r.status === 201,
	} );

	const post_id = findBetween( response.body, '{"id":', ',' );

	response = http.get(
		`${ base_url }/wp-json/wc/v3/orders/${ post_id }`,
		options
	);
	check( response, {
		'status is 200': ( r ) => r.status === 200,
	} );

	response = http.get( `${ base_url }/wp-json/wc/v3/orders`, options );
	check( response, {
		'status is 200': ( r ) => r.status === 200,
	} );

	data = JSON.stringify( {
		status: 'completed',
	} );

	response = http.put(
		`${ base_url }/wp-json/wc/v3/orders/${ post_id }`,
		data,
		options
	);
	check( response, {
		'status is 201': ( r ) => r.status === 201,
	} );
	/*
	response = http.delete(
		`${ base_url }/wp-json/wc/v3/orders/${ post_id }`,
		{ force: true },
		options
	);
	check( response, {
		'status is 201': ( r ) => r.status === 201,
	} );*/
}

export default function () {
	ordersAPI();
}

/*{"id":417164,"parent_id":0,"status":"pending","currency":"USD","version":"6.9.1",
"prices_include_tax":false,"date_created":"2022-09-15T08:09:36","date_modified":"2022-09-15T08:09:36",
"discount_total":"0.00","discount_tax":"0.00","shipping_total":"0.00","shipping_tax":"0.00",
"cart_tax":"0.00","total":"0.00","total_tax":"0.00","customer_id":0,
"order_key":"wc_order_TgDExzPpPIWmh",
"billing":{"first_name":"","last_name":"","company":"","address_1":"","address_2":"","city":"","state":"","postcode":"","country":"","email":"","phone":""},
"shipping":{"first_name":"","last_name":"","company":"","address_1":"","address_2":"","city":"","state":"","postcode":"","country":"","phone":""},
"payment_method":"","payment_method_title":"","transaction_id":"","customer_ip_address":"","customer_user_agent":"",
"created_via":"rest-api","customer_note":"","date_completed":null,"date_paid":null,"cart_hash":"","number":"417164",
"meta_data":[],"line_items":[],"tax_lines":[],"shipping_lines":[],"fee_lines":[],"coupon_lines":[],"refunds":[],
"payment_url":"https:\/\/wcperftesting.wpcomstaging.com\/checkout\/order-pay\/417164\/?pay_for_order=true&key=wc_order_TgDExzPpPIWmh","is_editable":true,
"needs_payment":false,"needs_processing":false,"date_created_gmt":"2022-09-15T02:39:36","date_modified_gmt":"2022-09-15T02:39:36",
"date_completed_gmt":null,"date_paid_gmt":null,"currency_symbol":"$",
"_links":{"self":[{"href":"https:\/\/wcperftesting.wpcomstaging.com\/wp-json\/wc\/v3\/orders\/417164"}],"collection":[{"href":"https:\/\/wcperftesting.wpcomstaging.com\/wp-json\/wc\/v3\/orders"}]}}
*/
