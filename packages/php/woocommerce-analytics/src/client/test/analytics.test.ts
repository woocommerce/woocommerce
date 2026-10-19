/**
 * @jest-environment-options {"url": "https://shop.example.com/"}
 */

/**
 * External dependencies
 */
import { afterEach, beforeEach, describe, expect, it } from '@jest/globals';

/**
 * Internal dependencies
 */
import { Analytics } from '../analytics';
import { COOKIE_NAME } from '../constants';
import SessionManager from '../session-manager';

const expire = ( name: string ) => {
	document.cookie = `${ name }=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT`;
};

const changeConsent = ( value: 'allow' | 'deny' ) =>
	document.dispatchEvent(
		new CustomEvent( 'wp_listen_for_consent_change', { detail: { statistics: value } } )
	);

describe( 'Analytics consent changes', () => {
	let hasConsent: boolean;

	beforeEach( () => {
		hasConsent = true;
		window.wp_has_consent = () => hasConsent;
	} );

	afterEach( () => {
		delete window.wp_has_consent;
		expire( 'tk_ai' );
		expire( COOKIE_NAME );
	} );

	it( 'sets tk_ai again when statistics consent is granted again on the same page', () => {
		new Analytics( new SessionManager(), {
			eventQueue: [],
			commonProps: {},
			features: {},
			pages: {},
		} ).init();
		hasConsent = false;
		changeConsent( 'deny' );
		// The entry point expires the Tracks cookies on withdrawal.
		expire( 'tk_ai' );

		hasConsent = true;
		changeConsent( 'allow' );

		expect( document.cookie ).toContain( 'tk_ai=' );
		expect( document.cookie ).not.toContain( `${ COOKIE_NAME }=` );
	} );
} );
