/* global woocommerce_settings_params, wp */
( function ( $, params, wp ) {
	$( function () {
		// Sell Countries
		$( 'select#woocommerce_allowed_countries' )
			.on( 'change', function () {
				if ( 'specific' === $( this ).val() ) {
					$( this ).closest( 'tr' ).next( 'tr' ).hide();
					$( this ).closest( 'tr' ).next().next( 'tr' ).show();
				} else if ( 'all_except' === $( this ).val() ) {
					$( this ).closest( 'tr' ).next( 'tr' ).show();
					$( this ).closest( 'tr' ).next().next( 'tr' ).hide();
				} else {
					$( this ).closest( 'tr' ).next( 'tr' ).hide();
					$( this ).closest( 'tr' ).next().next( 'tr' ).hide();
				}
			} )
			.trigger( 'change' );

		// Ship Countries
		$( 'select#woocommerce_ship_to_countries' )
			.on( 'change', function () {
				if ( 'specific' === $( this ).val() ) {
					$( this ).closest( 'tr' ).next( 'tr' ).show();
				} else {
					$( this ).closest( 'tr' ).next( 'tr' ).hide();
				}
			} )
			.trigger( 'change' );

		// Stock management
		$( 'input#woocommerce_manage_stock' )
			.on( 'change', function () {
				if ( $( this ).is( ':checked' ) ) {
					$( this )
						.closest( 'tbody' )
						.find( '.manage_stock_field' )
						.closest( 'tr' )
						.show();
				} else {
					$( this )
						.closest( 'tbody' )
						.find( '.manage_stock_field' )
						.closest( 'tr' )
						.hide();
				}
			} )
			.trigger( 'change' );

		// Color picker
		$( '.colorpick' )
			.iris( {
				change: function ( event, ui ) {
					$( this )
						.parent()
						.find( '.colorpickpreview' )
						.css( { backgroundColor: ui.color.toString() } );
				},
				hide: true,
				border: true,
			} )

			.on( 'click focus', function ( event ) {
				event.stopPropagation();
				$( '.iris-picker' ).hide();
				$( this ).closest( 'td' ).find( '.iris-picker' ).show();
				$( this ).data( 'originalValue', $( this ).val() );
			} )

			.on( 'change', function () {
				if ( $( this ).is( '.iris-error' ) ) {
					var original_value = $( this ).data( 'originalValue' );

					if (
						original_value.match(
							/^\#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/
						)
					) {
						$( this )
							.val( $( this ).data( 'originalValue' ) )
							.trigger( 'change' );
					} else {
						$( this ).val( '' ).trigger( 'change' );
					}
				}
			} );

		$( 'body' ).on( 'click', function () {
			$( '.iris-picker' ).hide();
		} );

		// Edit prompt
		function editPrompt () {
			var changed = false;
			let $check_column = $( '.wp-list-table .check-column' );

			$( 'input, textarea, select, checkbox' ).on( 'change input', function (
				event
			) {
				// Toggling WP List Table checkboxes should not trigger navigation warnings.
				// Theses checkboxes only select/unselect rows, they don't change the form.
				if (
					$check_column.length &&
					$check_column.has( event.target ).length
				) {
					return;
				}

				if ( ! changed ) {
					window.onbeforeunload = function () {
						return params.i18n_nav_warning;
					};
					changed = true;
					$( '.woocommerce-save-button' ).removeAttr( 'disabled' );
				}
			} );

			$( '.iris-picker' ).on( 'click', function () {
				if ( ! changed ) {
					changed = true;
					$( '.woocommerce-save-button' ).removeAttr( 'disabled' );
				}
			} );

			$( '.submit :input, input#search-submit' ).on(
				'click',
				function () {
					window.onbeforeunload = '';
				}
			);
		}

		$( editPrompt );

		const nodeListContainsFormElements = ( nodes ) => {
			if ( ! nodes.length	) {
				return false;
			}
			return Array.from( nodes ).some( ( element ) => {
				return $( element ).find( 'input, textarea, select, checkbox' ).length;
			} );
		}

		const form = document.querySelector( '#mainform' );
		const observer = new MutationObserver( ( mutationsList ) => {
			for ( const mutation of mutationsList ) {
				if ( mutation.type === 'childList' ) {
					if ( nodeListContainsFormElements( mutation.addedNodes ) ) {
						editPrompt();
						$( '.woocommerce-save-button' ).removeAttr( 'disabled' );
					} else if ( nodeListContainsFormElements( mutation.removedNodes ) ) {
						$( '.woocommerce-save-button' ).removeAttr( 'disabled' );
					}
				}
			}
		} );

		observer.observe( form, { childList: true, subtree: true } );

		// Sorting
		$( 'table.wc_gateways tbody, table.wc_shipping tbody' ).sortable( {
			items: 'tr',
			cursor: 'move',
			axis: 'y',
			handle: 'td.sort',
			scrollSensitivity: 40,
			helper: function ( event, ui ) {
				ui.children().each( function () {
					$( this ).width( $( this ).width() );
				} );
				ui.css( 'left', '0' );
				return ui;
			},
			start: function ( event, ui ) {
				ui.item.css( 'background-color', '#f6f6f6' );
			},
			stop: function ( event, ui ) {
				ui.item.removeAttr( 'style' );
				ui.item.trigger( 'updateMoveButtons', { isInitialLoad: false } );
			},
		} );

		// Select all/none
		$( '.woocommerce' ).on( 'click', '.select_all', function () {
			$( this )
				.closest( 'td' )
				.find( 'select option' )
				.prop( 'selected', true );
			$( this ).closest( 'td' ).find( 'select' ).trigger( 'change' );
			return false;
		} );

		$( '.woocommerce' ).on( 'click', '.select_none', function () {
			$( this )
				.closest( 'td' )
				.find( 'select option' )
				.prop( 'selected', false );
			$( this ).closest( 'td' ).find( 'select' ).trigger( 'change' );
			return false;
		} );

		// Re-order buttons.
		$( '.wc-item-reorder-nav' )
			.find( '.wc-move-up, .wc-move-down' )
			.on( 'click', function () {
				var moveBtn = $( this ),
					$row = moveBtn.closest( 'tr' );

				moveBtn.trigger( 'focus' );

				var isMoveUp = moveBtn.is( '.wc-move-up' ),
					isMoveDown = moveBtn.is( '.wc-move-down' );

				if ( isMoveUp ) {
					var $previewRow = $row.prev( 'tr' );

					if ( $previewRow && $previewRow.length ) {
						$previewRow.before( $row );
						wp.a11y.speak( params.i18n_moved_up );
					}
				} else if ( isMoveDown ) {
					var $nextRow = $row.next( 'tr' );

					if ( $nextRow && $nextRow.length ) {
						$nextRow.after( $row );
						wp.a11y.speak( params.i18n_moved_down );
					}
				}

				moveBtn.trigger( 'focus' ); // Re-focus after the container was moved.
				moveBtn.closest( 'table' ).trigger( 'updateMoveButtons', { isInitialLoad: false } );
			} );

		$( '.wc-item-reorder-nav' )
			.closest( 'table' )
			.on( 'updateMoveButtons', function ( event, data ) {
				var table = $( this ),
					lastRow = $( this ).find( 'tbody tr:last' ),
					firstRow = $( this ).find( 'tbody tr:first' );

				table
					.find( '.wc-item-reorder-nav .wc-move-disabled' )
					.removeClass( 'wc-move-disabled' )
					.attr( { tabindex: '0', 'aria-hidden': 'false' } );
				firstRow
					.find( '.wc-item-reorder-nav .wc-move-up' )
					.addClass( 'wc-move-disabled' )
					.attr( { tabindex: '-1', 'aria-hidden': 'true' } );
				lastRow
					.find( '.wc-item-reorder-nav .wc-move-down' )
					.addClass( 'wc-move-disabled' )
					.attr( { tabindex: '-1', 'aria-hidden': 'true' } );
				if ( ! data.isInitialLoad ) {
					$( '.woocommerce-save-button' ).removeAttr( 'disabled' );
				}	
			} );

		$( '.wc-item-reorder-nav' )
			.closest( 'table' )
			.trigger( 'updateMoveButtons', { isInitialLoad: true } );

		$( '.submit button' ).on( 'click', function () {
			if (
				$( 'select#woocommerce_allowed_countries' ).val() ===
					'specific' &&
				! $( '[name="woocommerce_specific_allowed_countries[]"]' ).val()
			) {
				if (
					window.confirm(
						woocommerce_settings_params.i18n_no_specific_countries_selected
					)
				) {
					return true;
				}
				return false;
			}
		} );

		$( '#settings-other-payment-methods' ).on( 'click', function ( e ) {
			if (
				typeof window.wcTracks.recordEvent === 'undefined' &&
				typeof window.wc.tracks.recordEvent === 'undefined'
			) {
				return;
			}

			var recordEvent =
				window.wc.tracks.recordEvent || window.wcTracks.recordEvent;

			var payment_methods = $.map(
				$(
					'td.wc_payment_gateways_wrapper tbody tr[data-gateway_id] '
				),
				function ( tr ) {
					return $( tr ).attr( 'data-gateway_id' );
				}
			);

			recordEvent( 'settings_payments_recommendations_other_options', {
				available_payment_methods: payment_methods,
			} );
		} );

		$( '.woocommerce-save-button.components-button' ).on( 'click', function ( e ) {
			if ( ! $( this ).attr( 'disabled' ) ) {
				$( this ).addClass( 'is-busy' );
			}
		} );

		/**
		 * Support conditionally displaying a settings field description when another element
		 * is set to a specific value.
		 *
		 * This logic is subject to change, and is not intended for use by other plugins.
		 * Note that we can't avoid jQuery here, because of our current dependence on Select2
		 * for various controls.
		 */
		document.querySelectorAll( 'body.woocommerce_page_wc-settings #mainform .conditional.description' ).forEach( description => {
			const $underObservation = $( description.dataset.dependsOn );
			const showIfEquals      = description.dataset.showIfEquals;

			if ( undefined === showIfEquals || $underObservation.length === 0 ) {
				return;
			}

			/**
			 * Set visibility of the description element according to whether its value
			 * matches that of showIfEquals.
			 */
			const changeAgent = () => {
				description.style.visibility = $underObservation.val() === showIfEquals ? 'visible' : 'hidden';
			};

			// Monitor future changes, and take action based on the current state.
			$underObservation.on( 'change', changeAgent );
			changeAgent();
		} );
	} );
} )( jQuery, woocommerce_settings_params, wp );
