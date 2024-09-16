/**
 * External dependencies
 */
import encoding from 'k6/encoding';
import http from 'k6/http';
import { check } from 'k6';
import { findBetween } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

/**
 * Internal dependencies
 */
import {
	base_url,
	admin_username,
	admin_password,
	addresses_customer_billing_first_name,
	addresses_customer_billing_last_name,
	addresses_customer_billing_country,
	addresses_customer_billing_address_1,
	addresses_customer_billing_address_2,
	addresses_customer_billing_city,
	addresses_customer_billing_state,
	addresses_customer_billing_postcode,
	addresses_customer_billing_phone,
	addresses_customer_billing_email,
} from '../config.js';

export function createCustomerTestOrder() {
	const credentials = `${ admin_username }:${ admin_password }`;
	const encodedCredentials = encoding.b64encode( credentials );
	const requestHeaders = {
		Authorization: `Basic ${ encodedCredentials }`,
		'Content-Type': 'application/json',
	};

	const createData = {
		billing: {
			first_name: `${ addresses_customer_billing_first_name }`,
			last_name: `${ addresses_customer_billing_last_name }`,
			address_1: `${ addresses_customer_billing_address_1 }`,
			address_2: `${ addresses_customer_billing_address_2 }`,
			city: `${ addresses_customer_billing_city }`,
			state: `${ addresses_customer_billing_state }`,
			postcode: `${ addresses_customer_billing_postcode }`,
			country: `${ addresses_customer_billing_country }`,
			email: `${ addresses_customer_billing_email }`,
			phone: `${ addresses_customer_billing_phone }`,
		},
		shipping: {
			first_name: `${ addresses_customer_billing_first_name }`,
			last_name: `${ addresses_customer_billing_last_name }`,
			address_1: `${ addresses_customer_billing_address_1 }`,
			address_2: `${ addresses_customer_billing_address_2 }`,
			city: `${ addresses_customer_billing_city }`,
			state: `${ addresses_customer_billing_state }`,
			postcode: `${ addresses_customer_billing_postcode }`,
			country: `${ addresses_customer_billing_country }`,
		},
		customer_id: 2,
	};
	const response = http.post(
		`${ base_url }/wp-json/wc/v3/orders`,
		JSON.stringify( createData ),
		{
			headers: requestHeaders,
			tags: { name: 'API - Create Order' },
		}
	);

	check( response, {
		'status is 201': ( r ) => r.status === 201,
		"body contains: 'Pending' Status": ( r ) =>
			r.body.includes( '"status":"pending"' ),
	} );

	const orderId = findBetween( response.body, '{"id":', ',' );

	console.log( `Test order created: ${ orderId }` );
}
