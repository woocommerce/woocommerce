/*global woocommerce_admin_meta_boxes, wcTracks */
jQuery( document ).ready( ( $ ) => {
	'use strict';

	// Stand-in wcTracks.recordEvent in case Tracks is not available (for any reason).
	window.wcTracks = window.wcTracks || {};
	window.wcTracks.recordEvent = window.wcTracks.recordEvent || ( () => {} );

	// Handle the "Details" container toggle.
	$( '.woocommerce-order-attribution-details-toggle' )
		.on( 'click', ( e ) => {
			const $this = $( e.target ).closest( '.woocommerce-order-attribution-details-toggle');
			const $container = $this.closest( '.order-attribution-metabox' )
											.find( '.woocommerce-order-attribution-details-container' );
			const $origin_detail = $this.closest( '.order-attribution-metabox' )
											.find( '.order-attribution-origin' );
			let toggle = '';

			e.preventDefault();

			$container.fadeToggle( 250 );
			$origin_detail.toggleClass('expanded');
			$this.find( '.toggle-text' ).toggle();
			if ( $container.hasClass( 'closed' ) ) {
				$this.attr( 'aria-expanded', 'true' );
				toggle = 'opened';
			} else {
				$this.attr( 'aria-expanded', 'false' );
				toggle = 'closed';
			}
			$container.toggleClass( 'closed' );

			window.wcTracks.recordEvent( 'order_attribution_details_toggle', {
				order_id: window.woocommerce_admin_meta_boxes.order_id,
				details: toggle
			} );
		} );
} );
