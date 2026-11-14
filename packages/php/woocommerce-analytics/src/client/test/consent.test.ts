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
	const setStoredChoice = ( choice: string, name = 'wp_consent_statistics' ) => {
		document.cookie = `${ name }=${ choice }; path=/`;
	};

	beforeEach( () => {
		window.wcAnalytics = {
			trackEndpoint: '',
			eventQueue: [],
			commonProps: {},
			features: {},
			pages: {},
			assets_url: '',
			consentManaged: false,
			statisticsConsentCookie: 'wp_consent_statistics',
		};
		// Like the WP Consent API without a consent type: every category is granted.
		window.wp_has_consent = () => true;
	} );

	afterEach( () => {
		for ( const name of [ 'wp_consent_statistics', 'custom_statistics' ] ) {
			document.cookie = `${ name }=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT`;
		}
		delete window.wcAnalytics;
		delete window.wp_has_consent;
	} );

	it( 'reports no consent when the shopper denied statistics, even though wp_has_consent() grants it', () => {
		setStoredChoice( 'deny' );

		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( false );
	} );

	it( 'reports no consent when the shopper denied statistics before a delayed WP Consent API script loads', () => {
		delete window.wp_has_consent;
		setStoredChoice( 'deny' );

		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( false );
	} );

	it( 'reads the stored choice under the cookie name the page provides', () => {
		window.wcAnalytics!.statisticsConsentCookie = 'custom_statistics';
		setStoredChoice( 'deny', 'custom_statistics' );

		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( false );
	} );

	it( 'reports consent when the shopper allowed statistics and wp_has_consent() grants it', () => {
		setStoredChoice( 'allow' );

		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( true );
	} );

	it( 'reports no consent when the shopper allowed statistics but wp_has_consent() refuses it', () => {
		setStoredChoice( 'allow' );
		window.wp_has_consent = () => false;

		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( false );
	} );

	it( 'reports consent when no choice is stored and wp_has_consent() grants it', () => {
		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( true );
	} );

	it( 'ignores a deny cookie when the page provides no cookie name, as without the WP Consent API', () => {
		delete window.wp_has_consent;
		window.wcAnalytics!.statisticsConsentCookie = '';
		setStoredChoice( 'deny' );

		expect( new ConsentManager().hasAnalyticsConsent() ).toBe( true );
	} );

	it( 'hears consent given once a delayed WP Consent API script loads', () => {
		delete window.wp_has_consent;
		const listener = jest.fn();

		new ConsentManager().addConsentChangeListener( listener );
		document.dispatchEvent(
			new CustomEvent( 'wp_listen_for_consent_change', { detail: { statistics: 'allow' } } )
		);

		expect( listener ).toHaveBeenCalledWith( true );
	} );
} );
