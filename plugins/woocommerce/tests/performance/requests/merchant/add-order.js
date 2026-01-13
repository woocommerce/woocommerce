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

const admin_new_order_url = 'post-new.php?post_type=shop_order';
const admin_update_order_url = 'post.php';
const admin_new_order_assert = 'Add new order';
const admin_open_order_assert = 'Edit order</h1>';
const admin_order_updated_assert = 'Order updated.';

const global_order_date = new Date().toJSON().slice( 0, 10 );

export function addOrder( includeTests = {} ) {
	let ajax_nonce_add_meta;
	let wpnonce;
	let closed_postboxes_nonce;
	let sample_permalink_nonce;
	let woocommerce_meta_nonce;
	let meta_box_order_nonce;
	let post_id;
	let api_x_wp_nonce;
	let apiNonceHeader;
	let heartbeat_nonce;
	const includedTests = Object.assign(
		{
			create: true,
			heartbeat: true,
			open: true,
			other: true,
			update: true,
		},
		includeTests
	);

	group( 'Add Order', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		const response = http.get(
			`${ base_url }/wp-admin/${ admin_new_order_url }`,
			{
				headers: requestHeaders,
				tags: { name: 'Merchant - New Order Page' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			"body contains: 'Add new order' header": ( r ) =>
				r.body.includes( `${ admin_new_order_assert }` ),
		} );

		// Correlate nonce values for use in subsequent requests.
		ajax_nonce_add_meta = response
			.html()
			.find( 'input[id=_ajax_nonce-add-meta]' )
			.first()
			.attr( 'value' );
		wpnonce = response
			.html()
			.find( 'input[id=_wpnonce]' )
			.first()
			.attr( 'value' );
		closed_postboxes_nonce = response
			.html()
			.find( 'input[id=closedpostboxesnonce]' )
			.first()
			.attr( 'value' );
		sample_permalink_nonce = response
			.html()
			.find( 'input[id=samplepermalinknonce]' )
			.first()
			.attr( 'value' );
		woocommerce_meta_nonce = response
			.html()
			.find( 'input[id=woocommerce_meta_nonce]' )
			.first()
			.attr( 'value' );
		meta_box_order_nonce = response
			.html()
			.find( 'input[id=meta-box-order-nonce]' )
			.first()
			.attr( 'value' );
		post_id = response
			.html()
			.find( 'input[id=post_ID]' )
			.first()
			.attr( 'value' );
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

			const tasksResponse = http.get(
				`${ base_url }/wp-json/wc-admin/onboarding/tasks?_locale=user`,
				{
					headers: requestHeaders,
					tags: { name: 'Merchant - wc-admin/onboarding/tasks?' },
				}
			);
			check( tasksResponse, {
				'is status 200': ( r ) => r.status === 200,
			} );

			const notesResponse = http.get(
				`${ base_url }/wp-json/wc-analytics/admin/notes?page=1&per_page=25&` +
					`type=error%2Cupdate&status=unactioned&_locale=user`,
				{
					headers: requestHeaders,
					tags: { name: 'Merchant - wc-analytics/admin/notes?' },
				}
			);
			check( notesResponse, {
				'is status 200': ( r ) => r.status === 200,
			} );

			const optionsResponse = http.get(
				`${ base_url }/wp-json/wc-admin/options?options=woocommerce_ces_tracks_queue&_locale=user`,
				{
					headers: requestHeaders,
					tags: {
						name: 'Merchant - wc-admin/options?options=woocommerce_ces_tracks_queue',
					},
				}
			);
			check( optionsResponse, {
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

			const response = http.post(
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

		sleep(
			randomIntBetween( `${ think_time_min }`, `${ think_time_max }` )
		);
	}

	if ( includedTests.create ) {
		group( 'Create New Order', function () {
			const requestHeaders = Object.assign(
				{},
				htmlRequestHeader,
				commonRequestHeaders,
				commonGetRequestHeaders,
				contentTypeRequestHeader,
				commonNonStandardHeaders
			);

			const order_date = new Date().toJSON().slice( 0, 10 );

			const orderParams = new URLSearchParams( [
				[ '_ajax_nonce-add-meta', `${ ajax_nonce_add_meta }` ],
				[
					'_billing_address_1',
					`${ addresses_guest_billing_address_1 }`,
				],
				[
					'_billing_address_2',
					`${ addresses_guest_billing_address_2 }`,
				],
				[ '_billing_city', `${ addresses_guest_billing_city }` ],
				[ '_billing_company', `${ addresses_guest_billing_company }` ],
				[ '_billing_country', `${ addresses_guest_billing_country }` ],
				[ '_billing_email', `${ addresses_guest_billing_email }` ],
				[
					'_billing_first_name',
					`${ addresses_guest_billing_first_name }`,
				],
				[
					'_billing_last_name',
					`${ addresses_guest_billing_last_name }`,
				],
				[ '_billing_phone', `${ addresses_guest_billing_phone }` ],
				[
					'_billing_postcode',
					`${ addresses_guest_billing_postcode }`,
				],
				[ '_billing_state', `${ addresses_guest_billing_state }` ],
				[
					'_shipping_address_1',
					`${ addresses_guest_billing_address_1 }`,
				],
				[
					'_shipping_address_2',
					`${ addresses_guest_billing_address_2 }`,
				],
				[ '_shipping_city', `${ addresses_guest_billing_city }` ],
				[ '_shipping_company', `${ addresses_guest_billing_company }` ],
				[ '_shipping_country', `${ addresses_guest_billing_country }` ],
				[
					'_shipping_first_name',
					`${ addresses_guest_billing_first_name }`,
				],
				[
					'_shipping_last_name',
					`${ addresses_guest_billing_last_name }`,
				],
				[ '_shipping_phone', `${ addresses_guest_billing_phone }` ],
				[
					'_shipping_postcode',
					`${ addresses_guest_billing_postcode }`,
				],
				[ '_shipping_state', `${ addresses_guest_billing_state }` ],
				[ '_payment_method', `${ payment_method }` ],
				[ '_transaction_id', '' ],
				[ '_wp_http_referer', '' ],
				[ '_wp_original_http_referer', '' ],
				[ '_wpnonce', `${ wpnonce }` ],
				[ 'action', 'editpost' ],
				[ 'auto_draft', '1' ],
				[ 'closedpostboxesnonce', `${ closed_postboxes_nonce }` ],
				[ 'customer_user', '' ],
				[ 'excerpt', '' ],
				[ 'meta-box-order-nonce', `${ meta_box_order_nonce }` ],
				[ 'metakeyinput', '' ],
				[ 'metakeyselect', '%23NONE%23' ],
				[ 'metavalue', '' ],
				[ 'order_date', `${ order_date }` ],
				[ 'order_date_hour', '01' ],
				[ 'order_date_minute', '01' ],
				[ 'order_date_second', '01' ],
				[ 'order_note', '' ],
				[ 'order_note_type', '' ],
				[ 'order_status', 'wc-pending' ],
				[ 'original_post_status', 'auto-draft' ],
				[ 'original_post_title', '' ],
				[ 'originalaction', 'editpost' ],
				[ 'post_ID', `${ post_id }` ],
				[ 'post_author', '1' ],
				[ 'post_status', 'auto-draft' ],
				[ 'post_title', '%2COrder' ],
				[ 'post_type', 'shop_order' ],
				[ 'referredby', '' ],
				[ 'samplepermalinknonce', `${ sample_permalink_nonce }` ],
				[ 'save', 'Create' ],
				[ 'user_ID', '1' ],
				[ 'wc_order_action', '' ],
				[ 'woocommerce_meta_nonce', `${ woocommerce_meta_nonce }` ],
			] );

			const response = http.post(
				`${ base_url }/wp-admin/${ admin_update_order_url }`,
				orderParams.toString(),
				{
					headers: requestHeaders,
					tags: { name: 'Merchant - Create New Order' },
				}
			);
			check( response, {
				'is status 200': ( r ) => r.status === 200,
				"body contains: 'Edit order' header": ( r ) =>
					r.body.includes( `${ admin_open_order_assert }` ),
				"body contains: 'Order updated' confirmation": ( r ) =>
					r.body.includes( `${ admin_order_updated_assert }` ),
			} );
		} );

		sleep(
			randomIntBetween( `${ think_time_min }`, `${ think_time_max }` )
		);
	}

	if ( includedTests.open ) {
		group( 'Open Order', function () {
			const requestHeaders = Object.assign(
				{},
				htmlRequestHeader,
				commonRequestHeaders,
				commonGetRequestHeaders,
				commonNonStandardHeaders
			);

			const response = http.get(
				`${ base_url }/wp-admin/${ admin_update_order_url }?post=${ post_id }&action=edit`,
				{
					headers: requestHeaders,
					tags: { name: 'Merchant - Open Order' },
				}
			);
			check( response, {
				'is status 200': ( r ) => r.status === 200,
				"body contains: 'Edit order' header": ( r ) =>
					r.body.includes( `${ admin_open_order_assert }` ),
			} );
		} );

		sleep(
			randomIntBetween( `${ think_time_min }`, `${ think_time_max }` )
		);
	}

	if ( includedTests.update ) {
		group( 'Update Order', function () {
			const requestHeaders = Object.assign(
				{},
				htmlRequestHeader,
				commonRequestHeaders,
				commonGetRequestHeaders,
				contentTypeRequestHeader,
				commonNonStandardHeaders
			);

			const orderParams = new URLSearchParams( [
				[ '_ajax_nonce-add-meta', `${ ajax_nonce_add_meta }` ],
				[
					'_billing_address_1',
					`${ addresses_guest_billing_address_1 }`,
				],
				[
					'_billing_address_2',
					`${ addresses_guest_billing_address_2 }`,
				],
				[ '_billing_city', `${ addresses_guest_billing_city }` ],
				[ '_billing_company', `${ addresses_guest_billing_company }` ],
				[ '_billing_country', `${ addresses_guest_billing_country }` ],
				[ '_billing_email', `${ addresses_guest_billing_email }` ],
				[
					'_billing_first_name',
					`${ addresses_guest_billing_first_name }`,
				],
				[
					'_billing_last_name',
					`${ addresses_guest_billing_last_name }`,
				],
				[ '_billing_phone', `${ addresses_guest_billing_phone }` ],
				[
					'_billing_postcode',
					`${ addresses_guest_billing_postcode }`,
				],
				[ '_billing_state', `${ addresses_guest_billing_state }` ],
				[
					'_shipping_address_1',
					`${ addresses_guest_billing_address_1 }`,
				],
				[
					'_shipping_address_2',
					`${ addresses_guest_billing_address_2 }`,
				],
				[ '_shipping_city', `${ addresses_guest_billing_city }` ],
				[ '_shipping_company', `${ addresses_guest_billing_company }` ],
				[ '_shipping_country', `${ addresses_guest_billing_country }` ],
				[
					'_shipping_first_name',
					`${ addresses_guest_billing_first_name }`,
				],
				[
					'_shipping_last_name',
					`${ addresses_guest_billing_last_name }`,
				],
				[ '_shipping_phone', `${ addresses_guest_billing_phone }` ],
				[
					'_shipping_postcode',
					`${ addresses_guest_billing_postcode }`,
				],
				[ '_shipping_state', `${ addresses_guest_billing_state }` ],
				[ '_payment_method', `${ payment_method }` ],
				[ '_transaction_id', '' ],
				[ '_wp_http_referer', '' ],
				[ '_wp_original_http_referer', '' ],
				[ '_wpnonce', `${ wpnonce }` ],
				[ 'action', 'editpost' ],
				[ 'closedpostboxesnonce', `${ closed_postboxes_nonce }` ],
				[ 'customer_user', '' ],
				[ 'excerpt', '' ],
				[ 'meta-box-order-nonce', `${ meta_box_order_nonce }` ],
				[ 'metakeyinput', '' ],
				[ 'metakeyselect', '%23NONE%23' ],
				[ 'metavalue', '' ],
				[ 'order_date', `${ global_order_date }` ],
				[ 'order_date_hour', '01' ],
				[ 'order_date_minute', '01' ],
				[ 'order_date_second', '01' ],
				[ 'order_note', '' ],
				[ 'order_note_type', '' ],
				[ 'order_status', 'wc-completed' ],
				[ 'original_post_status', 'wc-pending' ],
				[ 'original_post_title', '' ],
				[ 'originalaction', 'editpost' ],
				[ 'post_ID', `${ post_id }` ],
				[ 'post_author', '1' ],
				[ 'post_status', 'pending' ],
				[ 'post_title', '%2COrder' ],
				[ 'post_type', 'shop_order' ],
				[ 'referredby', '' ],
				[ 'samplepermalinknonce', `${ sample_permalink_nonce }` ],
				[ 'save', 'Update' ],
				[ 'user_ID', '1' ],
				[ 'wc_order_action', '' ],
				[ 'woocommerce_meta_nonce', `${ woocommerce_meta_nonce }` ],
			] );

			const response = http.post(
				`${ base_url }/wp-admin/${ admin_update_order_url }?post=${ post_id }&action=edit`,
				orderParams.toString(),
				{
					headers: requestHeaders,
					tags: { name: 'Merchant - Update Existing Order Status' },
				}
			);
			check( response, {
				'is status 200': ( r ) => r.status === 200,
				"body contains: 'Order updated' confirmation": ( r ) =>
					r.body.includes( `${ admin_order_updated_assert }` ),
			} );
		} );
	}

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	addOrder();
}
