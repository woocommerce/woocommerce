( function () {
	'use strict';

	// Set order attribution on consent change.
	document.addEventListener( 'wp_listen_for_consent_change', ( e ) => {
		const changedConsentCategory = e.detail;
		for ( const key in changedConsentCategory ) {
			if ( changedConsentCategory.hasOwnProperty( key ) && key === window.wc_order_attribution.params.consentCategory ) {
				window.wc_order_attribution.setOrderTracking( changedConsentCategory[ key ] === 'allow' );
		}
		}
	} );

	// Set order attribution as soon as consent type is defined.
	document.addEventListener( 'wp_consent_type_defined', () => {
		window.wc_order_attribution.setOrderTracking( wp_has_consent( window.wc_order_attribution.params.consentCategory ) );
	} );
}() );

