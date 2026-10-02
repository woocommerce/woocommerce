/* global wcCopyText */
/**
 * Copy formatted order addresses to the clipboard.
 *
 * Used on the order edit screen and in the order preview modal. The copied text is read
 * from the rendered address, so it always matches what the merchant sees.
 *
 * @since 11.3.0
 */
jQuery( function( $ ) {
	var hideTimer = null,
		messageShown = false;

	/**
	 * Hide the copy tooltip, if one is shown.
	 */
	function hideMessage() {
		clearTimeout( hideTimer );

		if ( messageShown ) {
			messageShown = false;
			$( '#tiptip_holder' ).hide();
		}
	}

	/**
	 * Show a tooltip and announce a message for a copy button.
	 *
	 * @param {jQuery}  $button  Copy button.
	 * @param {string}  message  Message to show.
	 * @param {boolean} autoHide Whether to hide the tooltip after a short delay.
	 */
	function showMessage( $button, message, autoHide ) {
		if ( ! message ) {
			return;
		}

		clearTimeout( hideTimer );
		$button.data( 'wcCopyMessage', message );

		if ( ! $button.data( 'wcCopyTip' ) ) {
			$button.data( 'wcCopyTip', true ).tipTip( {
				activation: 'focus',
				fadeIn: 50,
				fadeOut: 50,
				delay: 0,
				content: function() {
					return $button.data( 'wcCopyMessage' );
				}
			} );
		}

		// Re-focus so the tooltip shows even when the button already has focus.
		$button.trigger( 'blur' ).trigger( 'focus' );
		messageShown = true;

		if ( autoHide ) {
			hideTimer = setTimeout( hideMessage, 2000 );
		}

		if ( window.wp && window.wp.a11y ) {
			window.wp.a11y.speak( message );
		}
	}

	/**
	 * Get the formatted address element for a copy button.
	 *
	 * @param {jQuery} $button Copy button.
	 * @return {HTMLElement|undefined} Address element.
	 */
	function getAddress( $button ) {
		return $button
			.closest( '.order_data_column, .wc-order-preview-address' )
			.find( '.wc-order-formatted-address' )
			.get( 0 );
	}

	$( document.body )
		.on( 'click', '.wc-order-copy-address', function( e ) {
			var $button = $( this ),
				address = getAddress( $button );

			e.preventDefault();

			if ( ! address ) {
				return;
			}

			// innerText turns <br> tags into line breaks.
			wcCopyText( address.innerText.trim(), $button );
		} )
		.on( 'aftercopy', '.wc-order-copy-address', function() {
			var $button = $( this );

			showMessage( $button, $button.attr( 'data-tip' ), true );
		} )
		.on( 'aftercopyfailure', '.wc-order-copy-address', function() {
			var $button = $( this ),
				address = getAddress( $button ),
				range;

			// Select the address so the merchant can copy it manually.
			if ( address && window.getSelection ) {
				range = document.createRange();
				range.selectNodeContents( address );
				window.getSelection().removeAllRanges();
				window.getSelection().addRange( range );
			}

			showMessage( $button, $button.attr( 'data-tip-failed' ), false );
		} )
		// The preview modal removes its copy buttons without blurring them.
		.on( 'wc_backbone_modal_removed', hideMessage );
} );
