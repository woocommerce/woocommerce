/* global wcNavV2Config, jQuery, wcTracks */
( function ( $ ) {
	'use strict';

	// Full implementation lives in Task 13. This stub makes the enqueue work
	// end-to-end so Assets can be tested in isolation.
	$( function () {
		if ( wcNavV2Config && wcNavV2Config.isWooPage === '1' ) {
			$( 'body' ).addClass( 'wc-nav-v2-active' );
		}
	} );
} )( jQuery );
