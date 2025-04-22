/* eslint-disable jsdoc/require-property-description */
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable import/no-unresolved */
/**
 * k6 dependencies
 */
import encoding from 'k6/encoding';
import http from 'k6/http';
import { check } from 'k6';

/**
 * Internal dependencies
 */
import { base_url, admin_username, admin_password } from '../config.js';

/**
 * Convert Cart & Checkout pages to shortcode.
 */
export function setCartCheckoutShortcodes() {
	/**
	 * A WordPress page.
	 *
	 * @typedef {Object} WPPage
	 * @property {number} id
	 * @property {string} slug
	 */

	let defaultHeaders;

	/**
	 * @type {WPPage[]}
	 */
	let wp_pages;

	/**
	 * @type {WPPage}
	 */
	let cart;

	/**
	 * @type {WPPage}
	 */
	let checkout;

	function setDefaultHeaders() {
		const credentials = `${ admin_username }:${ admin_password }`;
		const encodedCredentials = encoding.b64encode( credentials );
		defaultHeaders = {
			Authorization: `Basic ${ encodedCredentials }`,
			'Content-Type': 'application/json',
		};
	}

	function listWPPages() {
		const url = `${ base_url }/wp-json/wp/v2/pages`;
		const body = {
			_fields: [ 'id', 'slug' ],
			context: 'edit',
		};
		const params = {
			headers: defaultHeaders,
		};
		const response = http.get( url, body, params );
		check( response, {
			'WP pages list obtained': ( r ) => r.status === 200,
		} );
		wp_pages = response.json();
	}

	function findCartCheckoutPage() {
		cart = wp_pages.find( ( page ) => page.slug === 'cart' );
		checkout = wp_pages.find( ( page ) => page.slug === 'checkout' );
	}

	function convertToCartCheckoutShortcode() {
		const url_cart = `${ base_url }/wp-json/wp/v2/pages/${ cart.id }`;
		const url_checkout = `${ base_url }/wp-json/wp/v2/pages/${ checkout.id }`;

		const body_cart = {
			content: {
				raw: '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->',
			},
		};
		const body_checkout = {
			content: {
				raw: '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
			},
		};

		const body_cart_str = JSON.stringify( body_cart );
		const body_checkout_str = JSON.stringify( body_checkout );

		const params = {
			headers: defaultHeaders,
		};

		const response_cart = http.post( url_cart, body_cart_str, params );
		const response_checkout = http.post(
			url_checkout,
			body_checkout_str,
			params
		);

		check( response_cart, {
			'cart shortcode set': ( r ) => r.status >= 200 && r.status < 300,
		} );
		check( response_checkout, {
			'checkout shortcode set': ( r ) =>
				r.status >= 200 && r.status < 300,
		} );
	}

	setDefaultHeaders();

	listWPPages();

	findCartCheckoutPage();

	convertToCartCheckoutShortcode();
}
