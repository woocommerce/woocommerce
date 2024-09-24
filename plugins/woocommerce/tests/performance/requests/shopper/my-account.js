/* eslint-disable import/no-unresolved */
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
	customer_email,
	customer_password,
	think_time_min,
	think_time_max,
	FOOTER_TEXT,
	STORE_NAME,
} from '../../config.js';
import {
	htmlRequestHeader,
	allRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonPostRequestHeaders,
	commonNonStandardHeaders,
} from '../../headers.js';
import { checkResponse } from '../../utils.js';

export function myAccount() {
	let response;
	let woocommerce_login_nonce;

	group( 'My Account', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get( `${ base_url }/my-account`, {
			headers: requestHeaders,
			tags: { name: 'Shopper - My Account Login Page' },
		} );

		checkResponse( response, 200, {
			title: `My account – ${ STORE_NAME }`,
			body: '>My account</h1>',
			footer: FOOTER_TEXT,
		} );

		// Correlate nonce value for use in subsequent requests.
		woocommerce_login_nonce = response
			.html()
			.find( 'input[name=woocommerce-login-nonce]' )
			.first()
			.attr( 'value' );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );

	group( 'My Account Login', function () {
		const requestHeaders = Object.assign(
			{},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.post(
			`${ base_url }/my-account`,
			{
				username: `${ customer_email }`,
				password: `${ customer_password }`,
				'woocommerce-login-nonce': `${ woocommerce_login_nonce }`,
				_wp_http_referer: '/my-account',
				login: 'Log%20in',
			},
			{
				headers: requestHeaders,
				tags: { name: 'Shopper - Login to My Account' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
			'body contains: my account welcome message': ( r ) =>
				r.body.includes( 'From your account dashboard you can view' ),
		} );

		const requestHeadersPost = Object.assign(
			{},
			allRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.post(
			`${ base_url }/?wc-ajax=get_refreshed_fragments`,
			{
				headers: requestHeadersPost,
				tags: { name: 'Shopper - wc-ajax=get_refreshed_fragments' },
			}
		);
		check( response, {
			'is status 200': ( r ) => r.status === 200,
		} );
	} );

	sleep( randomIntBetween( `${ think_time_min }`, `${ think_time_max }` ) );
}

export default function () {
	myAccount();
}
