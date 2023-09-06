/*global woocommerce_admin_meta_boxes, wcTracks */
jQuery( document ).ready( ( $ ) => {
	'use strict';

	// Stand-in wcTracks.recordEvent in case tracks is not available (for any reason).
	window.wcTracks = window.wcTracks || {};
	window.wcTracks.recordEvent = window.wcTracks.recordEvent || function() { };

	// Handle our details container toggle.
	$( '.woocommerce-order-source-attribution-details-toggle' )
		.on( 'click', function ( e ) {
			var $this = $( this );
			var $container = $this.siblings( '.woocommerce-order-source-attribution-details-container' );
			var toggle = '';

			e.preventDefault();

			if ( $container.hasClass( 'closed' ) ) {
				$container.removeClass( 'closed' );
				$container.fadeIn( 250 );
				$this.attr( 'aria-expanded', 'true' );
				toggle = 'opened';
			} else {
				$container.addClass( 'closed' );
				$container.fadeOut( 250 );
				$this.attr( 'aria-expanded', 'false' );
				toggle = 'closed';
			}

			window.wcTracks.recordEvent( 'order_source_attribution_details_toggle', {
				order_id: window.woocommerce_admin_meta_boxes.order_id,
				details: toggle
			} );
		} );
} );
