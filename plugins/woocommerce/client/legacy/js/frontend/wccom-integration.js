window.addEventListener( 'load' , function ( e ) {
	if ( window.wccom && window.wccom.canTrackUser('analytics') ) {
		window.wc_order_source_attribution.setAllowTrackingConsent( true );
	}

} );
