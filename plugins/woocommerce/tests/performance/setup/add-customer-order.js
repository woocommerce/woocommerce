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
import {
	base_url,
	admin_username,
	admin_password,
	customer_username,
	customer_email,
	customer_password,
	customer_first_name,
	customer_last_name,
} from '../config.js';

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

	// If customer doesn't exist, create the customer
	if ( ! customerId ) {
		console.log(
			`Customer with username ${ customer_username } not found. Creating new customer...`
		);
		const createCustomerData = {
			username: customer_username,
			password: customer_password,
			email: customer_email,
			first_name: customer_first_name,
			last_name: customer_last_name,
		};

		response = http.post(
			`${ base_url }/wp-json/wc/v3/customers`,
			JSON.stringify( createCustomerData ),
			{
				headers: requestHeaders,
			}
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
