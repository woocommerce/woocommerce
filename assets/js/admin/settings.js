jQuery( window ).load( function() {
	var $ = jQuery;

	// Countries
	$( 'select#woocommerce_allowed_countries, select#woocommerce_ship_to_countries' ).change( function() {
		if ( $( this ).val() === 'specific' ) {
			$( this ).parent().parent().next( 'tr' ).show();
		} else {
			$( this ).parent().parent().next( 'tr' ).hide();
		}
	}).change();

	// Color picker
	$( '.colorpick' ).iris( {
		change: function( event, ui ) {
			$( this ).css( { backgroundColor: ui.color.toString() });
		},
		hide: true,
		border: true
	} ).each( function() {
		$( this ).css( { backgroundColor: $( this ).val() });
	})
	.click( function() {
		$( '.iris-picker' ).hide();
		$( this ).closest( '.color_box, td' ).find( '.iris-picker' ).show();
	});

	$( 'body' ).click( function() {
		$( '.iris-picker' ).hide();
	});

	$( '.color_box, .colorpick' ).click( function( event ) {
	    event.stopPropagation();
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
				}
			} else {
				window.onbeforeunload = '';
			}
		});

		$( '.submit input' ).click( function() {
			window.onbeforeunload = '';
		});
	});

	// Sorting
	$( 'table.wc_gateways tbody, table.wc_shipping tbody' ).sortable({
		items:'tr',
		cursor:'move',
		axis:'y',
		handle: 'td',
		scrollSensitivity:40,
		helper:function( e,ui ) {
			ui.children().each( function() {
				$( this ).width( $( this ).width() );
			});
			
			ui.css( 'left', '0' );

			return ui;
		},
		start:function( event,ui ) {
			ui.item.css( 'background-color','#f6f6f6' );
		},
		stop:function( event,ui ) {
			ui.item.removeAttr( 'style' );
		}
	});

	// select2 selects
	$( 'select.select2_select' ).select2({
		width: '350px',
		minimumResultsForSearch: 5
	});

	$( 'select.select2_select_nostd' ).select2({
		width: '350px',
		minimumResultsForSearch: 5,
		allowClear: true
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
});