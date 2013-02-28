jQuery( function($){

	// Scroll to first checked category - https://github.com/scribu/wp-category-checklist-tree/blob/d1c3c1f449e1144542efa17dde84a9f52ade1739/category-checklist-tree.php
	$(function(){
		$('[id$="-all"] > ul.categorychecklist').each(function() {
			var $list = $(this);
			var $firstChecked = $list.find(':checked').first();

			if ( !$firstChecked.length )
				return;

			var pos_first = $list.find(':checkbox').position().top;
			var pos_checked = $firstChecked.position().top;

			$list.closest('.tabs-panel').scrollTop(pos_checked - pos_first + 5);
		});
	});

	// Prevent enter submitting post form
	$("#upsell_product_data").bind("keypress", function(e) {
		if (e.keyCode == 13) return false;
	});

	// Type box
	$('.type_box').appendTo( '#woocommerce-product-data h3.hndle span' );

	$(function(){
		// Prevent inputs in meta box headings opening/closing contents
		$('#woocommerce-product-data h3.hndle').unbind('click.postboxes');

		jQuery('#woocommerce-product-data').on('click', 'h3.hndle', function(event){

			// If the user clicks on some form input inside the h3 the box should not be toggled
			if ( $(event.target).filter('input, option, label, select').length )
				return;

			$('#woocommerce-product-data').toggleClass('closed');
		});
	});

	// Order emails
	$('#order-emails a.show-order-emails').click(function () {
		if ($('#order-emails-select').is(":hidden")) {
			$('#order-emails-select').slideDown('fast');
			$(this).hide();
		}
		return false;
	});

	$('#order-emails a.hide-order-emails').click(function () {
		$('input[name="order_email[]"]').each( function() { $(this).attr('checked', false) } );

		if ($('#order-emails-select').is(":visible")) {
			$('#order-emails-select').slideUp('fast');
			$('#order-emails a.show-order-emails').show();
		}
		return false;
	});

	// Catalog Visibility
	$('#catalog-visibility .edit-catalog-visibility').click(function () {
		if ($('#catalog-visibility-select').is(":hidden")) {
			$('#catalog-visibility-select').slideDown('fast');
			$(this).hide();
		}
		return false;
	});
	$('#catalog-visibility .save-post-visibility').click(function () {
		$('#catalog-visibility-select').slideUp('fast');
		$('#catalog-visibility .edit-catalog-visibility').show();

		var value = $('input[name=_visibility]:checked').val();
		var label = $('input[name=_visibility]:checked').attr('data-label');

		if ( $('input[name=_featured]').is(':checked') ) {
			label = label + ', ' + woocommerce_writepanel_params.featured_label
			$('input[name=_featured]').attr('checked', 'checked');
		}

		$('#catalog-visibility-display').text( label );
		return false;
	});
	$('#catalog-visibility .cancel-post-visibility').click(function () {
		$('#catalog-visibility-select').slideUp('fast');
		$('#catalog-visibility .edit-catalog-visibility').show();

		var current_visibility = $('#current_visibility').val();
		var current_featured = $('#current_featured').val();

		$('input[name=_visibility]').removeAttr('checked');
		$('input[name=_visibility][value=' + current_visibility + ']').attr('checked', 'checked');

		var label = $('input[name=_visibility]:checked').attr('data-label');

		if ( current_featured == 'yes' ) {
			label = label + ', ' + woocommerce_writepanel_params.featured_label
			$('input[name=_featured]').attr('checked', 'checked');
		} else {
			$('input[name=_featured]').removeAttr('checked');
		}

		$('#catalog-visibility-display').text( label );
		return false;
	});

	// TABS
	$('ul.wc-tabs').show();
	$('div.panel-wrap').each(function(){
		$(this).find('div.panel:not(:first)').hide();
	});
	$('ul.wc-tabs a').click(function(){
		var panel_wrap =  $(this).closest('div.panel-wrap');
		$('ul.wc-tabs li', panel_wrap).removeClass('active');
		$(this).parent().addClass('active');
		$('div.panel', panel_wrap).hide();
		$( $(this).attr('href') ).show();
		return false;
	});

	// Chosen selects
	jQuery("select.chosen_select").chosen();

	jQuery("select.chosen_select_nostd").chosen({
		allow_single_deselect: 'true'
	});

	// Ajax Chosen Product Selectors
	jQuery("select.ajax_chosen_select_products").ajaxChosen({
	    method: 	'GET',
	    url: 		woocommerce_writepanel_params.ajax_url,
	    dataType: 	'json',
	    afterTypeDelay: 100,
	    data:		{
	    	action: 		'woocommerce_json_search_products',
			security: 		woocommerce_writepanel_params.search_products_nonce
	    }
	}, function (data) {

		var terms = {};

	    $.each(data, function (i, val) {
	        terms[i] = val;
	    });

	    return terms;
	});

	jQuery("select.ajax_chosen_select_products_and_variations").ajaxChosen({
	    method: 	'GET',
	    url: 		woocommerce_writepanel_params.ajax_url,
	    dataType: 	'json',
	    afterTypeDelay: 100,
	    data:		{
	    	action: 		'woocommerce_json_search_products_and_variations',
			security: 		woocommerce_writepanel_params.search_products_nonce
	    }
	}, function (data) {

		var terms = {};

	    $.each(data, function (i, val) {
	        terms[i] = val;
	    });

	    return terms;
	});

	// ORDERS
	jQuery('#woocommerce-order-actions input, #woocommerce-order-actions a').click(function(){
		window.onbeforeunload = '';
	});

	$('a.edit_address').click(function(event){
		$(this).hide();
		$(this).closest('.order_data_column').find('div.address').hide();
		$(this).closest('.order_data_column').find('div.edit_address').show();
		event.preventDefault();
	});

	$('#order_items_list').on( 'init_row', 'tr.item', function() {
		var $row = $(this);
		var $qty = $row.find('input.quantity');
		var qty = $qty.val();

		var line_subtotal 	= $row.find('input.line_subtotal').val();
		var line_total 		= $row.find('input.line_total').val();
		var line_tax 		= $row.find('input.line_tax').val();
		var line_subtotal_tax = $row.find('input.line_subtotal_tax').val();

		if ( qty ) {
			unit_subtotal 		= accounting.toFixed( ( line_subtotal / qty ), 2 );
			unit_subtotal_tax 	= accounting.toFixed( ( line_subtotal_tax / qty ), 2 );
			unit_total			= accounting.toFixed( ( line_total / qty ), 2 );
			unit_total_tax		= accounting.toFixed( ( line_tax / qty ), 2 );
		} else {
			unit_subtotal = unit_subtotal_tax = unit_total = unit_total_tax = 0;
		}

		$qty.attr( 'data-o_qty', qty );
		$row.attr( 'data-unit_subtotal', unit_subtotal );
		$row.attr( 'data-unit_subtotal_tax', unit_subtotal_tax );
		$row.attr( 'data-unit_total', unit_total );
		$row.attr( 'data-unit_total_tax', unit_total_tax );
	});

	// When the page is loaded, store the unit costs
	$('#order_items_list tr.item').each( function() {
		$(this).trigger('init_row');
	} );

	// When the qty is changed, increase or decrease costs
	$('#order_items_list').on( 'change', 'input.quantity', function() {
		var $row = $(this).closest('tr.item');
		var qty = $(this).val();

		var unit_subtotal 		= $row.attr('data-unit_subtotal');
		var unit_subtotal_tax 	= $row.attr('data-unit_subtotal_tax');
		var unit_total 			= $row.attr('data-unit_total');
		var unit_total_tax = $row.attr('data-unit_total_tax');
		var o_qty 				= $(this).attr('data-o_qty');

		var subtotal = accounting.formatNumber( unit_subtotal * qty, 2, '' );
		var tax = accounting.formatNumber( unit_subtotal_tax * qty, 2, '' );
		var total = accounting.formatNumber( unit_total * qty, 2, '' );
		var total_tax = accounting.formatNumber( unit_total_tax * qty, 2, '' );

		$row.find('input.line_subtotal').val( subtotal );
		$row.find('input.line_total').val( total );
		$row.find('input.line_subtotal_tax').val( tax );
		$row.find('input.line_tax').val( total_tax );
	});

	// When subtotal is changed, update the unit costs
	$('#order_items_list').on( 'change', 'input.line_subtotal', function() {
		var $row = $(this).closest('tr.item');
		var $qty = $row.find('input.quantity');
		var qty = $qty.val();
		var value = ( qty ) ? accounting.toFixed( ( $(this).val() / qty ), 2 ) : 0;

		$row.attr( 'data-unit_subtotal', value );
	});

	// When total is changed, update the unit costs + discount amount
	$('#order_items_list').on( 'change', 'input.line_total', function() {
		var $row = $(this).closest('tr.item');
		var $qty = $row.find('input.quantity');
		var qty = $qty.val();
		var value = ( qty ) ? accounting.toFixed( ( $(this).val() / qty ), 2 ) : 0;

		$row.attr( 'data-unit_total', value );
	});

	// When total is changed, update the unit costs + discount amount
	$('#order_items_list').on( 'change', 'input.line_subtotal_tax', function() {
		var $row = $(this).closest('tr.item');
		var $qty = $row.find('input.quantity');
		var qty = $qty.val();
		var value = ( qty ) ? accounting.toFixed( ( $(this).val() / qty ), 2 ) : 0;

		$row.attr( 'data-unit_subtotal_tax', value );
	});

	// When total is changed, update the unit costs + discount amount
	$('#order_items_list').on( 'change', 'input.line_tax', function() {
		var $row = $(this).closest('tr.item');
		var $qty = $row.find('input.quantity');
		var qty = $qty.val();
		var value = ( qty ) ? accounting.toFixed( ( $(this).val() / qty ), 2 ) : 0;

		$row.attr( 'data-unit_total_tax', value );
	});

	// Display a total for taxes
	$('#woocommerce-order-totals').on( 'change', '#_order_tax, #_order_shipping_tax, #_cart_discount, #_order_discount', function() {

		var $this =  $(this);
		var fields = $this.closest('.totals').find('input');
		var total = 0;

		fields.each(function(){
			if ( $(this).val() )
				total = total + parseFloat( $(this).val() );
		});

		var formatted_total = accounting.formatMoney( total, {
			symbol 		: woocommerce_writepanel_params.currency_format_symbol,
			decimal 	: woocommerce_writepanel_params.currency_format_decimal_sep,
			thousand	: woocommerce_writepanel_params.currency_format_thousand_sep,
			precision 	: woocommerce_writepanel_params.currency_format_num_decimals,
			format		: woocommerce_writepanel_params.currency_format
		} );

		$this.closest('.totals_group').find('span.inline_total').text( formatted_total );
	} );

	$('span.inline_total').closest('.totals_group').find('input').change();

	// Calculate totals
	$('button.calc_line_taxes').click(function(){
		// Block write panel
		$('.woocommerce_order_items_wrapper').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

		var answer = confirm(woocommerce_writepanel_params.calc_line_taxes);

		if (answer) {

			var $items = $('#order_items_list').find('tr.item, tr.fee');

			var country = $('#_shipping_country').val();
			if (country) {
				var state = $('#_shipping_state').val();
				var postcode = $('#_shipping_postcode').val();
				var city = $('#_shipping_city').val();
			} else {
				country = $('#_billing_country').val();
				var state = $('#_billing_state').val();
				var postcode = $('#_billing_postcode').val();
				var city = $('#_billing_city').val();
			}

			// Get items and values
			var calculate_items = {};

			$items.each( function() {

				var $row = $(this);

				var item_id 		= $row.find('input.order_item_id').val();
				var line_subtotal	= $row.find('input.line_subtotal').val();
				var line_total		= $row.find('input.line_total').val();
				var tax_class		= $row.find('select.tax_class').val();

				calculate_items[ item_id ] = {};
				calculate_items[ item_id ].line_subtotal = line_subtotal;
				calculate_items[ item_id ].line_total = line_total;
				calculate_items[ item_id ].tax_class = tax_class;
			} );

			var data = {
				action: 		'woocommerce_calc_line_taxes',
				order_id: 		woocommerce_writepanel_params.post_id,
				items:			calculate_items,
				shipping:		accounting.unformat( $('#_order_shipping').val() ),
				country:		country,
				state:			state,
				postcode:		postcode,
				city:			city,
				security: 		woocommerce_writepanel_params.calc_totals_nonce
			};

			$.post( woocommerce_writepanel_params.ajax_url, data, function( response ) {

				if ( response ) {

					$items.each( function() {
						var $row = $(this);
						var item_id = $row.find('input.order_item_id').val();

						$row.find('input.line_tax').val( response['item_taxes'][ item_id ]['line_tax'] ).change();
						$row.find('input.line_subtotal_tax').val( response['item_taxes'][ item_id ]['line_subtotal_tax'] ).change();
						$('#tax_rows').empty().append( response['tax_row_html'] );
					} );

					$('#_order_tax').val( response['item_tax'] ).change();
					$('#_order_shipping_tax').val( response['shipping_tax'] ).change();

				}

				$('.woocommerce_order_items_wrapper').unblock();
			});

		} else {
			$('.woocommerce_order_items_wrapper').unblock();
		}
		return false;
	}).hover(function() {
		$('#order_items_list input.line_subtotal_tax, #order_items_list input.line_tax, #_order_shipping_tax, #_order_tax, .tax_rows_group').css('background-color', '#e3d2dd');
	}, function() {
		$('#order_items_list input.line_subtotal_tax, #order_items_list input.line_tax, #_order_shipping_tax, #_order_tax, .tax_rows_group').css('background-color', '');
	});


	$('button.calc_totals').click( function(){
		// Block write panel
		$('#woocommerce-order-totals').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

		var answer = confirm(woocommerce_writepanel_params.calc_totals);

		if (answer) {

			// Get row totals
			var line_subtotals 		= 0;
			var line_subtotal_taxes = 0;
			var line_totals 		= 0;
			var cart_discount 		= 0;
			var cart_tax 			= 0;
			var order_shipping 		= $('#_order_shipping').val() || '0';
			var order_shipping_tax 	= $('#_order_shipping_tax').val() || '0';
			var order_discount		= $('#_order_discount').val() || '0';

			order_shipping = accounting.unformat( order_shipping.replace(',', '.') );
			order_shipping_tax = accounting.unformat( order_shipping_tax.replace(',', '.') );
			order_discount = accounting.unformat( order_discount.replace(',', '.') );

			$('#order_items_list tr.item').each(function(){

				var line_subtotal 		= $(this).find('input.line_subtotal').val() || '0';
				var line_subtotal_tax 	= $(this).find('input.line_subtotal_tax').val() || '0';
				var line_total 			= $(this).find('input.line_total').val() || '0';
				var line_tax 			= $(this).find('input.line_tax').val() || '0';

				line_subtotal = accounting.unformat( line_subtotal.replace(',', '.') );
				line_subtotal_tax = accounting.unformat( line_subtotal_tax.replace(',', '.') );
				line_total = accounting.unformat( line_total.replace(',', '.') );
				line_tax = accounting.unformat( line_tax.replace(',', '.') );

				line_subtotals = line_subtotals + line_subtotal;
				line_subtotal_taxes = line_subtotal_taxes + line_subtotal_tax;
				line_totals = line_totals + line_total;

				if ( woocommerce_writepanel_params.round_at_subtotal=='no' ) {
					line_tax = accounting.toFixed( line_tax, 2 );
				}

				cart_tax = cart_tax + parseFloat( line_tax );
			});

			// Tax
			if (woocommerce_writepanel_params.round_at_subtotal=='yes') {
				cart_tax = accounting.toFixed( cart_tax, 2 );
			}

			// Cart discount
			var cart_discount = ( line_subtotals + line_subtotal_taxes ) - ( line_totals + cart_tax );
			if ( cart_discount < 0 ) cart_discount = 0;
			cart_discount = accounting.toFixed( cart_discount, 2 );

			$('#order_items_list tr.fee').each(function(){
				var line_total 			= $(this).find('input.line_total').val() || '0';;
				var line_tax 			= $(this).find('input.line_tax').val() || '0';;

				line_total = accounting.unformat( line_total.replace(',', '.') );
				line_tax = accounting.unformat( line_tax.replace(',', '.') );

				line_totals = line_totals + line_total;

				if ( woocommerce_writepanel_params.round_at_subtotal=='no' ) {
					line_tax = accounting.toFixed( line_tax, 2 );
				}

				cart_tax = cart_tax + parseFloat( line_tax );
			});

			// Tax
			if (woocommerce_writepanel_params.round_at_subtotal=='yes') {
				cart_tax = parseFloat( accounting.toFixed( cart_tax, 2 ) );
			}

			// Total
			var order_total = line_totals + cart_tax + order_shipping + order_shipping_tax - order_discount;
			order_total = accounting.toFixed( order_total, 2 );
			cart_tax = accounting.toFixed( cart_tax, 2 );

			// Set fields
			$('#_cart_discount').val( cart_discount ).change();
			$('#_order_tax').val( cart_tax ).change();
			$('#_order_total').val( order_total ).change();

			$('#woocommerce-order-totals').unblock();

		} else {
			$('#woocommerce-order-totals').unblock();
		}
		return false;
	}).hover(function() {
		$('#woocommerce-order-totals .calculated').css('background-color', '#e3d2dd');
	}, function() {
		$('#woocommerce-order-totals .calculated').css('background-color', '');
	});

	// Add a line item
	$('#woocommerce-order-items button.add_order_item').click(function(){

		var add_item_ids = $('select#add_item_id').val();

		if ( add_item_ids ) {

			count = add_item_ids.length;

			$('table.woocommerce_order_items').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			$.each( add_item_ids, function( index, value ) {

				var data = {
					action: 		'woocommerce_add_order_item',
					item_to_add: 	value,
					order_id:		woocommerce_writepanel_params.post_id,
					security: 		woocommerce_writepanel_params.order_item_nonce
				};

				$.post( woocommerce_writepanel_params.ajax_url, data, function( response ) {

					$('table.woocommerce_order_items tbody#order_items_list').append( response );

					if (!--count) {
						$('select#add_item_id, #add_item_id_chzn .chzn-choices').css('border-color', '').val('');
					    jQuery(".tips").tipTip({
					    	'attribute' : 'data-tip',
					    	'fadeIn' : 50,
					    	'fadeOut' : 50,
					    	'delay' : 200
					    });
					    $('select#add_item_id').trigger("liszt:updated");
					    $('table.woocommerce_order_items').unblock();
					}

					$('#order_items_list tr.new_row').trigger('init_row').removeClass('new_row');
				});

			});

		} else {
			$('select#add_item_id, #add_item_id_chzn .chzn-choices').css('border-color', 'red');
		}
		return false;
	});

	// Add a fee
	$('#woocommerce-order-items button.add_order_fee').click(function(){

		$('table.woocommerce_order_items').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

		var data = {
			action: 		'woocommerce_add_order_fee',
			order_id:		woocommerce_writepanel_params.post_id,
			security: 		woocommerce_writepanel_params.order_item_nonce
		};

		$.post( woocommerce_writepanel_params.ajax_url, data, function( response ) {
			$('table.woocommerce_order_items tbody#order_items_list').append( response );
			$('table.woocommerce_order_items').unblock();
		});
		return false;
	});

	// Add some meta to a line item
	$('#order_items_list').on('click', 'button.add_order_item_meta', function(){

		var $button = $(this);
		var $item = $button.closest('tr.item');

		var data = {
			order_item_id: 	$item.attr( 'data-order_item_id' ),
			action: 	'woocommerce_add_order_item_meta',
			security: 	woocommerce_writepanel_params.order_item_nonce
		};

		$('table.woocommerce_order_items').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

		$.ajax( {
			url: woocommerce_writepanel_params.ajax_url,
			data: data,
			type: 'POST',
			success: function( response ) {
				$item.find('tbody.meta_items').append( response );
				$('table.woocommerce_order_items').unblock();
			}
		} );

		return false;
	});

	$('#order_items_list').on('click', 'button.remove_order_item_meta', function(){
		var answer = confirm( woocommerce_writepanel_params.remove_item_meta )
		if ( answer ) {
			var $row = $(this).closest('tr');

			var data = {
				meta_id: 			$row.attr( 'data-meta_id' ),
				action: 			'woocommerce_remove_order_item_meta',
				security: 			woocommerce_writepanel_params.order_item_nonce
			};

			$('table.woocommerce_order_items').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			$.ajax( {
				url: woocommerce_writepanel_params.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					$row.hide();
					$('table.woocommerce_order_items').unblock();
				}
			} );
		}
		return false;
	});

	// Bulk actions for line items
	$('#woocommerce-order-items').on( 'click', 'input.check-column', function() {
		if ( $(this).is(':checked') )
			$('#woocommerce-order-items').find('.check-column input').attr('checked', 'checked');
		else
			$('#woocommerce-order-items').find('.check-column input').removeAttr('checked');
	} );

	$('#woocommerce-order-items').on( 'click', '.do_bulk_action', function() {

		var action = $(this).closest('.bulk_actions').find('select').val();
		var selected_rows = $('#woocommerce-order-items').find('.check-column input:checked');
		var item_ids = [];

		$(selected_rows).each( function() {

			var $item = $(this).closest('tr.item, tr.fee');

			item_ids.push( $item.attr( 'data-order_item_id' ) );

		} );

		if ( item_ids.length == 0 ) {
			alert( woocommerce_writepanel_params.i18n_select_items );
			return;
		}

		if ( action == 'delete' ) {

			var answer = confirm( woocommerce_writepanel_params.remove_item_notice );

			if ( answer ) {

				$('table.woocommerce_order_items').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

				var data = {
					order_item_ids: 	item_ids,
					action: 			'woocommerce_remove_order_item',
					security: 			woocommerce_writepanel_params.order_item_nonce
				};

				$.ajax( {
					url: woocommerce_writepanel_params.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						$(selected_rows).each( function() {
							$(this).closest('tr.item, tr.fee').remove();
						} );
						$('table.woocommerce_order_items').unblock();
					}
				} );

			}

		} else if ( action == 'reduce_stock' ) {

			$('table.woocommerce_order_items').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			var quantities = {};

			$(selected_rows).each( function() {

				var $item = $(this).closest('tr.item, tr.fee');
				var $qty  = $item.find('input.quantity');

				quantities[ $item.attr( 'data-order_item_id' ) ] = $qty.val();
			} );

			var data = {
				order_id:			woocommerce_writepanel_params.post_id,
				order_item_ids: 	item_ids,
				order_item_qty: 	quantities,
				action: 			'woocommerce_reduce_order_item_stock',
				security: 			woocommerce_writepanel_params.order_item_nonce
			};

			$.ajax( {
				url: woocommerce_writepanel_params.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					alert( response );
					$('table.woocommerce_order_items').unblock();
				}
			} );

		} else if ( action == 'increase_stock' ) {

			$('table.woocommerce_order_items').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			var quantities = {};

			$(selected_rows).each( function() {

				var $item = $(this).closest('tr.item, tr.fee');
				var $qty  = $item.find('input.quantity');

				quantities[ $item.attr( 'data-order_item_id' ) ] = $qty.val();
			} );

			var data = {
				order_id:			woocommerce_writepanel_params.post_id,
				order_item_ids: 	item_ids,
				order_item_qty: 	quantities,
				action: 			'woocommerce_increase_order_item_stock',
				security: 			woocommerce_writepanel_params.order_item_nonce
			};

			$.ajax( {
				url: woocommerce_writepanel_params.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					alert( response );
					$('table.woocommerce_order_items').unblock();
				}
			} );
		}

		return false;
	} );


	$('button.load_customer_billing').click(function(){

		var answer = confirm(woocommerce_writepanel_params.load_billing);
		if (answer){

			// Get user ID to load data for
			var user_id = $('#customer_user').val();

			if (!user_id) {
				alert(woocommerce_writepanel_params.no_customer_selected);
				return false;
			}

			var data = {
				user_id: 			user_id,
				type_to_load: 		'billing',
				action: 			'woocommerce_get_customer_details',
				security: 			woocommerce_writepanel_params.get_customer_details_nonce
			};

			$(this).closest('.edit_address').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			$.ajax({
				url: woocommerce_writepanel_params.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					var info = response;

					if (info) {
						$('input#_billing_first_name').val( info.billing_first_name );
						$('input#_billing_last_name').val( info.billing_last_name );
						$('input#_billing_company').val( info.billing_company );
						$('input#_billing_address_1').val( info.billing_address_1 );
						$('input#_billing_address_2').val( info.billing_address_2 );
						$('input#_billing_city').val( info.billing_city );
						$('input#_billing_postcode').val( info.billing_postcode );
						$('#_billing_country').val( info.billing_country );
						$('input#_billing_state').val( info.billing_state );
						$('input#_billing_email').val( info.billing_email );
						$('input#_billing_phone').val( info.billing_phone );
					}

					$('.edit_address').unblock();
				}
			});
		}
		return false;
	});

	$('button.load_customer_shipping').click(function(){

		var answer = confirm(woocommerce_writepanel_params.load_shipping);
		if (answer){

			// Get user ID to load data for
			var user_id = $('#customer_user').val();

			if (!user_id) {
				alert(woocommerce_writepanel_params.no_customer_selected);
				return false;
			}

			var data = {
				user_id: 			user_id,
				type_to_load: 		'shipping',
				action: 			'woocommerce_get_customer_details',
				security: 			woocommerce_writepanel_params.get_customer_details_nonce
			};

			$(this).closest('.edit_address').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			$.ajax({
				url: woocommerce_writepanel_params.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					var info = response;

					if (info) {
						$('input#_shipping_first_name').val( info.shipping_first_name );
						$('input#_shipping_last_name').val( info.shipping_last_name );
						$('input#_shipping_company').val( info.shipping_company );
						$('input#_shipping_address_1').val( info.shipping_address_1 );
						$('input#_shipping_address_2').val( info.shipping_address_2 );
						$('input#_shipping_city').val( info.shipping_city );
						$('input#_shipping_postcode').val( info.shipping_postcode );
						$('#_shipping_country').val( info.shipping_country );
						$('input#_shipping_state').val( info.shipping_state );
					}

					$('.edit_address').unblock();
				}
			});
		}
		return false;
	});

	$('button.billing-same-as-shipping').click(function(){
		var answer = confirm(woocommerce_writepanel_params.copy_billing);
		if (answer){
			$('input#_shipping_first_name').val( $('input#_billing_first_name').val() );
			$('input#_shipping_last_name').val( $('input#_billing_last_name').val() );
			$('input#_shipping_company').val( $('input#_billing_company').val() );
			$('input#_shipping_address_1').val( $('input#_billing_address_1').val() );
			$('input#_shipping_address_2').val( $('input#_billing_address_2').val() );
			$('input#_shipping_city').val( $('input#_billing_city').val() );
			$('input#_shipping_postcode').val( $('input#_billing_postcode').val() );
			$('#_shipping_country').val( $('#_billing_country').val() );
			$('input#_shipping_state').val( $('input#_billing_state').val() );
		}
		return false;
	});

	// Add a tax row
	$('a.add_tax_row').click(function(){

		var data = {
			order_id: 	woocommerce_writepanel_params.post_id,
			action: 	'woocommerce_add_line_tax',
			security: 	woocommerce_writepanel_params.calc_totals_nonce
		};

		$('#tax_rows').closest('.totals_group').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

		$.ajax({
			url: woocommerce_writepanel_params.ajax_url,
			data: data,
			type: 'POST',
			success: function( response ) {
				$('#tax_rows').append( response ).closest('.totals_group').unblock();
			}
		});

		return false;
	});

	// Delete a tax row
	$('#tax_rows').on('click','a.delete_tax_row',function(){

		var $tax_row = $(this).closest('.tax_row');

		var tax_row_id = $tax_row.attr( 'data-order_item_id' );

		var data = {
			tax_row_id: tax_row_id,
			action: 	'woocommerce_remove_line_tax',
			security: 	woocommerce_writepanel_params.calc_totals_nonce
		};

		$('#tax_rows').closest('.totals_group').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

		$.ajax({
			url: woocommerce_writepanel_params.ajax_url,
			data: data,
			type: 'POST',
			success: function( response ) {
				$tax_row.remove();
				$('#tax_rows').closest('.totals_group').unblock();
			}
		});

		return false;
	});

	// PRODUCT TYPE SPECIFIC OPTIONS
	$('select#product-type').change(function(){

		// Get value
		var select_val = $(this).val();

		if (select_val=='variable') {
			$('input#_manage_stock').change();
			$('input#_downloadable').prop('checked', false);
			$('input#_virtual').removeAttr('checked');
		}

		else if (select_val=='grouped') {
			$('input#_downloadable').prop('checked', false);
			$('input#_virtual').removeAttr('checked');
		}

		else if (select_val=='external') {
			$('input#_downloadable').prop('checked', false);
			$('input#_virtual').removeAttr('checked');
		}


		show_and_hide_panels();

		$('ul.wc-tabs li:visible').eq(0).find('a').click();

		$('body').trigger('woocommerce-product-type-change', select_val, $(this) );
	}).change();

	$('input#_downloadable, input#_virtual').change(function(){
		show_and_hide_panels();
	});

	function show_and_hide_panels() {
		var product_type    = $('select#product-type').val();
		var is_virtual      = $('input#_virtual:checked').size();
		var is_downloadable = $('input#_downloadable:checked').size();

		// Hide/Show all with rules
		var hide_classes = '.hide_if_downloadable, .hide_if_virtual';
		var show_classes = '.show_if_downloadable, .show_if_virtual, .show_if_external';

		$.each( woocommerce_writepanel_params.product_types, function( index, value ) {
			hide_classes = hide_classes + ', .hide_if_' + value;
			show_classes = show_classes + ', .show_if_' + value;
		} );

		$( hide_classes ).show();
		$( show_classes ).hide();

		// Shows rules
		if ( is_downloadable ) {
			$('.show_if_downloadable').show();
		}
		if ( is_virtual ) {
			$('.show_if_virtual').show();
		}

        $('.show_if_' + product_type).show();

		// Hide rules
		if ( is_downloadable ) {
			$('.hide_if_downloadable').hide();
		}
		if ( is_virtual ) {
			$('.hide_if_virtual').hide();
		}

		$('.hide_if_' + product_type).hide();

		$('input#_manage_stock').change();
	}


	// Sale price schedule
	$('.sale_price_dates_fields').each(function() {

		var $these_sale_dates = $(this);
		var sale_schedule_set = false;
		var $wrap = $these_sale_dates.closest( 'div, table' );

		$these_sale_dates.find('input').each(function(){
			if ( $(this).val() != '' )
				sale_schedule_set = true;
		});

		if ( sale_schedule_set ) {

			$wrap.find('.sale_schedule').hide();
			$wrap.find('.sale_price_dates_fields').show();

		} else {

			$wrap.find('.sale_schedule').show();
			$wrap.find('.sale_price_dates_fields').hide();

		}

	});

	$('#woocommerce-product-data').on( 'click', '.sale_schedule', function() {
		var $wrap = $(this).closest( 'div, table' );

		$(this).hide();
		$wrap.find('.cancel_sale_schedule').show();
		$wrap.find('.sale_price_dates_fields').show();

		return false;
	});
	$('#woocommerce-product-data').on( 'click', '.cancel_sale_schedule', function() {
		var $wrap = $(this).closest( 'div, table' );

		$(this).hide();
		$wrap.find('.sale_schedule').show();
		$wrap.find('.sale_price_dates_fields').hide();
		$wrap.find('.sale_price_dates_fields').find('input').val('');

		return false;
	});


	// STOCK OPTIONS
	$('input#_manage_stock').change(function(){
		if ($(this).is(':checked')) $('div.stock_fields').show();
		else $('div.stock_fields').hide();
	}).change();


	// DATE PICKER FIELDS
	var dates = $( ".sale_price_dates_fields input" ).datepicker({
		defaultDate: "",
		dateFormat: "yy-mm-dd",
		numberOfMonths: 1,
		showButtonPanel: true,
		showOn: "button",
		buttonImage: woocommerce_writepanel_params.calendar_image,
		buttonImageOnly: true,
		onSelect: function( selectedDate ) {
			var option = $(this).is('#_sale_price_dates_from, .sale_price_dates_from') ? "minDate" : "maxDate";

			var instance = $( this ).data( "datepicker" ),
				date = $.datepicker.parseDate(
					instance.settings.dateFormat ||
					$.datepicker._defaults.dateFormat,
					selectedDate, instance.settings );
			dates.not( this ).datepicker( "option", option, date );
		}
	});

	$( ".date-picker" ).datepicker({
		dateFormat: "yy-mm-dd",
		numberOfMonths: 1,
		showButtonPanel: true,
		showOn: "button",
		buttonImage: woocommerce_writepanel_params.calendar_image,
		buttonImageOnly: true
	});

	$( ".date-picker-field" ).datepicker({
		dateFormat: "yy-mm-dd",
		numberOfMonths: 1,
		showButtonPanel: true,
	});

	// META BOXES

		// Open/close
		jQuery('.wc-metaboxes-wrapper').on('click', '.wc-metabox h3', function(event){
			// If the user clicks on some form input inside the h3, like a select list (for variations), the box should not be toggled
			if ($(event.target).filter(':input, option').length) return;

			jQuery(this).next('.wc-metabox-content').toggle();
		})
		.on('click', '.expand_all', function(event){
			jQuery(this).closest('.wc-metaboxes-wrapper').find('.wc-metabox > table').show();
			return false;
		})
		.on('click', '.close_all', function(event){
			jQuery(this).closest('.wc-metaboxes-wrapper').find('.wc-metabox > table').hide();
			return false;
		});

		jQuery('.wc-metabox.closed').each(function(){
			jQuery(this).find('.wc-metabox-content').hide();
		});

	// ATTRIBUTE TABLES

		// Multiselect attributes
		$(".woocommerce_attributes select.multiselect").chosen();

		// Initial order
		var woocommerce_attribute_items = $('.woocommerce_attributes').find('.woocommerce_attribute').get();

		woocommerce_attribute_items.sort(function(a, b) {
		   var compA = parseInt($(a).attr('rel'));
		   var compB = parseInt($(b).attr('rel'));
		   return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
		})
		$(woocommerce_attribute_items).each( function(idx, itm) { $('.woocommerce_attributes').append(itm); } );

		function attribute_row_indexes() {
			$('.woocommerce_attributes .woocommerce_attribute').each(function(index, el){
				$('.attribute_position', el).val( parseInt( $(el).index('.woocommerce_attributes .woocommerce_attribute') ) );
			});
		};

		// Add rows
		$('button.add_attribute').on('click', function(){

			var size = $('.woocommerce_attributes .woocommerce_attribute').size();

			var attribute_type = $('select.attribute_taxonomy').val();

			if (!attribute_type) {

				var product_type = $('select#product-type').val();
				if (product_type!='variable') enable_variation = 'style="display:none;"'; else enable_variation = '';

				// Add custom attribute row
				$('.woocommerce_attributes').append('<div class="woocommerce_attribute wc-metabox">\
						<h3>\
							<button type="button" class="remove_row button">' + woocommerce_writepanel_params.remove_label + '</button>\
							<div class="handlediv" title="' + woocommerce_writepanel_params.click_to_toggle + '"></div>\
							<strong class="attribute_name"></strong>\
						</h3>\
						<table cellpadding="0" cellspacing="0" class="woocommerce_attribute_data">\
							<tbody>\
								<tr>\
									<td class="attribute_name">\
										<label>' + woocommerce_writepanel_params.name_label + ':</label>\
										<input type="text" class="attribute_name" name="attribute_names[' + size + ']" />\
										<input type="hidden" name="attribute_is_taxonomy[' + size + ']" value="0" />\
										<input type="hidden" name="attribute_position[' + size + ']" class="attribute_position" value="' + size + '" />\
									</td>\
									<td rowspan="3">\
										<label>' + woocommerce_writepanel_params.values_label + ':</label>\
										<textarea name="attribute_values[' + size + ']" cols="5" rows="5" placeholder="' + woocommerce_writepanel_params.text_attribute_tip + '"></textarea>\
									</td>\
								</tr>\
								<tr>\
									<td>\
										<label><input type="checkbox" class="checkbox" checked="checked" name="attribute_visibility[' + size + ']" value="1" /> ' + woocommerce_writepanel_params.visible_label + '</label>\
									</td>\
								</tr>\
								<tr>\
									<td>\
										<div class="enable_variation show_if_variable" ' + enable_variation + '>\
										<label><input type="checkbox" class="checkbox" name="attribute_variation[' + size + ']" value="1" /> ' + woocommerce_writepanel_params.used_for_variations_label + '</label>\
										</div>\
									</td>\
								</tr>\
							</tbody>\
						</table>\
					</div>');

			} else {

				// Reveal taxonomy row
				var thisrow = $('.woocommerce_attributes .woocommerce_attribute.' + attribute_type);
				$('.woocommerce_attributes').append( $(thisrow) );
				$(thisrow).show().find('.woocommerce_attribute_data').show();
				attribute_row_indexes();

			}

			$('select.attribute_taxonomy').val('');
		});

		$('.woocommerce_attributes').on('blur', 'input.attribute_name', function(){
			$(this).closest('.woocommerce_attribute').find('strong.attribute_name').text( $(this).val() );
		});

		$('.woocommerce_attributes').on('click', 'button.select_all_attributes', function(){
			$(this).closest('td').find('select option').attr("selected","selected");
			$(this).closest('td').find('select').trigger("liszt:updated");
			return false;
		});

		$('.woocommerce_attributes').on('click', 'button.select_no_attributes', function(){
			$(this).closest('td').find('select option').removeAttr("selected");
			$(this).closest('td').find('select').trigger("liszt:updated");
			return false;
		});

		$('.woocommerce_attributes').on('click', 'button.remove_row', function() {
			var answer = confirm(woocommerce_writepanel_params.remove_attribute);
			if (answer){
				var $parent = $(this).parent().parent();

				if ($parent.is('.taxonomy')) {
					$parent.find('select, input[type=text]').val('');
					$parent.hide();
				} else {
					$parent.find('select, input[type=text]').val('');
					$parent.hide();
					attribute_row_indexes();
				}
			}
			return false;
		});

		// Attribute ordering
		$('.woocommerce_attributes').sortable({
			items:'.woocommerce_attribute',
			cursor:'move',
			axis:'y',
			handle: 'h3',
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
				attribute_row_indexes();
			}
		});

		// Add a new attribute (via ajax)
		$('.woocommerce_attributes').on('click', 'button.add_new_attribute', function() {

			$('.woocommerce_attributes').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			var attribute = $(this).attr('data-attribute');
			var $wrapper = $(this).closest('.woocommerce_attribute_data');
			var new_attribute_name = prompt( woocommerce_writepanel_params.new_attribute_prompt );

			if ( new_attribute_name ) {

				var data = {
					action: 		'woocommerce_add_new_attribute',
					taxonomy:		attribute,
					term:			new_attribute_name,
					security: 		woocommerce_writepanel_params.add_attribute_nonce
				};

				$.post( woocommerce_writepanel_params.ajax_url, data, function( response ) {

					if ( response.error ) {
						// Error
						alert( response.error );
					} else if ( response.slug ) {
						// Success
						$wrapper.find('select.attribute_values').append('<option value="' + response.slug + '" selected="selected">' + response.name + '</option>');
						$wrapper.find('select.attribute_values').trigger("liszt:updated");
					}

					$('.woocommerce_attributes').unblock();

				});

			} else {
				$('.woocommerce_attributes').unblock();
			}

			return false;

		});

		// Save attributes and update variations
		$('.save_attributes').on('click', function(){

			$('.woocommerce_attributes').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			var data = {
				post_id: 		woocommerce_writepanel_params.post_id,
				data:			$('.woocommerce_attributes').find('input, select, textarea').serialize(),
				action: 		'woocommerce_save_attributes',
				security: 		woocommerce_writepanel_params.save_attributes_nonce
			};

			$.post( woocommerce_writepanel_params.ajax_url, data, function( response ) {

				var this_page = window.location.toString();

				this_page = this_page.replace( 'post-new.php?', 'post.php?post=' + woocommerce_writepanel_params.post_id + '&action=edit&' );

				// Load variations panel
				$('#variable_product_options').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });
				$('#variable_product_options').load( this_page + ' #variable_product_options_inner', function() {
					$('#variable_product_options').unblock();
				} );

				$('.woocommerce_attributes').unblock();

			});

		});

	// Uploading files
	var downloadable_file_frame;

	jQuery(document).on( 'click', '.upload_file_button', function( event ){

		var $el = $(this);
		var $file_path_field = $el.parent().find('.file_paths');
		var file_paths = $file_path_field.val();

		event.preventDefault();

		// If the media frame already exists, reopen it.
		if ( downloadable_file_frame ) {
			downloadable_file_frame.open();
			return;
		}

		var downloadable_file_states = [
			// Main states.
			new wp.media.controller.Library({
				library:   wp.media.query(),
				multiple:  true,
				title:     $el.data('choose'),
				priority:  20,
				filterable: 'uploaded',
			})
		];

		// Create the media frame.
		downloadable_file_frame = wp.media.frames.downloadable_file = wp.media({
			// Set the title of the modal.
			title: $el.data('choose'),
			library: {
				type: ''
			},
			button: {
				text: $el.data('update'),
			},
			multiple: true,
			states: downloadable_file_states,
		});

		// When an image is selected, run a callback.
		downloadable_file_frame.on( 'select', function() {

			var selection = downloadable_file_frame.state().get('selection');

			selection.map( function( attachment ) {

				attachment = attachment.toJSON();

				if ( attachment.url )
					file_paths = file_paths ? file_paths + "\n" + attachment.url : attachment.url

			} );

			$file_path_field.val( file_paths );
		});

		// Set post to 0 and set our custom type
		downloadable_file_frame.on( 'ready', function() {
			downloadable_file_frame.uploader.options.uploader.params = {
				type: 'downloadable_product'
			};
		});

		// Finally, open the modal.
		downloadable_file_frame.open();
	});

});

/*!
 * accounting.js v0.3.2, copyright 2011 Joss Crowcroft, MIT license, http://josscrowcroft.github.com/accounting.js
 */
(function(p,z){function q(a){return!!(""===a||a&&a.charCodeAt&&a.substr)}function m(a){return u?u(a):"[object Array]"===v.call(a)}function r(a){return"[object Object]"===v.call(a)}function s(a,b){var d,a=a||{},b=b||{};for(d in b)b.hasOwnProperty(d)&&null==a[d]&&(a[d]=b[d]);return a}function j(a,b,d){var c=[],e,h;if(!a)return c;if(w&&a.map===w)return a.map(b,d);for(e=0,h=a.length;e<h;e++)c[e]=b.call(d,a[e],e,a);return c}function n(a,b){a=Math.round(Math.abs(a));return isNaN(a)?b:a}function x(a){var b=c.settings.currency.format;"function"===typeof a&&(a=a());return q(a)&&a.match("%v")?{pos:a,neg:a.replace("-","").replace("%v","-%v"),zero:a}:!a||!a.pos||!a.pos.match("%v")?!q(b)?b:c.settings.currency.format={pos:b,neg:b.replace("%v","-%v"),zero:b}:a}var c={version:"0.3.2",settings:{currency:{symbol:"$",format:"%s%v",decimal:".",thousand:",",precision:2,grouping:3},number:{precision:0,grouping:3,thousand:",",decimal:"."}}},w=Array.prototype.map,u=Array.isArray,v=Object.prototype.toString,o=c.unformat=c.parse=function(a,b){if(m(a))return j(a,function(a){return o(a,b)});a=a||0;if("number"===typeof a)return a;var b=b||".",c=RegExp("[^0-9-"+b+"]",["g"]),c=parseFloat((""+a).replace(/\((.*)\)/,"-$1").replace(c,"").replace(b,"."));return!isNaN(c)?c:0},y=c.toFixed=function(a,b){var b=n(b,c.settings.number.precision),d=Math.pow(10,b);return(Math.round(c.unformat(a)*d)/d).toFixed(b)},t=c.formatNumber=function(a,b,d,i){if(m(a))return j(a,function(a){return t(a,b,d,i)});var a=o(a),e=s(r(b)?b:{precision:b,thousand:d,decimal:i},c.settings.number),h=n(e.precision),f=0>a?"-":"",g=parseInt(y(Math.abs(a||0),h),10)+"",l=3<g.length?g.length%3:0;return f+(l?g.substr(0,l)+e.thousand:"")+g.substr(l).replace(/(\d{3})(?=\d)/g,"$1"+e.thousand)+(h?e.decimal+y(Math.abs(a),h).split(".")[1]:"")},A=c.formatMoney=function(a,b,d,i,e,h){if(m(a))return j(a,function(a){return A(a,b,d,i,e,h)});var a=o(a),f=s(r(b)?b:{symbol:b,precision:d,thousand:i,decimal:e,format:h},c.settings.currency),g=x(f.format);return(0<a?g.pos:0>a?g.neg:g.zero).replace("%s",f.symbol).replace("%v",t(Math.abs(a),n(f.precision),f.thousand,f.decimal))};c.formatColumn=function(a,b,d,i,e,h){if(!a)return[];var f=s(r(b)?b:{symbol:b,precision:d,thousand:i,decimal:e,format:h},c.settings.currency),g=x(f.format),l=g.pos.indexOf("%s")<g.pos.indexOf("%v")?!0:!1,k=0,a=j(a,function(a){if(m(a))return c.formatColumn(a,f);a=o(a);a=(0<a?g.pos:0>a?g.neg:g.zero).replace("%s",f.symbol).replace("%v",t(Math.abs(a),n(f.precision),f.thousand,f.decimal));if(a.length>k)k=a.length;return a});return j(a,function(a){return q(a)&&a.length<k?l?a.replace(f.symbol,f.symbol+Array(k-a.length+1).join(" ")):Array(k-a.length+1).join(" ")+a:a})};if("undefined"!==typeof exports){if("undefined"!==typeof module&&module.exports)exports=module.exports=c;exports.accounting=c}else"function"===typeof define&&define.amd?define([],function(){return c}):(c.noConflict=function(a){return function(){p.accounting=a;c.noConflict=z;return c}}(p.accounting),p.accounting=c)})(this);
