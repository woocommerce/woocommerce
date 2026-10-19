/* global document, window */
/**
 * Progressive enhancement for the Review Order page. Without this script the
 * native radios + form post still work; server validates per row.
 */
( function () {
	'use strict';

	var ERROR_CLASS = 'woocommerce-review-order__item-rating-error';
	var STATUS_CLASS = 'woocommerce-review-order__item-status';
	var HINT_CLASS = 'woocommerce-review-order__submit-hint';
	var uid = 0;

	/**
	 * Add or remove one token from a space-separated attribute.
	 *
	 * @param {HTMLElement} el
	 * @param {string}      attr
	 * @param {string}      token
	 * @param {boolean}     on
	 */
	function toggleToken( el, attr, token, on ) {
		var tokens = ( el.getAttribute( attr ) || '' )
			.split( /\s+/ )
			.filter( function ( entry ) {
				return entry && entry !== token;
			} );
		if ( on ) {
			tokens.push( token );
		}
		if ( tokens.length ) {
			el.setAttribute( attr, tokens.join( ' ' ) );
		} else {
			el.removeAttribute( attr );
		}
	}

	/**
	 * The row's message element, created only for a theme override that
	 * dropped the paragraph the templates render up front.
	 *
	 * @param {HTMLElement} row       `.woocommerce-review-order__item`
	 * @param {string}      className Element class.
	 * @param {?string}     role      ARIA role, or null for a plain paragraph.
	 * @return {HTMLElement}
	 */
	function rowRegion( row, className, role ) {
		// Any depth: a theme override may wrap it, and a child-only lookup
		// would miss it and add another on every call.
		var region = row.querySelector( '.' + className );
		if ( region ) {
			// An override may render the paragraph without the role. Only
			// when it has none, so a deliberate `role="alert"` survives;
			// `role=""` counts as none, since an empty token list leaves the
			// element without a role.
			var existingRole = ( region.getAttribute( 'role' ) || '' ).trim();
			if ( role && ! existingRole ) {
				region.setAttribute( 'role', role );
			}
			return region;
		}

		region = document.createElement( 'p' );
		region.className = className;
		if ( role ) {
			region.setAttribute( 'role', role );
		}

		if ( STATUS_CLASS === className ) {
			row.appendChild( region );
			return region;
		}

		// Under the title, not below the stars, so the message is at the top
		// of the row on tall layouts.
		var title = row.querySelector(
			'.woocommerce-review-order__item-title'
		);
		if ( title ) {
			title.parentNode.insertBefore( region, title.nextSibling );
		} else {
			row.insertBefore( region, row.firstChild );
		}
		return region;
	}

	/**
	 * @param {HTMLElement} el
	 * @return {string} The element's id, assigning a generated one if needed.
	 */
	function idOf( el ) {
		if ( ! el.id ) {
			uid += 1;
			el.id = 'woocommerce-review-order-region-' + uid;
		}
		return el.id;
	}

	/**
	 * @param {HTMLElement} container `.woocommerce-star-rating` element.
	 */
	function initGroup( container ) {
		var inputs = Array.prototype.slice.call(
			container.querySelectorAll( '.woocommerce-star-rating__input' )
		);
		var caption = container.querySelector(
			'.woocommerce-star-rating__caption'
		);

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

		// DOM order is 5..1, so the next visual star is the previous input;
		// RTL flips the row again, taking the horizontal keys with it.
		inputs.forEach( function ( input, index ) {
			input.addEventListener( 'change', syncCaption );

			input.addEventListener( 'keydown', function ( event ) {
				var rtl =
					'rtl' ===
					window.getComputedStyle( container ).direction;
				var forward = ( index - 1 + inputs.length ) % inputs.length;
				var backward = ( index + 1 ) % inputs.length;
				var nextIndex = null;
				switch ( event.key ) {
					case 'ArrowRight':
						nextIndex = rtl ? backward : forward;
						break;
					case 'ArrowDown':
						nextIndex = forward;
						break;
					case 'ArrowLeft':
						nextIndex = rtl ? forward : backward;
						break;
					case 'ArrowUp':
						nextIndex = backward;
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
		// Trim to match currentText so prefilled whitespace doesn't mark the row dirty.
		var initialText = ( row.getAttribute( 'data-initial-text' ) || '' ).trim();
		return (
			currentRating( row ) !== initialRating ||
			currentText( row ) !== initialText
		);
	}

	/**
	 * @param {HTMLElement} submit Submit button.
	 * @return {boolean} Whether the dirty gate is currently closed.
	 */
	function isGated( submit ) {
		return 'true' === submit.getAttribute( 'aria-disabled' );
	}

	/**
	 * The hint that says why a gated submit does nothing. Created here rather
	 * than rendered by the template, because the gate only exists when this
	 * script runs.
	 *
	 * @param {HTMLElement} submit Submit button.
	 * @return {HTMLElement}
	 */
	function submitHint( submit ) {
		var parent = submit.parentNode || submit;
		var hint = parent.querySelector( '.' + HINT_CLASS );
		if ( hint ) {
			return hint;
		}

		var i18n = ( window.wcOrderReview && window.wcOrderReview.i18n ) || {};
		hint = document.createElement( 'p' );
		hint.className = HINT_CLASS;
		hint.textContent =
			i18n.submit_hint || 'Add or change a rating or review to submit.';
		parent.appendChild( hint );
		return hint;
	}

	/**
	 * Enable / disable the review-order submit button based on whether at
	 * least one row has been edited since page load.
	 *
	 * `aria-disabled` rather than `disabled`, so the button stays in the tab
	 * order and a screen reader can reach it.
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
		var hint = submitHint( submit );

		function syncSubmit() {
			var dirty = rows.some( isRowDirty );
			var ready = ! form.wcReviewOrderSubmitting && dirty;
			submit.setAttribute( 'aria-disabled', ready ? 'false' : 'true' );
			// Keyed on `dirty`, not `ready`: an in-flight submit closes the
			// gate too, and the hint would then name the wrong reason. An
			// override that renders the form with no rows offers nothing to
			// add or change either.
			var explain = ! dirty && rows.length > 0;
			hint.hidden = ! explain;
			toggleToken( submit, 'aria-describedby', idOf( hint ), explain );
		}

		// `aria-disabled` does not stop the browser submitting, and the AJAX
		// handler bails out without `data-ajax-url`. `requestSubmit()` raises
		// no click, so both events need the guard.
		submit.addEventListener( 'click', function ( event ) {
			if ( isGated( submit ) ) {
				event.preventDefault();
			}
		} );

		form.addEventListener( 'submit', function ( event ) {
			if ( isGated( submit ) ) {
				event.preventDefault();
			}
		} );

		// Expose so initAjaxSubmit can re-run the gate after the request completes.
		form.syncReviewOrderSubmitGate = syncSubmit;

		form.addEventListener( 'change', syncSubmit );
		form.addEventListener( 'input', syncSubmit );

		syncSubmit();
	}

	/**
	 * Show or clear a row's rating error. Not announced on its own: the
	 * caller focuses the first invalid rating, and `aria-describedby` carries
	 * the rest when the customer reaches them.
	 *
	 * @param {HTMLElement} row     `.woocommerce-review-order__item`
	 * @param {boolean}     visible Whether the error should be shown.
	 */
	function setRowRatingError( row, visible ) {
		var region = rowRegion( row, ERROR_CLASS, null );
		// On the radios, not the group: a screen reader reports these for the
		// focused element, and focus lands on a radio.
		var inputs = Array.prototype.slice.call(
			row.querySelectorAll( '.woocommerce-star-rating__input' )
		);

		if ( ! visible ) {
			region.textContent = '';
			inputs.forEach( function ( input ) {
				if ( ! input.hasAttribute( 'aria-invalid' ) ) {
					return;
				}
				input.removeAttribute( 'aria-invalid' );
				toggleToken( input, 'aria-describedby', region.id, false );
			} );
			return;
		}

		var i18n = ( window.wcOrderReview && window.wcOrderReview.i18n ) || {};
		region.textContent =
			i18n.rating_required ||
			'Please rate this product before submitting your review.';

		inputs.forEach( function ( input ) {
			input.setAttribute( 'aria-invalid', 'true' );
			toggleToken( input, 'aria-describedby', idOf( region ), true );
		} );
	}

	/**
	 * Focus a group's first star in reading order, which the reversed markup
	 * puts last in DOM order.
	 *
	 * @param {HTMLElement} scope Rating group, or any ancestor of one.
	 * @return {?HTMLElement} The focused radio, or null.
	 */
	function focusFirstStar( scope ) {
		if ( ! scope ) {
			return null;
		}
		var inputs = scope.querySelectorAll( '.woocommerce-star-rating__input' );
		var first = inputs[ inputs.length - 1 ];
		if ( ! first || typeof first.focus !== 'function' ) {
			return null;
		}
		// The radios are 1px, so letting the browser scroll to one can leave
		// the message it points at off-screen.
		first.focus( { preventScroll: true } );
		return first;
	}

	/**
	 * Bring an invalid row's rating error into view, then its focused star.
	 *
	 * @param {HTMLElement}  input   Radio carrying `aria-invalid`.
	 * @param {?HTMLElement} focused Radio `focusFirstStar` focused, if any.
	 */
	function revealRatingError( input, focused ) {
		var row = input.closest( '.woocommerce-review-order__item' );
		var message = row && row.querySelector( '.' + ERROR_CLASS );
		if ( message && typeof message.scrollIntoView === 'function' ) {
			// `start`, not `center`: in the stacked layout the stars sit below
			// the image, and centring the message pushes them off-screen.
			message.scrollIntoView( { block: 'start' } );
		}

		// Stacked, the two are ~360px apart and a short viewport cannot hold
		// both, so the ring wins: it is the clipped radios' only indicator.
		var label = focused && focused.labels && focused.labels[ 0 ];
		if ( label && typeof label.scrollIntoView === 'function' ) {
			label.scrollIntoView( { block: 'nearest' } );
		}
	}

	/**
	 * @param {HTMLFormElement} form `.woocommerce-review-order__form`
	 * @return {function(): boolean} Validator the AJAX submit handler re-runs.
	 */
	function initRatingValidation( form ) {
		var rows = Array.prototype.slice.call(
			form.querySelectorAll( '.woocommerce-review-order__item' )
		);

		// Up front, because a live region created in the same step that fills
		// it is not announced, and an override copied before the templates
		// rendered these has neither.
		rows.forEach( function ( row ) {
			rowRegion( row, ERROR_CLASS, null );
			rowRegion( row, STATUS_CLASS, 'status' );
		} );

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
		var region = rowRegion( row, STATUS_CLASS, 'status' );
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

		[ 'ok', 'pending_moderation', 'error' ].forEach( function ( name ) {
			region.classList.remove( STATUS_CLASS + '--' + name );
		} );
		region.classList.add( STATUS_CLASS, STATUS_CLASS + '--' + status );
		region.textContent = text || defaults[ status ] || defaults.error;
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

			var submit = form.querySelector(
				'.woocommerce-review-order__submit'
			);
			if ( form.wcReviewOrderSubmitting || ( submit && isGated( submit ) ) ) {
				return;
			}

			if ( ! validate() ) {
				var firstInvalid = form.querySelector(
					'.woocommerce-star-rating__input[aria-invalid="true"]'
				);
				if ( firstInvalid ) {
					var focused = focusFirstStar(
						firstInvalid.closest( '.woocommerce-star-rating' )
					);
					revealRatingError( firstInvalid, focused );
				}
				return;
			}

			form.wcReviewOrderSubmitting = true;
			if ( submit ) {
				submit.setAttribute( 'aria-disabled', 'true' );
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
					var anyFailed = false;
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
						if ( ! entry || ! entry.status ) {
							anyFailed = true;
							return;
						}
						if (
							entry.status === 'ok' ||
							entry.status === 'pending_moderation'
						) {
							anySaved = true;
						} else {
							anyFailed = true;
						}
					} );

					if ( anySaved && ! anyFailed ) {
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
								// `is-success` hides the form and the
								// focused button in it. The heading, not
								// the container: an unnamed div announces
								// nothing in some screen readers.
								var target =
									success.querySelector( 'h1, h2' ) ||
									success;
								if ( ! target.hasAttribute( 'tabindex' ) ) {
									target.setAttribute( 'tabindex', '-1' );
								}
								if ( typeof target.focus === 'function' ) {
									target.focus( { preventScroll: true } );
								}
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
					form.wcReviewOrderSubmitting = false;
					if ( typeof form.syncReviewOrderSubmitGate === 'function' ) {
						form.syncReviewOrderSubmitGate();
					} else if ( submit ) {
						submit.setAttribute( 'aria-disabled', 'false' );
					}
				} );
		} );
	}

	/**
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

			// The dismiss button goes with the notice, so focus would fall to
			// the document. Tabindex set here for theme overrides without it.
			var wrapper = notice.closest( '.woocommerce-review-order' );
			var heading = ( wrapper || document ).querySelector(
				'.woocommerce-review-order__title'
			);
			if ( heading && typeof heading.focus === 'function' ) {
				if ( ! heading.hasAttribute( 'tabindex' ) ) {
					heading.setAttribute( 'tabindex', '-1' );
				}
				heading.focus();
			}
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
