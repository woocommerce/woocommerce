/* global woocommerce_admin */

/**
 * WooCommerce Admin JS
 */
jQuery( function ( $ ) {

	// Price input validation
	$('body').on( 'blur', '.wc_input_decimal[type=text], .wc_input_price[type=text], .wc_input_country_iso[type=text]', function() {
		$('.wc_error_tip').fadeOut('100', function(){ $(this).remove(); } );
		return this;
	});

	$('body').on('keyup change', '.wc_input_price[type=text]', function(){
		var value		= $(this).val();
		var regex		= new RegExp( "[^\-0-9\%.\\" + woocommerce_admin.mon_decimal_point + "]+", "gi" );
		var newvalue = value.replace( regex, '' );

		if ( value !== newvalue ) {
			$(this).val( newvalue );
			if ( $(this).parent().find('.wc_error_tip').size() == 0 ) {
				var offset = $(this).position();
				$(this).after( '<div class="wc_error_tip">' + woocommerce_admin.i18n_mon_decimal_error + '</div>' );
				$('.wc_error_tip')
					.css('left', offset.left + $(this).width() - ( $(this).width() / 2 ) - ( $('.wc_error_tip').width() / 2 ) )
					.css('top', offset.top + $(this).height() )
					.fadeIn('100');
			}
		}
		return this;
	});

	$('body').on('keyup change', '.wc_input_decimal[type=text]', function(){
		var value    = $(this).val();
		var regex    = new RegExp( "[^\-0-9\%.\\" + woocommerce_admin.decimal_point + "]+", "gi" );
		var newvalue = value.replace( regex, '' );

		if ( value !== newvalue ) {
			$(this).val( newvalue );
			if ( $(this).parent().find('.wc_error_tip').size() === 0 ) {
				var offset = $(this).position();
				$(this).after( '<div class="wc_error_tip">' + woocommerce_admin.i18n_decimal_error + '</div>' );
				$('.wc_error_tip')
					.css('left', offset.left + $(this).width() - ( $(this).width() / 2 ) - ( $('.wc_error_tip').width() / 2 ) )
					.css('top', offset.top + $(this).height() )
					.fadeIn('100');
			}
		}
		return this;
	});

	$('body').on( 'keyup', '#_sale_price.wc_input_price[type=text], .wc_input_price[name^=variable_sale_price]', function(){
		var sale_price_field = $(this);

		if( sale_price_field.attr('name').indexOf('variable') !== -1 ) {
			var regular_price_field = sale_price_field.parents('.variable_pricing').find('.wc_input_price[name^=variable_regular_price]');
		} else {
			var regular_price_field = $('#_regular_price');
		}

		var sale_price    = parseFloat( accounting.unformat( sale_price_field.val(), woocommerce_admin.mon_decimal_point ) );
		var regular_price = parseFloat( accounting.unformat( regular_price_field.val(), woocommerce_admin.mon_decimal_point ) );

		if( sale_price >= regular_price ) {
			if ( $(this).parent().find('.wc_error_tip').size() === 0 ) {
				var offset = $(this).position();
				$(this).after( '<div class="wc_error_tip">' + woocommerce_admin.i18_sale_less_than_regular_error + '</div>' );
				$('.wc_error_tip')
					.css('left', offset.left + $(this).width() - ( $(this).width() / 2 ) - ( $('.wc_error_tip').width() / 2 ) )
					.css('top', offset.top + $(this).height() )
					.fadeIn('100');
			}
		} else {
			$('.wc_error_tip').fadeOut('100', function(){ $(this).remove(); } );
		}
		return this;
	});
	$('body').on( 'change', '#_sale_price.wc_input_price[type=text], .wc_input_price[name^=variable_sale_price]', function(){
		var sale_price_field = $(this);

		if( sale_price_field.attr('name').indexOf('variable') !== -1 ) {
			var regular_price_field = sale_price_field.parents('.variable_pricing').find('.wc_input_price[name^=variable_regular_price]');
		} else {
			var regular_price_field = $('#_regular_price');
		}

		var sale_price    = parseFloat( accounting.unformat( sale_price_field.val(), woocommerce_admin.mon_decimal_point ) );
		var regular_price = parseFloat( accounting.unformat( regular_price_field.val(), woocommerce_admin.mon_decimal_point ) );

		if( sale_price >= regular_price ) {
			sale_price_field.val( regular_price_field.val() );
		} else {
			$('.wc_error_tip').fadeOut('100', function(){ $(this).remove(); } );
		}
		return this;
	});

	$('body').on('keyup change', '.wc_input_country_iso[type=text]', function(){
		var value = $(this).val();
		var regex = new RegExp( '^([A-Z])?([A-Z])$' );

		if ( ! regex.test( value ) ) {
			$(this).val( '' );
			if ( $(this).parent().find('.wc_error_tip').size() === 0 ) {
				var offset = $(this).position();
				$(this).after( '<div class="wc_error_tip">' + woocommerce_admin.i18n_country_iso_error + '</div>' );
				$('.wc_error_tip')
					.css('left', offset.left + $(this).width() - ( $(this).width() / 2 ) - ( $('.wc_error_tip').width() / 2 ) )
					.css('top', offset.top + $(this).height() )
					.fadeIn('100');
			}
		}
		return this;
	});

	$("body").click(function(){
		$('.wc_error_tip').fadeOut('100', function(){ $(this).remove(); } );
	});

	// Tooltips
	var tiptip_args = {
		'attribute' : 'data-tip',
		'fadeIn' : 50,
		'fadeOut' : 50,
		'delay' : 200
	};
	$(".tips, .help_tip").tipTip( tiptip_args );

	// Add tiptip to parent element for widefat tables
	$(".parent-tips").each(function(){
		$(this).closest( 'a, th' ).attr( 'data-tip', $(this).data( 'tip' ) ).tipTip( tiptip_args ).css( 'cursor', 'help' );
	});

	// wc_input_table tables
	$('.wc_input_table.sortable tbody').sortable({
		items:'tr',
		cursor:'move',
		axis:'y',
		scrollSensitivity:40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65,
		placeholder: 'wc-metabox-sortable-placeholder',
		start:function(event,ui){
			ui.item.css('background-color','#f6f6f6');
		},
		stop:function(event,ui){
			ui.item.removeAttr('style');
		}
	});

	$('.wc_input_table .remove_rows').click(function() {
		var $tbody = $(this).closest('.wc_input_table').find('tbody');
		if ( $tbody.find('tr.current').size() > 0 ) {
			$current = $tbody.find('tr.current');

			$current.each(function(){
				$(this).remove();
			});
		}
		return false;
	});

	var controlled = false;
	var shifted = false;
	var hasFocus = false;

	$(document).bind('keyup keydown', function(e){ shifted = e.shiftKey; controlled = e.ctrlKey || e.metaKey } );

	$('.wc_input_table').on( 'focus click', 'input', function( e ) {

		$this_table = $(this).closest('table');
		$this_row   = $(this).closest('tr');

		if ( ( e.type == 'focus' && hasFocus != $this_row.index() ) || ( e.type == 'click' && $(this).is(':focus') ) ) {

			hasFocus = $this_row.index();

			if ( ! shifted && ! controlled ) {
				$('tr', $this_table).removeClass('current').removeClass('last_selected');
				$this_row.addClass('current').addClass('last_selected');
			} else if ( shifted ) {
				$('tr', $this_table).removeClass('current');
				$this_row.addClass('selected_now').addClass('current');

				if ( $('tr.last_selected', $this_table).size() > 0 ) {
					if ( $this_row.index() > $('tr.last_selected, $this_table').index() ) {
						$('tr', $this_table).slice( $('tr.last_selected', $this_table).index(), $this_row.index() ).addClass('current');
					} else {
						$('tr', $this_table).slice( $this_row.index(), $('tr.last_selected', $this_table).index() + 1 ).addClass('current');
					}
				}

				$('tr', $this_table).removeClass('last_selected');
				$this_row.addClass('last_selected');
			} else {
				$('tr', $this_table).removeClass('last_selected');
				if ( controlled && $(this).closest('tr').is('.current') ) {
					$this_row.removeClass('current');
				} else {
					$this_row.addClass('current').addClass('last_selected');
				}
			}

			$('tr', $this_table).removeClass('selected_now');

		}
	}).on( 'blur', 'input', function( e ) {
		hasFocus = false;
	});

	// Additional cost tables
	$( '.woocommerce_page_wc-settings .shippingrows tbody tr:even' ).addClass( 'alternate' );

	// Availability inputs
	$('select.availability').change(function(){
		if ( $(this).val() == "all" ) {
			$(this).closest('tr').next('tr').hide();
		} else {
			$(this).closest('tr').next('tr').show();
		}
	}).change();

	// Show order items on orders page
	$('body').on( 'click', '.show_order_items', function() {
		$(this).closest('td').find('table').toggle();
		return false;
	});

	// Hidden options
	$('.hide_options_if_checked').each(function(){

		$(this).find('input:eq(0)').change(function() {

			if ($(this).is(':checked')) {
				$(this).closest('fieldset, tr').nextUntil( '.hide_options_if_checked, .show_options_if_checked', '.hidden_option').hide();
			} else {
				$(this).closest('fieldset, tr').nextUntil( '.hide_options_if_checked, .show_options_if_checked', '.hidden_option').show();
			}

		}).change();

	});

	$('.show_options_if_checked').each(function(){

		$(this).find('input:eq(0)').change(function() {

			if ($(this).is(':checked')) {
				$(this).closest('fieldset, tr').nextUntil( '.hide_options_if_checked, .show_options_if_checked', '.hidden_option').show();
			} else {
				$(this).closest('fieldset, tr').nextUntil( '.hide_options_if_checked, .show_options_if_checked', '.hidden_option').hide();
			}

		}).change();

	});

	$('input#woocommerce_demo_store').change(function() {
		if ($(this).is(':checked')) {
			$('#woocommerce_demo_store_notice').closest('tr').show();
		} else {
			$('#woocommerce_demo_store_notice').closest('tr').hide();
		}
	}).change();

	// Attribute term table
	$( 'table.attributes-table tbody tr:nth-child(odd)' ).addClass( 'alternate' );

});
