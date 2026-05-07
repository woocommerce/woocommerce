/* global document, window */
/**
 * Progressive enhancement for the Review Order page.
 *
 * Adds keyboard navigation (Left/Right/Up/Down + Home/End) and a dynamic
 * caption to every `.woocommerce-star-rating` group on the page.
 * Without this script the underlying native radio inputs still work.
 */
( function () {
	'use strict';

	/**
	 * @param {HTMLElement} container `.woocommerce-star-rating` element.
	 */
	function initGroup( container ) {
		var inputs = Array.prototype.slice.call(
			container.querySelectorAll( '.woocommerce-star-rating__input' )
		);
		var captionId = container.getAttribute( 'aria-describedby' );
		var caption = captionId ? document.getElementById( captionId ) : null;

		function syncCaption() {
			if ( ! caption ) {
				return;
			}
			var checked = inputs.filter( function ( input ) {
				return input.checked;
			} )[ 0 ];
			caption.textContent = checked
				? checked.getAttribute( 'data-label' ) || ''
				: '';
		}

		function focusInput( input ) {
			input.focus();
			input.checked = true;
			input.dispatchEvent( new window.Event( 'change', { bubbles: true } ) );
		}

		// DOM order is 5..1 (reversed for the CSS row-reverse layout), so
		// "next visual star" is the previous DOM input and vice-versa.
		// Home/End map to visual-leftmost / visual-rightmost = inputs[last] /
		// inputs[0] in DOM order.
		inputs.forEach( function ( input, index ) {
			input.addEventListener( 'change', syncCaption );

			input.addEventListener( 'keydown', function ( event ) {
				var nextIndex = null;
				switch ( event.key ) {
					case 'ArrowRight':
					case 'ArrowDown':
						nextIndex =
							( index - 1 + inputs.length ) % inputs.length;
						break;
					case 'ArrowLeft':
					case 'ArrowUp':
						nextIndex = ( index + 1 ) % inputs.length;
						break;
					case 'Home':
						nextIndex = inputs.length - 1;
						break;
					case 'End':
						nextIndex = 0;
						break;
					default:
						return;
				}
				event.preventDefault();
				focusInput( inputs[ nextIndex ] );
			} );
		} );

		syncCaption();
	}

	function init() {
		var groups = document.querySelectorAll( '.woocommerce-star-rating' );
		Array.prototype.forEach.call( groups, initGroup );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
