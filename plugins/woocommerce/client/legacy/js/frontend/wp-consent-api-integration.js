( function () {
	'use strict';

	// Check init order attribution on consent change.
	const CONSENT_CATEGORY_MARKING = 'marketing';
	document.addEventListener( 'wp_listen_for_consent_change', ( e ) => {
		const changedConsentCategory = e.detail;
		for ( const key in changedConsentCategory ) {
			if ( changedConsentCategory.hasOwnProperty( key ) && key === CONSENT_CATEGORY_MARKING ) {
				window.wc_order_attribution.setOrderTracking( changedConsentCategory[ key ] === 'allow' );
		}
		}
	} );

	// Init order attribution as soon as consent type is defined.
	document.addEventListener( 'wp_consent_type_defined', () => {
		window.wc_order_attribution.setOrderTracking( wp_has_consent( CONSENT_CATEGORY_MARKING ) );
	} );
}() );

