/* global document, window */
/**
 * Progressive enhancement for the Review Order page.
 *
 * Adds keyboard navigation (Left/Right/Up/Down + Home/End) and a dynamic
 * caption to every `.woocommerce-star-rating` group on the page. Gates the
 * submit button on the form being dirty (i.e. the customer has changed
 * something from the prefilled state), enforces an inline "Please rate this
 * product before submitting your review." validation on rows with typed
 * text but no rating, and wires the dismiss control on the
 * disabled-products notice.
 *
 * Without this script the underlying native radio inputs still work; the
 * customer can submit and the server-side handler validates per row.
 */
( function () {
	'use strict';

	var ERROR_CLASS = 'woocommerce-review-order__item-rating-error';

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
	 * Return the currently selected rating (1-5) for a row, or 0 if none.
	 *
	 * @param {HTMLElement} row `.woocommerce-review-order__item`
	 * @return {number}
	 */
	function currentRating( row ) {
		var checked = row.querySelector(
			'.woocommerce-star-rating__input:checked'
		);
		return checked ? parseInt( checked.value, 10 ) || 0 : 0;
	}

	/**
	 * Return the current textarea value for a row (trimmed).
	 *
	 * @param {HTMLElement} row `.woocommerce-review-order__item`
	 * @return {string}
	 */
	function currentText( row ) {
		var textarea = row.querySelector(
			'.woocommerce-review-order__item-review-textarea'
		);
		return textarea ? ( textarea.value || '' ).trim() : '';
	}

	/**
	 * Whether a row has been edited since page load.
	 *
	 * @param {HTMLElement} row `.woocommerce-review-order__item`
	 * @return {boolean}
	 */
	function isRowDirty( row ) {
		var initialRating = parseInt(
			row.getAttribute( 'data-initial-rating' ) || '0',
			10
		) || 0;
		var initialText = row.getAttribute( 'data-initial-text' ) || '';
		return (
			currentRating( row ) !== initialRating ||
			currentText( row ) !== initialText
		);
	}

	/**
	 * Enable / disable the review-order submit button based on whether at
	 * least one row has been edited since page load.
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
						'the dirty gate will not run.'
				);
			}
			return;
		}

		var rows = Array.prototype.slice.call(
			form.querySelectorAll( '.woocommerce-review-order__item' )
		);

		function syncSubmit() {
			submit.disabled = ! rows.some( isRowDirty );
		}

		// Expose so initAjaxSubmit can re-run the gate after the request
		// completes (instead of unconditionally enabling the button).
		form.syncReviewOrderSubmitGate = syncSubmit;

		form.addEventListener( 'change', syncSubmit );
		form.addEventListener( 'input', syncSubmit );

		syncSubmit();
	}

	/**
	 * Show or clear the "Please rate this product..." inline error for one
	 * row. Mutating the DOM idempotently keeps the call sites simple.
	 *
	 * @param {HTMLElement} row     `.woocommerce-review-order__item`
	 * @param {boolean}     visible Whether the error should be shown.
	 */
	function setRowRatingError( row, visible ) {
		var rating = row.querySelector(
			'.woocommerce-review-order__item-rating'
		);
		if ( ! rating ) {
			return;
		}
		var existing = rating.querySelector( '.' + ERROR_CLASS );
		if ( ! visible ) {
			if ( existing ) {
				existing.parentNode.removeChild( existing );
			}
			return;
		}
		if ( existing ) {
			return;
		}
		var i18n = ( window.wcOrderReview && window.wcOrderReview.i18n ) || {};
		var msg =
			i18n.rating_required ||
			'Please rate this product before submitting your review.';
		var note = document.createElement( 'p' );
		note.className = ERROR_CLASS;
		note.setAttribute( 'role', 'alert' );
		note.textContent = msg;
		rating.appendChild( note );
	}

	/**
	 * Wire client-side validation: any row that has typed review text but no
	 * rating renders an inline error and blocks form submission until the
	 * customer either rates the product or clears the text. Rows that are
	 * entirely empty are still allowed (the "Feel free to skip" path).
	 *
	 * Returns the validation function so the AJAX submit handler can re-use
	 * the same logic.
	 *
	 * @param {HTMLFormElement} form `.woocommerce-review-order__form`
	 * @return {function(): boolean}
	 */
	function initRatingValidation( form ) {
		var rows = Array.prototype.slice.call(
			form.querySelectorAll( '.woocommerce-review-order__item' )
		);

		function validate() {
			var ok = true;
			rows.forEach( function ( row ) {
				var needsRating =
					currentText( row ).length > 0 && currentRating( row ) === 0;
				setRowRatingError( row, needsRating );
				if ( needsRating ) {
					ok = false;
				}
			} );
			return ok;
		}

		// Clear a row's error as soon as the customer fixes it (sets a
		// rating, or clears the textarea back to empty).
		rows.forEach( function ( row ) {
			row.addEventListener( 'change', function () {
				if (
					currentText( row ).length === 0 ||
					currentRating( row ) > 0
				) {
					setRowRatingError( row, false );
				}
			} );
			row.addEventListener( 'input', function () {
				if (
					currentText( row ).length === 0 ||
					currentRating( row ) > 0
				) {
					setRowRatingError( row, false );
				}
			} );
		} );

		return validate;
	}

	/**
	 * Render per-row outcome below the row's columns.
	 *
	 * @param {HTMLElement} row    `.woocommerce-review-order__item`
	 * @param {string}      status `ok | pending_moderation | error`
	 * @param {string}      [text] Optional message override.
	 */
	function renderRowStatus( row, status, text ) {
		var existing = row.querySelector(
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
		row.appendChild( note );
	}

	/**
	 * Intercept form submit and POST it to admin-ajax.
	 *
	 * @param {HTMLFormElement} form
	 * @param {function(): boolean} validate Returns true when the form is
	 *                                       safe to submit.
	 */
	function initAjaxSubmit( form, validate ) {
		var ajaxUrl = form.getAttribute( 'data-ajax-url' );
		if ( ! ajaxUrl ) {
			return;
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			if ( ! validate() ) {
				var firstError = form.querySelector( '.' + ERROR_CLASS );
				if ( firstError && typeof firstError.scrollIntoView === 'function' ) {
					firstError.scrollIntoView( {
						behavior: 'smooth',
						block: 'center',
					} );
				}
				return;
			}

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
					var anySaved = false;
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
						if (
							entry &&
							( entry.status === 'ok' ||
								entry.status === 'pending_moderation' )
						) {
							anySaved = true;
						}
					} );

					if ( anySaved ) {
						var wrapper = form.closest(
							'.woocommerce-review-order'
						);
						if ( wrapper ) {
							wrapper.classList.add( 'is-success' );
							var success = wrapper.querySelector(
								'.woocommerce-review-order__success'
							);
							if ( success ) {
								success.hidden = false;
							}
							if (
								typeof wrapper.scrollIntoView === 'function'
							) {
								wrapper.scrollIntoView( {
									behavior: 'smooth',
									block: 'start',
								} );
							}
						}
					}
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

	/**
	 * Wire the dismiss control on the disabled-products notice. Dismissal is
	 * in-page only; revisiting the URL re-renders the notice from PHP.
	 *
	 * @param {HTMLElement} notice `.woocommerce-review-order__notice`
	 */
	function initNoticeDismiss( notice ) {
		var dismiss = notice.querySelector(
			'.woocommerce-review-order__notice-dismiss'
		);
		if ( ! dismiss ) {
			return;
		}
		dismiss.addEventListener( 'click', function () {
			notice.classList.add( 'woocommerce-review-order__notice--hidden' );
		} );
	}

	function init() {
		var groups = document.querySelectorAll( '.woocommerce-star-rating' );
		Array.prototype.forEach.call( groups, initGroup );

		var forms = document.querySelectorAll(
			'.woocommerce-review-order__form'
		);
		Array.prototype.forEach.call( forms, function ( form ) {
			initSubmitGate( form );
			var validate = initRatingValidation( form );
			initAjaxSubmit( form, validate );
		} );

		var notices = document.querySelectorAll(
			'.woocommerce-review-order__notice'
		);
		Array.prototype.forEach.call( notices, initNoticeDismiss );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
