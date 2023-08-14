jQuery( document ).ready( ( $ ) => {
	'use strict';

	// Handle our details container toggle.
	$( '.woocommerce-order-source-attribution-details-toggle' )
		.on( 'click', function ( e ) {
			var $this = $( this );
			var $container = $this.siblings( '.woocommerce-order-source-attribution-details-container' );
			var $toggle = $this.find( '.toggle-indicator' );

			e.preventDefault();

			if ( $container.hasClass( 'closed' ) ) {
				$container.removeClass( 'closed' );
				$container.fadeIn( 250 );
				$this.attr( 'aria-expanded', 'true' );
			} else {
				$container.addClass( 'closed' );
				$container.fadeOut( 250 );
				$this.attr( 'aria-expanded', 'false' );
			}
		} );
} );
