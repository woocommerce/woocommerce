/* eslint-disable jsdoc/require-property-description */
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable import/no-unresolved */
/**
 * k6 dependencies
 */
import encoding from 'k6/encoding';
import http from 'k6/http';
import { findBetween } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

/**
 * Internal dependencies
 */
import { base_url, admin_username, admin_password } from '../config.js';

/**
 * Convert Cart & Checkout pages to shortcode.
 */
export function addCustomerOrder() {
	function addOrderToCustomer() {
		let response;

		const credentials = `${ admin_username }:${ admin_password }`;
		const encodedCredentials = encoding.b64encode( credentials );
		const requestHeaders = {
			Authorization: `Basic ${ encodedCredentials }`,
			'Content-Type': 'application/json',
		};

		response = http.get( `${ base_url }/wp-json/wc/v3/customers`, {
			headers: requestHeaders,
			tags: { name: 'API - Retrieve Customer' },
		} );

		const customerId = findBetween( response.body, '"id":', ',' );

		const createCustomerOrderData = {
			customer_id: customerId,
			status: 'completed',
		};
		response = http.post(
			`${ base_url }/wp-json/wc/v3/orders`,
			JSON.stringify( createCustomerOrderData ),
			{
				headers: requestHeaders,
				tags: { name: 'API - Create Customer Order' },
			}
		);
	}

	addOrderToCustomer();
}
