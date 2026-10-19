/**
 * External dependencies
 */
import { afterEach, beforeEach, describe, expect, it, jest } from '@jest/globals';

const TRACKING_SCRIPT_URL = 'https://stats.wp.com/s-202642.js';

jest.mock( '../public-path', () => ( {} ) );

const mockInitAnalytics = jest.fn();
jest.mock( '../init', () => {
	mockInitAnalytics();
	return {};
} );

const addedListeners: Array< [ string, EventListenerOrEventListenerObject ] > = [];
const startedIntervals: number[] = [];

const getTrackingScripts = () =>
	Array.from( document.querySelectorAll< HTMLScriptElement >( 'script' ) ).filter(
		script => script.getAttribute( 'src' ) === TRACKING_SCRIPT_URL
	);

const isTrackingScriptLoaded = () =>
	getTrackingScripts().some( script => script.getAttribute( 'type' ) !== 'text/plain' );

/**
 * Print the s.js tag the way PHP does, then run the entry point as a fresh page load and let its dynamic import settle.
 *
 * @param printedTag - `held` with a consent plugin, `regular` without one.
 */
async function loadPage( printedTag: 'held' | 'regular' = 'held' ) {
	const tag = document.createElement( 'script' );
	tag.setAttribute( 'src', TRACKING_SCRIPT_URL );
	if ( printedTag === 'held' ) {
		tag.setAttribute( 'type', 'text/plain' );
		tag.setAttribute( 'data-wc-analytics-consent', 'statistics' );
	}
	document.head.appendChild( tag );

	window.wcAnalytics = {
		trackEndpoint: '',
		eventQueue: [],
		commonProps: {},
		features: {},
		pages: {},
		assets_url: '',
		consentApiActive: printedTag === 'held',
	};
	// A fresh registry also makes the dynamic import of `../init` run its mock factory again.
	jest.resetModules();
	require( '../index' );
	await flushPromises();
}

const flushPromises = () => new Promise( resolve => setTimeout( resolve, 0 ) );

/**
 * Load the WP Consent API script that a delay-JS plugin held back, and give the client time to notice it.
 *
 * @param hasConsent - What `wp_has_consent()` answers.
 */
async function loadDelayedConsentApi( hasConsent: boolean ) {
	window.wp_has_consent = () => hasConsent;
	await new Promise( resolve => setTimeout( resolve, 300 ) );
}

function changeConsent( value: 'allow' | 'deny' ) {
	document.dispatchEvent(
		new CustomEvent( 'wp_listen_for_consent_change', {
			detail: { statistics: value },
		} )
	);
}

describe( 'client entry point', () => {
	beforeEach( () => {
		( window as unknown as { jQuery: ( callback: () => void ) => void } ).jQuery = callback =>
			callback();

		// Track listeners and intervals so one test's page cannot react to events dispatched in the next.
		const addEventListener = document.addEventListener.bind( document );
		jest.spyOn( document, 'addEventListener' ).mockImplementation(
			( type: string, listener, options ) => {
				addedListeners.push( [ type, listener ] );
				addEventListener( type, listener, options );
			}
		);

		const setInterval = window.setInterval.bind( window );
		jest.spyOn( window, 'setInterval' ).mockImplementation( ( handler, timeout ) => {
			const id = setInterval( handler, timeout );
			startedIntervals.push( id );
			return id;
		} );
	} );

	afterEach( () => {
		addedListeners.forEach( ( [ type, listener ] ) =>
			document.removeEventListener( type, listener )
		);
		addedListeners.length = 0;
		startedIntervals.forEach( id => window.clearInterval( id ) );
		startedIntervals.length = 0;
		jest.restoreAllMocks();
		mockInitAnalytics.mockClear();
		document.head.replaceChildren();
		delete window.wcAnalytics;
		delete window.wp_has_consent;
		delete window.waitfor_consent_hook;
		delete window.wp_consent_type;
	} );

	it( 'loads s.js and analytics when statistics consent is given', async () => {
		window.wp_has_consent = () => true;

		await loadPage();

		expect( isTrackingScriptLoaded() ).toBe( true );
		expect( mockInitAnalytics ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'loads neither s.js nor analytics without statistics consent', async () => {
		window.wp_has_consent = () => false;

		await loadPage();

		expect( isTrackingScriptLoaded() ).toBe( false );
		expect( mockInitAnalytics ).not.toHaveBeenCalled();
	} );

	it( 'loads s.js and analytics once consent is granted on the page', async () => {
		window.wp_has_consent = () => false;
		await loadPage();

		changeConsent( 'allow' );
		await flushPromises();

		expect( isTrackingScriptLoaded() ).toBe( true );
		expect( mockInitAnalytics ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'loads neither s.js nor analytics while a delayed WP Consent API script has not loaded', async () => {
		await loadPage();

		expect( isTrackingScriptLoaded() ).toBe( false );
		expect( mockInitAnalytics ).not.toHaveBeenCalled();
	} );

	it( 'loads s.js and analytics once a delayed WP Consent API script reports consent', async () => {
		await loadPage();

		await loadDelayedConsentApi( true );

		expect( isTrackingScriptLoaded() ).toBe( true );
		expect( mockInitAnalytics ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'loads neither s.js nor analytics once a delayed WP Consent API script reports no consent', async () => {
		await loadPage();

		await loadDelayedConsentApi( false );

		expect( isTrackingScriptLoaded() ).toBe( false );
		expect( mockInitAnalytics ).not.toHaveBeenCalled();
	} );

	it( 'loads s.js and analytics once consent is granted after a delayed WP Consent API script loads', async () => {
		await loadPage();
		await loadDelayedConsentApi( false );

		changeConsent( 'allow' );
		await flushPromises();

		expect( isTrackingScriptLoaded() ).toBe( true );
		expect( mockInitAnalytics ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'leaves a regular s.js tag alone when there is no consent plugin', async () => {
		await loadPage( 'regular' );

		expect( getTrackingScripts() ).toHaveLength( 1 );
		expect( mockInitAnalytics ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'waits for the consent type before checking consent', async () => {
		// WP Consent API reports consent until a consent type is set.
		window.wp_has_consent = () => window.wp_consent_type === undefined;
		window.waitfor_consent_hook = '1';

		await loadPage();
		expect( isTrackingScriptLoaded() ).toBe( false );
		expect( mockInitAnalytics ).not.toHaveBeenCalled();

		window.wp_consent_type = 'optin';
		document.dispatchEvent( new CustomEvent( 'wp_consent_type_defined' ) );
		await flushPromises();

		expect( isTrackingScriptLoaded() ).toBe( false );
		expect( mockInitAnalytics ).not.toHaveBeenCalled();
	} );

	it( 'loads s.js and analytics when consent is granted before the consent type is defined', async () => {
		window.wp_has_consent = () => false;
		window.waitfor_consent_hook = '1';
		await loadPage();

		changeConsent( 'allow' );
		await flushPromises();

		expect( isTrackingScriptLoaded() ).toBe( true );
		expect( mockInitAnalytics ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'loads s.js and analytics once a late consent type allows statistics', async () => {
		window.wp_has_consent = () => true;
		window.waitfor_consent_hook = '1';

		await loadPage();
		expect( isTrackingScriptLoaded() ).toBe( false );
		expect( mockInitAnalytics ).not.toHaveBeenCalled();

		window.wp_consent_type = 'optout';
		document.dispatchEvent( new CustomEvent( 'wp_consent_type_defined' ) );
		await flushPromises();

		expect( isTrackingScriptLoaded() ).toBe( true );
		expect( mockInitAnalytics ).toHaveBeenCalledTimes( 1 );
	} );
} );
