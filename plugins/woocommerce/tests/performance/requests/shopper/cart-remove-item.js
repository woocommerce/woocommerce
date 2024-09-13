// eslint-disable import/no-unresolved
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
	product_sku,
	product_id,
	think_time_min,
	think_time_max,
} from '../../config.js';
import {
	htmlRequestHeader,
	jsonRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonPostRequestHeaders,
	commonNonStandardHeaders,
} from '../../headers.js';

export function cartRemoveItem() {
	let item_to_remove;
	let wpnonce;

	group( 'Product Page Add to cart', function () {
		const requestheaders = Object.assign(
			{},
			jsonRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		const response = http.post(
			`${ base_url }/?wc-ajax=add_to_cart`,
			{
				product_sku: `${ product_sku }`,
				product_id: `${ product_id }`,
				quantity: '1',
			},
			{
				headers: requestheaders,
				tags: { name: 'Shopper - wc-ajax=add_to_cart' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );

	group( 'View Cart', function () {
		const requestheaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		const response = http.get( `${ base_url }/cart`, {
			headers: requestheaders,
			tags: { name: 'Shopper - View Cart' },
		} );
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			"body does not contain: 'your cart is currently empty'": ( r ) =>
				! r.body.includes( 'Your cart is currently empty.' ),
		} );

		// Correlate cart item value for use in subsequent requests.
		item_to_remove = findBetween( response.body, '?remove_item=', '&' );
		wpnonce = findBetween( response.body, '_wpnonce=', '" class="remove"' );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );

	group( 'Remove item from cart', function () {
		const requestheaders = Object.assign(
			{},
			jsonRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		const response = http.get(
			`${ base_url }/cart?remove_item=${ item_to_remove }&_wpnonce=${ wpnonce }`,
			{
				headers: requestheaders,
				tags: { name: 'Shopper - Remove Item From Cart' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			"body contains: 'removed'": ( r ) => r.body.includes( ' removed.' ),
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	cartRemoveItem();
}
