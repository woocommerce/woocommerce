/* eslint-disable import/no-unresolved */
/* eslint-disable no-shadow */
/**
 * External dependencies
 */
import { sleep, check, group } from 'k6';
import http from 'k6/http';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.1.0/index.js';

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
	customer_user_id,
} from '../../config.js';
import {
	htmlRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonNonStandardHeaders,
} from '../../headers.js';

const date = new Date();
const month = date.toJSON().slice( 5, 7 );
const year = date.toJSON().slice( 0, 4 );
const currentDate = `${ year }${ month }`;

// Change URL if HPOS is enabled and being used
let admin_orders_base;
let admin_filter_month_assert;
if ( hpos_status === true ) {
	admin_orders_base = hpos_admin_orders_base_url;
	admin_filter_month_assert = `selected='selected' value="${ currentDate }">`;
} else {
	admin_orders_base = `${ admin_orders_base_url }&post_status=all`;
	admin_filter_month_assert = `selected='selected' value='${ currentDate }'>`;
}

export function ordersFilter() {
	let response;

	group( 'Orders Filter', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(
			`${ base_url }/wp-admin/${ admin_orders_base }` +
				`&s&action=-1&m=${ currentDate }&_customer_user&filter_action=Filter&paged=1&action2=-1`,
			{
				headers: requestHeaders,
				tags: { name: 'Merchant - Filter Orders By Month' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			'body contains: filter set to selected month': ( response ) =>
				response.body.includes( `${ admin_filter_month_assert }` ),
		} );

		response = http.get(
			`${ base_url }/wp-admin/${ admin_orders_base }` +
				`&s&action=-1&m=0&_customer_user=${ customer_user_id }&filter_action=Filter&paged=1&action2=-1`,
			{
				headers: requestHeaders,
				tags: { name: 'Merchant - Filter Orders By Customer' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			'body contains: filter set to selected customer': ( response ) =>
				response.body.includes(
					`<option value="${ customer_user_id }" selected="selected">`
				),
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	ordersFilter();
}
