/**
 * @jest-environment-options {"url": "https://shop.example.co.uk/"}
 */

/**
 * External dependencies
 */
import { afterEach, describe, expect, it, jest } from '@jest/globals';

/**
 * Internal dependencies
 */
import { clearTrackingCookies } from '../tracking-cookies';

const TRACKING_COOKIE_NAMES = [ 'tk_or', 'tk_lr', 'tk_r3d', 'tk_ai', 'tk_aip', 'tk_qs' ];

const expire = ( name: string, domain = '' ) => {
	document.cookie = `${ name }=; path=/; ${ domain }expires=Thu, 01 Jan 1970 00:00:00 GMT`;
};

describe( 'clearTrackingCookies', () => {
	afterEach( () => {
		for ( const cookie of document.cookie.split( '; ' ).filter( Boolean ) ) {
			const name = cookie.split( '=' )[ 0 ];
			expire( name );
			expire( name, 'domain=.example.co.uk; ' );
		}
	} );

	it( 'expires every tracking cookie on the parent domain s.js uses', () => {
		for ( const name of TRACKING_COOKIE_NAMES ) {
			document.cookie = `${ name }=1; domain=.example.co.uk; path=/`;
		}

		clearTrackingCookies();

		expect( document.cookie ).toBe( '' );
	} );

	it( 'expires both copies of a cookie set host-only and on the parent domain', () => {
		document.cookie = 'tk_ai=host; path=/';
		document.cookie = 'tk_ai=parent; domain=.example.co.uk; path=/';

		clearTrackingCookies();

		expect( document.cookie ).toBe( '' );
	} );

	it( 'writes a host-only expiry, which browsers keep apart from a same-host domain cookie', () => {
		document.cookie = 'tk_ai=host; path=/';
		const setCookie = jest.spyOn( Document.prototype, 'cookie', 'set' );

		clearTrackingCookies();

		expect( setCookie ).toHaveBeenCalledWith( 'tk_ai=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT' );
		setCookie.mockRestore();
	} );

	it( 'leaves other cookies alone', () => {
		document.cookie = 'tk_or=1; domain=.example.co.uk; path=/';
		document.cookie = 'wp_consent_statistics=deny; path=/';
		document.cookie = 'woocommerce_cart_hash=abc; domain=.example.co.uk; path=/';

		clearTrackingCookies();

		expect( document.cookie.split( '; ' ).sort() ).toEqual( [
			'woocommerce_cart_hash=abc',
			'wp_consent_statistics=deny',
		] );
	} );
} );
