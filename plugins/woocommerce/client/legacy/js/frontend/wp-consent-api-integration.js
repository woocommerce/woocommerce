( function ( $ ) {
	'use strict';

	// check init order source attribution on consent change
	const consentCategory = 'marketing';
	document.addEventListener( 'wp_listen_for_consent_change', ( e ) => {
		const changedConsentCategory = e.detail;
		for ( let key in changedConsentCategory ) {
			if ( changedConsentCategory.hasOwnProperty( key ) ) {
				if ( key === consentCategory && changedConsentCategory[ key ] === 'allow' ) {
					window.woocommerce_order_source_attribution.setAllowTrackingConsent( true );
				}
			}
		}
	} );

	// Init order source attribution as soon as consent type is defined.
	$( document ).on( 'wp_consent_type_defined', () => {
		if ( wp_has_consent( consentCategory ) ) {
			window.woocommerce_order_source_attribution.setAllowTrackingConsent( true );
		}
	} );
}( jQuery ) );

