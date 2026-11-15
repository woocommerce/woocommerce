/**
 * @jest-environment-options {"url": "http://shop.example.com/"}
 */

/**
 * External dependencies
 */
import { afterEach, beforeEach, describe, expect, it } from '@jest/globals';

/**
 * Internal dependencies
 */
import { Analytics } from '../analytics';
import SessionManager from '../session-manager';

const changeConsent = ( value: 'allow' | 'deny' ) =>
	document.dispatchEvent(
		new CustomEvent( 'wp_listen_for_consent_change', { detail: { statistics: value } } )
	);

describe( 'Analytics consent changes on an HTTP store', () => {
	let hasConsent: boolean;

	beforeEach( () => {
		hasConsent = true;
		window.wp_has_consent = () => hasConsent;
	} );

	afterEach( () => {
		delete window.wp_has_consent;
		document.cookie = 'tk_ai=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
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
		document.cookie = 'tk_ai=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';

		hasConsent = true;
		changeConsent( 'allow' );

		expect( document.cookie ).toContain( 'tk_ai=' );
	} );
} );
