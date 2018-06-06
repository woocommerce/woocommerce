/* global woocommerce_settings_params */
( function( $ ) {

	// Sell Countries
	$( 'select#woocommerce_allowed_countries' ).change( function() {
		if ( 'specific' === $( this ).val() ) {
			$( this ).closest('tr').next( 'tr' ).hide();
			$( this ).closest('tr').next().next( 'tr' ).show();
		} else if ( 'all_except' === $( this ).val() ) {
			$( this ).closest('tr').next( 'tr' ).show();
			$( this ).closest('tr').next().next( 'tr' ).hide();
		} else {
			$( this ).closest('tr').next( 'tr' ).hide();
			$( this ).closest('tr').next().next( 'tr' ).hide();
		}
	}).change();

	// Ship Countries
	$( 'select#woocommerce_ship_to_countries' ).change( function() {
		if ( 'specific' === $( this ).val() ) {
			$( this ).closest('tr').next( 'tr' ).show();
		} else {
			$( this ).closest('tr').next( 'tr' ).hide();
		}
	}).change();

	// Stock management
	$( 'input#woocommerce_manage_stock' ).change( function() {
		if ( $( this ).is(':checked') ) {
			$( this ).closest('tbody').find( '.manage_stock_field' ).closest( 'tr' ).show();
		} else {
			$( this ).closest('tbody').find( '.manage_stock_field' ).closest( 'tr' ).hide();
		}
	}).change();

	// Color picker
	$( '.colorpick' )

		.iris({
			change: function( event, ui ) {
				$( this ).parent().find( '.colorpickpreview' ).css({ backgroundColor: ui.color.toString() });
			},
			hide: true,
			border: true
		})

		.on( 'click focus', function( event ) {
			event.stopPropagation();
			$( '.iris-picker' ).hide();
			$( this ).closest( 'td' ).find( '.iris-picker' ).show();
			$( this ).data( 'original-value', $( this ).val() );
		})

		.on( 'change', function() {
			if ( $( this ).is( '.iris-error' ) ) {
				var original_value = $( this ).data( 'original-value' );

				if ( original_value.match( /^\#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/ ) ) {
					$( this ).val( $( this ).data( 'original-value' ) ).change();
				} else {
					$( this ).val( '' ).change();
				}
			}
		});

	$( 'body' ).on( 'click', function() {
		$( '.iris-picker' ).hide();
	});

	// Edit prompt
	$( function() {
		var changed = false;

		$( 'input, textarea, select, checkbox' ).change( function() {
			changed = true;
		});

		$( '.woo-nav-tab-wrapper a' ).click( function() {
			if ( changed ) {
				window.onbeforeunload = function() {
				    return woocommerce_settings_params.i18n_nav_warning;
				};
			} else {
				window.onbeforeunload = '';
			}
		});

		$( '.submit :input' ).click( function() {
			window.onbeforeunload = '';
		});
	});

	// Sorting
	$( 'table.wc_gateways tbody, table.wc_shipping tbody' ).sortable({
		items: 'tr',
		cursor: 'move',
		axis: 'y',
		handle: 'td.sort',
		scrollSensitivity: 40,
		helper: function( event, ui ) {
			ui.children().each( function() {
				$( this ).width( $( this ).width() );
			});
			ui.css( 'left', '0' );
			return ui;
		},
		start: function( event, ui ) {
			ui.item.css( 'background-color', '#f6f6f6' );
		},
		stop: function( event, ui ) {
			ui.item.removeAttr( 'style' );
		}
	});

	// Select all/none
	$( '.woocommerce' ).on( 'click', '.select_all', function() {
		$( this ).closest( 'td' ).find( 'select option' ).attr( 'selected', 'selected' );
		$( this ).closest( 'td' ).find( 'select' ).trigger( 'change' );
		return false;
	});

	$( '.woocommerce' ).on( 'click', '.select_none', function() {
		$( this ).closest( 'td' ).find( 'select option' ).removeAttr( 'selected' );
		$( this ).closest( 'td' ).find( 'select' ).trigger( 'change' );
		return false;
	});
})( jQuery );
