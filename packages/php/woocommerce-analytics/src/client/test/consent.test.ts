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
			consentManaged: true,
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

	it( 'reports consent when no consent plugin decides', () => {
		window.wcAnalytics!.consentManaged = false;

		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( true );
	} );

	it( 'waits for the script to load', () => {
		const callback = jest.fn();

		new ConsentManager().waitForConsentApi( callback );
		jest.runOnlyPendingTimers();
		expect( callback ).not.toHaveBeenCalled();

		window.wp_has_consent = () => false;
		jest.runOnlyPendingTimers();

		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'runs right away once the script has loaded', () => {
		window.wp_has_consent = () => false;
		const callback = jest.fn();

		new ConsentManager().waitForConsentApi( callback );

		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'runs right away when no consent plugin decides', () => {
		window.wcAnalytics!.consentManaged = false;
		const callback = jest.fn();

		new ConsentManager().waitForConsentApi( callback );

		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );
} );

describe( 'ConsentManager.hasAnalyticsConsent with a stored statistics choice', () => {
	let storedChoice: string;

	beforeEach( () => {
		storedChoice = '';
		// Like the WP Consent API without a consent type: every category is granted.
		window.wp_has_consent = () => true;
		window.consent_api = { cookie_prefix: 'wp_consent' };
		window.consent_api_get_cookie = name => ( name === 'wp_consent_statistics' ? storedChoice : '' );
	} );

	afterEach( () => {
		delete window.wp_has_consent;
		delete window.consent_api;
		delete window.consent_api_get_cookie;
	} );

	it( 'reports no consent when the shopper denied statistics, even though wp_has_consent() grants it', () => {
		storedChoice = 'deny';

		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( false );
	} );

	it( 'reads the stored choice under the consent cookie prefix', () => {
		window.consent_api = { cookie_prefix: 'custom' };
		window.consent_api_get_cookie = name => ( name === 'custom_statistics' ? 'deny' : '' );

		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( false );
	} );

	it( 'reports consent when the shopper allowed statistics and wp_has_consent() grants it', () => {
		storedChoice = 'allow';

		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( true );
	} );

	it( 'reports no consent when the shopper allowed statistics but wp_has_consent() refuses it', () => {
		storedChoice = 'allow';
		window.wp_has_consent = () => false;

		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( false );
	} );

	it( 'reports consent when no choice is stored and wp_has_consent() grants it', () => {
		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( true );
	} );

	it( 'leaves the decision to wp_has_consent() when the cookie reader is not available', () => {
		delete window.consent_api_get_cookie;
		storedChoice = 'deny';

		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( true );
	} );
} );
