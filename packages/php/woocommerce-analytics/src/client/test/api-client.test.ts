/**
 * External dependencies
 */
import { afterEach, beforeEach, describe, expect, it, jest } from '@jest/globals';

/**
 * Internal dependencies
 */
import { ApiClient } from '../api-client';

const changeConsent = ( value: 'allow' | 'deny' ) =>
	document.dispatchEvent(
		new CustomEvent( 'wp_listen_for_consent_change', { detail: { statistics: value } } )
	);

describe( 'ApiClient', () => {
	const sendBeacon = jest.fn( () => true );

	beforeEach( () => {
		jest.useFakeTimers();
		Object.defineProperty( navigator, 'sendBeacon', { value: sendBeacon, configurable: true } );
		window.wcAnalytics = {
			trackEndpoint: 'https://shop.example/wp-json/woocommerce-analytics/v1/track',
			eventQueue: [],
			commonProps: {},
			features: { proxy: true },
			pages: {},
			assets_url: '',
		};
	} );

	afterEach( () => {
		jest.useRealTimers();
		sendBeacon.mockClear();
		delete window.wcAnalytics;
		delete window.wp_has_consent;
	} );

	it( 'sends queued events after the debounce while statistics consent is given', () => {
		window.wp_has_consent = () => true;
		const client = new ApiClient();
		client.init();

		client.addEvent( 'page_view' );
		jest.advanceTimersByTime( 1000 );

		expect( sendBeacon ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'drops events queued before statistics consent was withdrawn', () => {
		let hasConsent = true;
		window.wp_has_consent = () => hasConsent;
		const client = new ApiClient();
		client.init();

		client.addEvent( 'page_view' );
		hasConsent = false;
		jest.advanceTimersByTime( 1000 );
		window.dispatchEvent( new Event( 'pagehide' ) );

		hasConsent = true;
		window.dispatchEvent( new Event( 'pagehide' ) );

		expect( sendBeacon ).not.toHaveBeenCalled();
	} );

	it( 'drops events queued before a withdrawal when consent is granted again before they are sent', () => {
		let hasConsent = true;
		window.wp_has_consent = () => hasConsent;
		const client = new ApiClient();
		client.init();

		client.addEvent( 'page_view' );
		hasConsent = false;
		changeConsent( 'deny' );
		hasConsent = true;
		changeConsent( 'allow' );
		jest.advanceTimersByTime( 1000 );

		expect( sendBeacon ).not.toHaveBeenCalled();
	} );

	it( 'drops a batch that fails after consent was withdrawn, even when consent is granted again', async () => {
		let hasConsent = true;
		window.wp_has_consent = () => hasConsent;
		sendBeacon.mockReturnValueOnce( false ).mockReturnValueOnce( false );
		let failFirstRequest: ( error: Error ) => void = () => {};
		const fetchMock = jest
			.fn< typeof fetch >()
			.mockReturnValueOnce(
				new Promise( ( _resolve, reject ) => {
					failFirstRequest = reject;
				} )
			)
			.mockResolvedValue( { ok: true, json: async () => ( {} ) } as Response );
		global.fetch = fetchMock;
		const client = new ApiClient();
		client.init();

		client.addEvent( 'before_withdrawal' );
		client.flush();
		hasConsent = false;
		changeConsent( 'deny' );
		failFirstRequest( new Error( 'Network error' ) );
		await jest.runAllTimersAsync();
		hasConsent = true;
		changeConsent( 'allow' );
		client.addEvent( 'after_regrant' );
		client.flush();

		expect( fetchMock ).toHaveBeenCalledTimes( 2 );
		expect( fetchMock.mock.calls[ 1 ][ 1 ]?.body ).not.toContain( 'before_withdrawal' );
	} );
} );
