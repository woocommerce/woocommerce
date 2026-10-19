/**
 * External dependencies
 */
import { afterEach, beforeEach, describe, expect, it, jest } from '@jest/globals';

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
		window.waitfor_consent_hook = '1';
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

describe( 'ConsentManager with a delayed WP Consent API script', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		window.wcAnalytics = {
			trackEndpoint: '',
			eventQueue: [],
			commonProps: {},
			features: {},
			pages: {},
			assets_url: '',
			consentApiActive: true,
		};
	} );

	afterEach( () => {
		jest.useRealTimers();
		delete window.wcAnalytics;
		delete window.wp_has_consent;
	} );

	it( 'reports no consent until the script loads', () => {
		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( false );
	} );

	it( 'reports consent without the WP Consent API', () => {
		window.wcAnalytics!.consentApiActive = false;

		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( true );
	} );

	it( 'waits for the script to load', () => {
		const callback = jest.fn();

		new ConsentManager().waitForConsentApi( callback );
		jest.advanceTimersByTime( 1000 );
		expect( callback ).not.toHaveBeenCalled();

		window.wp_has_consent = () => false;
		jest.advanceTimersByTime( 1000 );

		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'runs right away once the script has loaded', () => {
		window.wp_has_consent = () => false;
		const callback = jest.fn();

		new ConsentManager().waitForConsentApi( callback );

		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'runs right away without the WP Consent API', () => {
		window.wcAnalytics!.consentApiActive = false;
		const callback = jest.fn();

		new ConsentManager().waitForConsentApi( callback );

		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );
} );
