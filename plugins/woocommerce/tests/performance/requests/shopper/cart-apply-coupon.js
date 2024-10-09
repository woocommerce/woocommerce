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
	product_sku,
	product_id,
	coupon_code,
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
	contentTypeRequestHeader,
} from '../../headers.js';

export function cartApplyCoupon() {
	let apply_coupon_nonce;
	// let item_name;
	let woocommerce_cart_nonce;

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
		apply_coupon_nonce = findBetween(
			response.body,
			'apply_coupon_nonce":"',
			'","'
		);
		// item_name = findBetween( response.body, 'name="cart[', '][qty]' );
		woocommerce_cart_nonce = response
			.html()
			.find( 'input[name=woocommerce-cart-nonce]' )
			.first()
			.attr( 'value' );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );

	group( 'Apply Coupon', function () {
		const requestheaders = Object.assign(
			{},
			jsonRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders,
			contentTypeRequestHeader
		);

		const response = http.post(
			`${ base_url }/?wc-ajax=apply_coupon`,
			{
				coupon_code: `${ coupon_code }`,
				security: `${ apply_coupon_nonce }`,
			},
			{
				headers: requestheaders,
				tags: { name: 'Shopper - wc-ajax=apply_coupon' },
			}
		);

		check( response, {
			'is status 200': ( r ) => r.status === 200,
			"body contains: 'Coupon code applied successfully'": ( r ) =>
				r.body.includes( 'Coupon code applied successfully' ),
		} );

		const cartResponse = http.post(
			`${ base_url }/cart`,
			{
				_wp_http_referer: '%2Fcart',
				// "cart["+`${item_name}`+"][qty]": "1",
				coupon_code: '',
				'woocommerce-cart-nonce': `${ woocommerce_cart_nonce }`,
			},
			{
				headers: requestheaders,
				tags: { name: 'Shopper - Update Cart' },
			}
		);
		check( cartResponse, {
			'is status 200': ( r ) => r.status === 200,
			"body contains: 'woocommerce-remove-coupon' class": ( r ) =>
				r.body.includes( 'class="woocommerce-remove-coupon"' ),
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	cartApplyCoupon();
}
