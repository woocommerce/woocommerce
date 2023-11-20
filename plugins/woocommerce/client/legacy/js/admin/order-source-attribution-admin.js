/*global woocommerce_admin_meta_boxes, wcTracks */
jQuery( document ).ready( ( $ ) => {
	'use strict';

	// Stand-in wcTracks.recordEvent in case Tracks is not available (for any reason).
	window.wcTracks = window.wcTracks || {};
	window.wcTracks.recordEvent = window.wcTracks.recordEvent || ( () => {} );

	// Handle the "Details" container toggle.
	$( '.woocommerce-order-source-attribution-details-toggle' )
		.on( 'click', ( e ) => {
			const $this = $( e.target ).closest( '.woocommerce-order-source-attribution-details-toggle');
			const $container = $this.closest( '.order-source-attribution-metabox' )
											.find( '.woocommerce-order-source-attribution-details-container' );
			let toggle = '';

			e.preventDefault();

			$container.fadeToggle( 250 );
			$this.find( '.toggle-text' ).toggle();
			if ( $container.hasClass( 'closed' ) ) {
				$this.attr( 'aria-expanded', 'true' );
				toggle = 'opened';
			} else {
				$this.attr( 'aria-expanded', 'false' );
				toggle = 'closed';
			}
			$container.toggleClass( 'closed' );

			window.wcTracks.recordEvent( 'order_source_attribution_details_toggle', {
				order_id: window.woocommerce_admin_meta_boxes.order_id,
				details: toggle
			} );
		} );
} );
