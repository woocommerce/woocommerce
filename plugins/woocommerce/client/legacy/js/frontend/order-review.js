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

	/**
	 * Enable / disable the review-order submit button based on whether at
	 * least one row has a rating selected.
	 *
	 * @param {HTMLFormElement} form `.woocommerce-review-order__form`
	 */
	function initSubmitGate( form ) {
		var submit = form.querySelector( '.woocommerce-review-order__submit' );
		if ( ! submit ) {
			if ( window.console && window.console.warn ) {
				window.console.warn(
					'Review Order form is missing its submit button ' +
						'(.woocommerce-review-order__submit); ' +
						'the rating-based gate will not run.'
				);
			}
			return;
		}

		function syncSubmit() {
			var anyChecked = !! form.querySelector(
				'.woocommerce-star-rating__input:checked'
			);
			submit.disabled = ! anyChecked;
		}

		// Expose so initAjaxSubmit can re-run the gate after the request
		// completes (instead of unconditionally enabling the button).
		form.syncReviewOrderSubmitGate = syncSubmit;

		form.addEventListener( 'change', function ( event ) {
			if (
				event.target &&
				event.target.classList &&
				event.target.classList.contains(
					'woocommerce-star-rating__input'
				)
			) {
				syncSubmit();
			}
		} );

		syncSubmit();
	}

	/**
	 * Render per-row outcome inside a row's fields container.
	 *
	 * @param {HTMLElement} row    `.woocommerce-review-order__item`
	 * @param {string}      status `ok | pending_moderation | error`
	 * @param {string}      [text] Optional message override.
	 */
	function renderRowStatus( row, status, text ) {
		var fields = row.querySelector(
			'.woocommerce-review-order__item-fields'
		);
		if ( ! fields ) {
			return;
		}
		var existing = fields.querySelector(
			'.woocommerce-review-order__item-status'
		);
		if ( existing ) {
			existing.parentNode.removeChild( existing );
		}
		var i18n =
			( window.wcOrderReview && window.wcOrderReview.i18n ) || {};
		var defaults = {
			ok: i18n.ok || 'Thanks, your review is live.',
			pending_moderation:
				i18n.pending_moderation ||
				'Thanks, your review is pending approval.',
			error:
				i18n.error || 'Something went wrong, please try again.',
		};
		var note = document.createElement( 'p' );
		note.className =
			'woocommerce-review-order__item-status woocommerce-review-order__item-status--' +
			status;
		note.setAttribute( 'role', 'status' );
		note.textContent = text || defaults[ status ] || defaults.error;
		fields.appendChild( note );
	}

	/**
	 * Intercept form submit and POST it to admin-ajax.
	 *
	 * @param {HTMLFormElement} form
	 */
	function initAjaxSubmit( form ) {
		var ajaxUrl = form.getAttribute( 'data-ajax-url' );
		if ( ! ajaxUrl ) {
			return;
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var submit = form.querySelector(
				'.woocommerce-review-order__submit'
			);
			if ( submit ) {
				submit.disabled = true;
			}

			window
				.fetch( ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: new window.FormData( form ),
				} )
				.then( function ( response ) {
					return response.json().catch( function () {
						return { success: false };
					} );
				} )
				.then( function ( payload ) {
					if ( ! payload || ! payload.success || ! payload.data ) {
						Array.prototype.forEach.call(
							form.querySelectorAll(
								'.woocommerce-review-order__item'
							),
							function ( row ) {
								if (
									row.querySelector(
										'.woocommerce-star-rating__input:checked'
									)
								) {
									renderRowStatus( row, 'error' );
								}
							}
						);
						return;
					}

					var results = payload.data.results || {};
					Object.keys( results ).forEach( function ( key ) {
						var entry = results[ key ];
						var row = form.querySelector(
							'.woocommerce-review-order__item[data-row-index="' +
								key +
								'"]'
						);
						if ( row && entry && entry.status ) {
							renderRowStatus( row, entry.status );
						}
					} );
				} )
				.catch( function () {
					Array.prototype.forEach.call(
						form.querySelectorAll(
							'.woocommerce-review-order__item'
						),
						function ( row ) {
							if (
								row.querySelector(
									'.woocommerce-star-rating__input:checked'
								)
							) {
								renderRowStatus( row, 'error' );
							}
						}
					);
				} )
				.then( function () {
					if ( typeof form.syncReviewOrderSubmitGate === 'function' ) {
						form.syncReviewOrderSubmitGate();
					} else if ( submit ) {
						submit.disabled = false;
					}
				} );
		} );
	}

	function init() {
		var groups = document.querySelectorAll( '.woocommerce-star-rating' );
		Array.prototype.forEach.call( groups, initGroup );

		var forms = document.querySelectorAll(
			'.woocommerce-review-order__form'
		);
		Array.prototype.forEach.call( forms, initSubmitGate );
		Array.prototype.forEach.call( forms, initAjaxSubmit );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
