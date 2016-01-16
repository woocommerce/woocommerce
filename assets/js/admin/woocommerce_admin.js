/* global woocommerce_admin */

/**
 * WooCommerce Admin JS
 */
jQuery( function ( $ ) {

	// Field validation error tips
	$( document.body )
		.on( 'wc_add_error_tip', function( e, element, error_type ) {
			var offset = element.position();

			if ( element.parent().find( '.wc_error_tip' ).size() === 0 ) {
				element.after( '<div class="wc_error_tip ' + error_type + '">' + woocommerce_admin[error_type] + '</div>' );
				element.parent().find( '.wc_error_tip' )
					.css( 'left', offset.left + element.width() - ( element.width() / 2 ) - ( $( '.wc_error_tip' ).width() / 2 ) )
					.css( 'top', offset.top + element.height() )
					.fadeIn( '100' );
			}
		})
		.on( 'wc_remove_error_tip', function( e, element, error_type ) {
			element.parent().find( '.wc_error_tip.' + error_type ).remove();
		})
		.on( 'click', function() {
			$( '.wc_error_tip' ).fadeOut( '100', function() { $( this ).remove(); } );
		})
		.on( 'blur', '.wc_input_decimal[type=text], .wc_input_price[type=text], .wc_input_country_iso[type=text]', function() {
			$( '.wc_error_tip' ).fadeOut( '100', function() { $( this ).remove(); } );
		})
		.on( 'keyup change', '.wc_input_price[type=text]', function() {
			var value    = $( this ).val();
			var regex    = new RegExp( '[^\-0-9\%\\' + woocommerce_admin.mon_decimal_point + ']+', 'gi' );
			var newvalue = value.replace( regex, '' );

			if ( value !== newvalue ) {
				$( this ).val( newvalue );
				$( document.body ).triggerHandler( 'wc_add_error_tip', [ $( this ), 'i18n_mon_decimal_error' ] );
			} else {
				$( document.body ).triggerHandler( 'wc_remove_error_tip', [ $( this ), 'i18n_mon_decimal_error' ] );
			}
		})
		.on( 'keyup change', '.wc_input_decimal[type=text]', function() {
			var value    = $( this ).val();
			var regex    = new RegExp( '[^\-0-9\%\\' + woocommerce_admin.decimal_point + ']+', 'gi' );
			var newvalue = value.replace( regex, '' );

			if ( value !== newvalue ) {
				$( this ).val( newvalue );
				$( document.body ).triggerHandler( 'wc_add_error_tip', [ $( this ), 'i18n_decimal_error' ] );
			} else {
				$( document.body ).triggerHandler( 'wc_remove_error_tip', [ $( this ), 'i18n_decimal_error' ] );
			}
		})
		.on( 'keyup change', '.wc_input_country_iso[type=text]', function() {
			var value = $( this ).val();
			var regex = new RegExp( '^([A-Z])?([A-Z])$' );

			if ( ! regex.test( value ) ) {
				$( this ).val( '' );
				$( document.body ).triggerHandler( 'wc_add_error_tip', [ $( this ), 'i18n_country_iso_error' ] );
			} else {
				$( document.body ).triggerHandler( 'wc_remove_error_tip', [ $( this ), 'i18n_country_iso_error' ] );
			}
		})
		.on( 'keyup change', '#_sale_price.wc_input_price[type=text], .wc_input_price[name^=variable_sale_price]', function() {
			var sale_price_field = $( this ), regular_price_field;

			if( sale_price_field.attr( 'name' ).indexOf( 'variable' ) !== -1 ) {
				regular_price_field = sale_price_field.parents( '.variable_pricing' ).find( '.wc_input_price[name^=variable_regular_price]' );
			} else {
				regular_price_field = $( '#_regular_price' );
			}

			var sale_price    = parseFloat( window.accounting.unformat( sale_price_field.val(), woocommerce_admin.mon_decimal_point ) );
			var regular_price = parseFloat( window.accounting.unformat( regular_price_field.val(), woocommerce_admin.mon_decimal_point ) );

			if ( sale_price >= regular_price ) {
				$( document.body ).triggerHandler( 'wc_add_error_tip', [ $(this), 'i18_sale_less_than_regular_error' ] );
			} else {
				$( document.body ).triggerHandler( 'wc_remove_error_tip', [ $(this), 'i18_sale_less_than_regular_error' ] );
			}
		});

	// Tooltips
	var tiptip_args = {
		'attribute': 'data-tip',
		'fadeIn': 50,
		'fadeOut': 50,
		'delay': 200
	};
	$( '.tips, .help_tip, .woocommerce-help-tip' ).tipTip( tiptip_args );

	// Add tiptip to parent element for widefat tables
	$( '.parent-tips' ).each( function() {
		$( this ).closest( 'a, th' ).attr( 'data-tip', $( this ).data( 'tip' ) ).tipTip( tiptip_args ).css( 'cursor', 'help' );
	});

	// wc_input_table tables
	$( '.wc_input_table.sortable tbody' ).sortable({
		items: 'tr',
		cursor: 'move',
		axis: 'y',
		scrollSensitivity: 40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65,
		placeholder: 'wc-metabox-sortable-placeholder',
		start: function( event, ui ) {
			ui.item.css( 'background-color', '#f6f6f6' );
		},
		stop: function( event, ui ) {
			ui.item.removeAttr( 'style' );
		}
	});

	$( '.wc_input_table .remove_rows' ).click( function() {
		var $tbody = $( this ).closest( '.wc_input_table' ).find( 'tbody' );
		if ( $tbody.find( 'tr.current' ).size() > 0 ) {
			var $current = $tbody.find( 'tr.current' );
			$current.each( function() {
				$( this ).remove();
			});
		}
		return false;
	});

	var controlled = false;
	var shifted    = false;
	var hasFocus   = false;

	$( document.body ).bind( 'keyup keydown', function( e ) {
		shifted    = e.shiftKey;
		controlled = e.ctrlKey || e.metaKey;
	});

	$( '.wc_input_table' ).on( 'focus click', 'input', function( e ) {
		var $this_table = $( this ).closest( 'table, tbody' );
		var $this_row   = $( this ).closest( 'tr' );

		if ( ( e.type === 'focus' && hasFocus !== $this_row.index() ) || ( e.type === 'click' && $( this ).is( ':focus' ) ) ) {
			hasFocus = $this_row.index();

			if ( ! shifted && ! controlled ) {
				$( 'tr', $this_table ).removeClass( 'current' ).removeClass( 'last_selected' );
				$this_row.addClass( 'current' ).addClass( 'last_selected' );
			} else if ( shifted ) {
				$( 'tr', $this_table ).removeClass( 'current' );
				$this_row.addClass( 'selected_now' ).addClass( 'current' );

				if ( $( 'tr.last_selected', $this_table ).size() > 0 ) {
					if ( $this_row.index() > $( 'tr.last_selected', $this_table ).index() ) {
						$( 'tr', $this_table ).slice( $( 'tr.last_selected', $this_table ).index(), $this_row.index() ).addClass( 'current' );
					} else {
						$( 'tr', $this_table ).slice( $this_row.index(), $( 'tr.last_selected', $this_table ).index() + 1 ).addClass( 'current' );
					}
				}

				$( 'tr', $this_table ).removeClass( 'last_selected' );
				$this_row.addClass( 'last_selected' );
			} else {
				$( 'tr', $this_table ).removeClass( 'last_selected' );
				if ( controlled && $( this ).closest( 'tr' ).is( '.current' ) ) {
					$this_row.removeClass( 'current' );
				} else {
					$this_row.addClass( 'current' ).addClass( 'last_selected' );
				}
			}

			$( 'tr', $this_table ).removeClass( 'selected_now' );
		}
	}).on( 'blur', 'input', function() {
		hasFocus = false;
	});

	// Additional cost and Attribute term tables
	$( '.woocommerce_page_wc-settings .shippingrows tbody tr:even, table.attributes-table tbody tr:nth-child(odd)' ).addClass( 'alternate' );

	// Show order items on orders page
	$( document.body ).on( 'click', '.show_order_items', function() {
		$( this ).closest( 'td' ).find( 'table' ).toggle();
		return false;
	});

	// Select availability
	$( 'select.availability' ).change( function() {
		if ( $( this ).val() === 'all' ) {
			$( this ).closest( 'tr' ).next( 'tr' ).hide();
		} else {
			$( this ).closest( 'tr' ).next( 'tr' ).show();
		}
	}).change();

	// Hidden options
	$( '.hide_options_if_checked' ).each( function() {
		$( this ).find( 'input:eq(0)' ).change( function() {
			if ( $( this ).is( ':checked' ) ) {
				$( this ).closest( 'fieldset, tr' ).nextUntil( '.hide_options_if_checked, .show_options_if_checked', '.hidden_option' ).hide();
			} else {
				$( this ).closest( 'fieldset, tr' ).nextUntil( '.hide_options_if_checked, .show_options_if_checked', '.hidden_option' ).show();
			}
		}).change();
	});

	$( '.show_options_if_checked' ).each( function() {
		$( this ).find( 'input:eq(0)' ).change( function() {
			if ( $( this ).is( ':checked' ) ) {
				$( this ).closest( 'fieldset, tr' ).nextUntil( '.hide_options_if_checked, .show_options_if_checked', '.hidden_option' ).show();
			} else {
				$( this ).closest( 'fieldset, tr' ).nextUntil( '.hide_options_if_checked, .show_options_if_checked', '.hidden_option' ).hide();
			}
		}).change();
	});

	// Demo store notice
	$( 'input#woocommerce_demo_store' ).change(function() {
		if ( $( this ).is( ':checked' ) ) {
			$( '#woocommerce_demo_store_notice' ).closest( 'tr' ).show();
		} else {
			$( '#woocommerce_demo_store_notice' ).closest( 'tr' ).hide();
		}
	}).change();

	// Attribute term table
	$( 'table.attributes-table tbody tr:nth-child(odd)' ).addClass( 'alternate' );
});
