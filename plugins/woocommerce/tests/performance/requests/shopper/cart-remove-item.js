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
import { base_url, think_time_min, think_time_max } from '../../config.js';
import {
	htmlRequestHeader,
	jsonRequestHeader,
	jsonAPIRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonPostRequestHeaders,
	commonAPIGetRequestHeaders,
	commonNonStandardHeaders,
} from '../../headers.js';
import { getDefaultProduct } from '../../utils.js';

export function cartRemoveItem() {
	let cartItemKey;
	let storeApiNonce;

	group( 'Product Page Add to cart', function () {
		const requestheaders = Object.assign(
			{},
			jsonRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		const product = getDefaultProduct( 'Shopper' );

		const response = http.post(
			`${ base_url }/?wc-ajax=add_to_cart`,
			{
				product_id: `${ product.id }`,
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

		// Extract Store API nonce for cart operations.
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

	group( 'Get cart item key via Store API', function () {
		const requestheaders = Object.assign(
			{},
			{ Nonce: storeApiNonce },
			jsonAPIRequestHeader,
			commonAPIGetRequestHeaders,
			commonRequestHeaders,
			commonNonStandardHeaders
		);

		const response = http.get( `${ base_url }/wp-json/wc/store/v1/cart`, {
			headers: requestheaders,
			tags: { name: 'Shopper - Store API Get Cart' },
		} );
		check( response, {
			'is status 200': ( r ) => r.status === 200,
		} );

		const cartData = response.json();
		if ( cartData.items && cartData.items.length > 0 ) {
			cartItemKey = cartData.items[ 0 ].key;
		}
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );

	group( 'Remove item from cart via Store API', function () {
		const requestheaders = Object.assign(
			{},
			{ 'content-type': 'application/json', Nonce: storeApiNonce },
			jsonAPIRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		const response = http.post(
			`${ base_url }/wp-json/wc/store/v1/cart/remove-item`,
			JSON.stringify( { key: cartItemKey } ),
			{
				headers: requestheaders,
				tags: { name: 'Shopper - Store API Remove Item From Cart' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			'cart items reduced': ( r ) => {
				const cart = r.json();
				return (
					cart.items &&
					! cart.items.find( ( item ) => item.key === cartItemKey )
				);
			},
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	cartRemoveItem();
}
