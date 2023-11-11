( function ( $ ) {
	'use strict';

	// Check init order source attribution on consent change.
	const CONSENT_CATEGORY_MARKING = 'marketing';
	document.addEventListener( 'wp_listen_for_consent_change', ( e ) => {
		const changedConsentCategory = e.detail;
		for ( const key in changedConsentCategory ) {
			if ( changedConsentCategory.hasOwnProperty( key ) ) {
				if ( key === CONSENT_CATEGORY_MARKING && changedConsentCategory[ key ] === 'allow' ) {
					window.woocommerce_order_source_attribution.setAllowTrackingConsent( true );
				}
			}
		}
	} );

	// Init order source attribution as soon as consent type is defined.
	$( document ).on( 'wp_consent_type_defined', () => {
		if ( wp_has_consent( CONSENT_CATEGORY_MARKING ) ) {
			window.woocommerce_order_source_attribution.setAllowTrackingConsent( true );
		}
	} );
}( jQuery ) );

