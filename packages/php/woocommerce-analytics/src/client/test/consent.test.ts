/**
 * External dependencies
 */
import { afterEach, describe, expect, it, jest } from '@jest/globals';

/**
 * Internal dependencies
 */
import { ConsentManager } from '../consent';

describe( 'ConsentManager.waitForConsentType', () => {
	afterEach( () => {
		delete window.wp_has_consent;
		delete window.waitfor_consent_hook;
		delete window.wp_consent_type;
	} );

	it( 'runs right away without the WP Consent API', () => {
		const callback = jest.fn();

		new ConsentManager().waitForConsentType( callback );

		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'runs right away when the consent plugin does not ask to wait', () => {
		window.wp_has_consent = () => false;
		const callback = jest.fn();

		new ConsentManager().waitForConsentType( callback );

		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'runs right away when the consent type is already defined', () => {
		window.wp_has_consent = () => false;
		window.waitfor_consent_hook = '1';
		window.wp_consent_type = 'optin';
		const callback = jest.fn();

		new ConsentManager().waitForConsentType( callback );

		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'waits for the consent plugin to define the consent type', () => {
		window.wp_has_consent = () => false;
		window.waitfor_consent_hook = '1';
		const callback = jest.fn();

		new ConsentManager().waitForConsentType( callback );
		expect( callback ).not.toHaveBeenCalled();

		window.wp_consent_type = 'optin';
		document.dispatchEvent( new CustomEvent( 'wp_consent_type_defined' ) );
		document.dispatchEvent( new CustomEvent( 'wp_consent_type_defined' ) );

		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );
} );
