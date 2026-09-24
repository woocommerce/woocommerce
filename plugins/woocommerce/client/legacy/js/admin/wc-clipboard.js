/* exported wcSetClipboard, wcClearClipboard, wcCopyText */

/**
 * Simple text copy functions using native browser clipboard capabilities.
 * @since 3.2.0
 */

/**
 * Set the user's clipboard contents.
 *
 * @param string data: Text to copy to clipboard.
 * @param object $el: jQuery element to trigger copy events on. (Default: document)
 */
function wcSetClipboard( data, $el ) {
	if ( 'undefined' === typeof $el ) {
		$el = jQuery( document );
	}
	var $temp_input = jQuery( '<textarea style="opacity:0">' );
	jQuery( 'body' ).append( $temp_input );
	$temp_input.val( data ).trigger( 'select' );

	$el.trigger( 'beforecopy' );
	try {
		if ( document.execCommand( 'copy' ) ) {
			$el.trigger( 'aftercopy' );
		} else {
			$el.trigger( 'aftercopyfailure' );
		}
	} catch ( err ) {
		$el.trigger( 'aftercopyfailure' );
	}

	$temp_input.remove();
}

/**
 * Clear the user's clipboard.
 */
function wcClearClipboard() {
	wcSetClipboard( '' );
}

/**
 * Copy text to the user's clipboard.
 *
 * Uses the asynchronous Clipboard API where the page is a secure context, and falls
 * back to wcSetClipboard() elsewhere. Triggers the same events as wcSetClipboard().
 *
 * @since 11.3.0
 *
 * @param string data: Text to copy to clipboard.
 * @param object $el: jQuery element to trigger copy events on. (Default: document)
 */
function wcCopyText( data, $el ) {
	if ( 'undefined' === typeof $el ) {
		$el = jQuery( document );
	}

	if ( ! window.isSecureContext || ! navigator.clipboard || ! navigator.clipboard.writeText ) {
		wcSetClipboard( data, $el );
		return;
	}

	$el.trigger( 'beforecopy' );
	navigator.clipboard.writeText( data ).then(
		function() {
			$el.trigger( 'aftercopy' );
		},
		function() {
			$el.trigger( 'aftercopyfailure' );
		}
	);
}
