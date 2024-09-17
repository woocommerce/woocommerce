/* eslint-disable jsdoc/require-property-description */
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable import/no-unresolved */
/**
 * k6 dependencies
 */
import encoding from 'k6/encoding';
import http from 'k6/http';

/**
 * Internal dependencies
 */
import { base_url, admin_username, admin_password } from '../config.js';

/**
 * Add a customer order specifically for my-account-orders.js test
 */
export function addCustomerOrder() {
	let response;
	const customerUsername = 'customer';

	const credentials = `${ admin_username }:${ admin_password }`;
	const encodedCredentials = encoding.b64encode( credentials );
	const requestHeaders = {
		Authorization: `Basic ${ encodedCredentials }`,
		'Content-Type': 'application/json',
	};

	// Fetch the list of customers
	response = http.get( `${ base_url }/wp-json/wc/v3/customers`, {
		headers: requestHeaders,
	} );

	// Parse the response body as JSON to find the customer by username
	const customers = JSON.parse( response.body );
	let customerId = null;

	for ( let i = 0; i < customers.length; i++ ) {
		if ( customers[ i ].username === customerUsername ) {
			customerId = customers[ i ].id;
			break;
		}
	}

	// If the customer isn't found, throw an error
	if ( ! customerId ) {
		throw new Error(
			`Customer with username ${ customerUsername } not found`
		);
	}

	// Create a new order for the identified customer
	const createCustomerOrderData = {
		customer_id: customerId,
		status: 'completed',
	};
	response = http.post(
		`${ base_url }/wp-json/wc/v3/orders`,
		JSON.stringify( createCustomerOrderData ),
		{
			headers: requestHeaders,
		}
	);
}
