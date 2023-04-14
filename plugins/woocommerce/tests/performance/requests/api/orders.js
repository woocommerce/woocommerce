/* eslint-disable no-shadow */
/* eslint-disable import/no-unresolved */
/**
 * External dependencies
 */
import encoding from 'k6/encoding';
import http from 'k6/http';
import { check, group } from 'k6';
import { findBetween } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

/**
 * Internal dependencies
 */
import {
	base_url,
	admin_username,
	admin_password,
	addresses_guest_billing_first_name,
	addresses_guest_billing_last_name,
	addresses_guest_billing_country,
	addresses_guest_billing_address_1,
	addresses_guest_billing_address_2,
	addresses_guest_billing_city,
	addresses_guest_billing_state,
	addresses_guest_billing_postcode,
	addresses_guest_billing_phone,
	addresses_guest_billing_email,
} from '../../config.js';

export function ordersAPI() {
	const credentials = `${ admin_username }:${ admin_password }`;
	const encodedCredentials = encoding.b64encode( credentials );
	const requestHeaders = {
		Authorization: `Basic ${ encodedCredentials }`,
		'Content-Type': 'application/json',
	};

	const createData = {
		billing: {
			first_name: `${ addresses_guest_billing_first_name }`,
			last_name: `${ addresses_guest_billing_last_name }`,
			address_1: `${ addresses_guest_billing_address_1 }`,
			address_2: `${ addresses_guest_billing_address_2 }`,
			city: `${ addresses_guest_billing_city }`,
			state: `${ addresses_guest_billing_state }`,
			postcode: `${ addresses_guest_billing_postcode }`,
			country: `${ addresses_guest_billing_country }`,
			email: `${ addresses_guest_billing_email }`,
			phone: `${ addresses_guest_billing_phone }`,
		},
		shipping: {
			first_name: `${ addresses_guest_billing_first_name }`,
			last_name: `${ addresses_guest_billing_last_name }`,
			address_1: `${ addresses_guest_billing_address_1 }`,
			address_2: `${ addresses_guest_billing_address_2 }`,
			city: `${ addresses_guest_billing_city }`,
			state: `${ addresses_guest_billing_state }`,
			postcode: `${ addresses_guest_billing_postcode }`,
			country: `${ addresses_guest_billing_country }`,
		},
	};

	let batchData;
	const createBatchData = [];
	const updateBatchData = [];

	const batchSize = 10;

	const updateData = {
		status: 'completed',
	};
	let post_id;
	let post_ids;
	let response;

	group( 'API Create Order', function () {
		response = http.post(
			`${ base_url }/wp-json/wc/v3/orders`,
			JSON.stringify( createData ),
			{
				headers: requestHeaders,
				tags: { name: 'API - Create Order' },
			}
		);
		check( response, {
			'status is 201': ( r ) => r.status === 201,
			"body contains: 'Pending' Status": ( response ) =>
				response.body.includes( '"status":"pending"' ),
		} );

		post_id = findBetween( response.body, '{"id":', ',' );
	} );

	group( 'API Retrieve Order', function () {
		if ( post_id ) {
			response = http.get(
				`${ base_url }/wp-json/wc/v3/orders/${ post_id }`,
				{
					headers: requestHeaders,
					tags: { name: 'API - Retrieve Order' },
				}
			);
			check( response, {
				'status is 200': ( r ) => r.status === 200,
				'body contains: Order ID': ( response ) =>
					response.body.includes( `"id":${ post_id }` ),
			} );
		}
	} );

	group( 'API List Orders', function () {
		if ( post_id ) {
			response = http.get( `${ base_url }/wp-json/wc/v3/orders`, {
				headers: requestHeaders,
				tags: { name: 'API - List Orders' },
			} );
			check( response, {
				'status is 200': ( r ) => r.status === 200,
				'body contains: Order ID': ( response ) =>
					response.body.includes( '[{"id":' ),
			} );
		}
	} );

	group( 'API Update Order', function () {
		if ( post_id ) {
			response = http.put(
				`${ base_url }/wp-json/wc/v3/orders/${ post_id }`,
				JSON.stringify( updateData ),
				{
					headers: requestHeaders,
					tags: { name: 'API - Update Order (Status)' },
				}
			);
			check( response, {
				'status is 200': ( r ) => r.status === 200,
				"body contains: 'Completed' Status": ( response ) =>
					response.body.includes( '"status":"completed"' ),
			} );
		}
	} );

	group( 'API Delete Order', function () {
		if ( post_id ) {
			response = http.del(
				`${ base_url }/wp-json/wc/v3/orders/${ post_id }`,
				JSON.stringify( { force: true } ),
				{
					headers: requestHeaders,
					tags: { name: 'API - Delete Order' },
				}
			);
			check( response, {
				'status is 200': ( r ) => r.status === 200,
				'body contains: Order ID': ( response ) =>
					response.body.includes( `"id":${ post_id }` ),
			} );
		}
	} );

	group( 'API Batch Create Orders', function () {
		for ( let index = 0; index < batchSize; index++ ) {
			createBatchData.push( createData );
		}
		batchData = {
			create: createBatchData,
		};

		response = http.post(
			`${ base_url }/wp-json/wc/v3/orders/batch`,
			JSON.stringify( batchData ),
			{
				headers: requestHeaders,
				tags: { name: 'API - Batch Create Orders' },
			}
		);
		check( response, {
			'status is 200': ( r ) => r.status === 200,
			'body contains: Create batch prefix': ( response ) =>
				response.body.includes( 'create":[{"id"' ),
		} );

		post_ids = findBetween( response.body, '{"id":', ',"parent_id', true );
	} );

	group( 'API Batch Update Orders', function () {
		let updateBatchItem;

		for ( let index = 0; index < batchSize; index++ ) {
			updateBatchItem = {
				id: `${ post_ids[ index ] }`,
				status: 'completed',
			};
			updateBatchData.push( updateBatchItem );
		}
		batchData = {
			update: updateBatchData,
		};

		response = http.post(
			`${ base_url }/wp-json/wc/v3/orders/batch`,
			JSON.stringify( batchData ),
			{
				headers: requestHeaders,
				tags: {
					name: 'API - Batch Update (Status) Orders',
				},
			}
		);
		check( response, {
			'status is 200': ( r ) => r.status === 200,
			'body contains: Update batch prefix': ( response ) =>
				response.body.includes( 'update":[{"id"' ),
		} );
	} );
}

export default function () {
	ordersAPI();
}
