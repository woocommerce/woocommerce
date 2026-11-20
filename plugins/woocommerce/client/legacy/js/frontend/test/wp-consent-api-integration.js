/**
 * @jest-environment jest-fixed-jsdom
 */

describe( 'WP Consent API integration', () => {
	const loadIntegration = ( { consentType, waitForConsentHook, hasConsent } ) => {
		window.wp_consent_type = consentType;
		window.waitfor_consent_hook = waitForConsentHook;
		window.wp_has_consent = jest.fn( () => hasConsent );
		window.wc_order_attribution = {
			params: { consentCategory: 'marketing' },
			setOrderTracking: jest.fn(),
		};
		jest.isolateModules( () => require( '../wp-consent-api-integration' ) );
	};

	afterEach( () => {
		delete window.wp_consent_type;
		delete window.waitfor_consent_hook;
		delete window.wp_has_consent;
		delete window.wc_order_attribution;
	} );

	test( 'waits for the consent type when the consent plugin defines it later', () => {
		loadIntegration( { consentType: undefined, waitForConsentHook: '1', hasConsent: true } );

		expect( window.wc_order_attribution.setOrderTracking ).not.toHaveBeenCalled();

		document.dispatchEvent( new CustomEvent( 'wp_consent_type_defined' ) );

		expect( window.wp_has_consent ).toHaveBeenCalledWith( 'marketing' );
		expect( window.wc_order_attribution.setOrderTracking ).toHaveBeenCalledWith( true );
	} );

	test( 'checks consent on load when the consent type is already defined', () => {
		loadIntegration( { consentType: 'optin', waitForConsentHook: '1', hasConsent: true } );

		expect( window.wp_has_consent ).toHaveBeenCalledWith( 'marketing' );
		expect( window.wc_order_attribution.setOrderTracking ).toHaveBeenCalledWith( true );
	} );

	test( 'checks consent on load when no consent plugin defines the consent type', () => {
		loadIntegration( { consentType: undefined, waitForConsentHook: '', hasConsent: false } );

		expect( window.wp_has_consent ).toHaveBeenCalledWith( 'marketing' );
		expect( window.wc_order_attribution.setOrderTracking ).toHaveBeenCalledWith( false );
	} );

	test( 'removes tracking when consent is revoked', () => {
		loadIntegration( { consentType: 'optin', waitForConsentHook: '1', hasConsent: true } );

		document.dispatchEvent(
			new CustomEvent( 'wp_listen_for_consent_change', { detail: { marketing: 'deny' } } )
		);

		expect( window.wc_order_attribution.setOrderTracking ).toHaveBeenLastCalledWith( false );
	} );
} );
