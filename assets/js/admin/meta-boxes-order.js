/*global woocommerce_admin_meta_boxes */
jQuery( function ( $ ) {

	/**
	 * Add order items loading block
	 */
	function addOrderItemsLoading() {
		$( '#woocommerce-order-items' ).block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + woocommerce_admin_meta_boxes.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity: 0.6
			}
		});
	}

	/**
	 * Remove order items loading block
	 */
	function removeOrderItemsLoading() {
		$( '#woocommerce-order-items' ).unblock();
	}

	/**
	 * Run TipTip
	 */
	function runTipTip() {
		$( '#tiptip_holder' ).removeAttr( 'style' );
		$( '#tiptip_arrow' ).removeAttr( 'style' );
		$( '.tips' ).tipTip({
			'attribute': 'data-tip',
			'fadeIn': 50,
			'fadeOut': 50,
			'delay': 200
		});
	}

	/**
	 * Load order items
	 *
	 * @return {void}
	 */
	function loadOrderItems() {
		var data = {
			order_id: woocommerce_admin_meta_boxes.post_id,
			action:   'woocommerce_load_order_items',
			security: woocommerce_admin_meta_boxes.order_item_nonce
		};

		addOrderItemsLoading();

		$.ajax({
			url:  woocommerce_admin_meta_boxes.ajax_url,
			data: data,
			type: 'POST',
			success: function( response ) {
				$( '#woocommerce-order-items .inside' ).empty();
				$( '#woocommerce-order-items .inside' ).append( response );
				runTipTip();
				removeOrderItemsLoading();
			}
		});
	}

	// ORDERS
	$( '#woocommerce-order-actions input, #woocommerce-order-actions a' ).click( function () {
		window.onbeforeunload = '';
	});

	$( 'a.edit_address' ).click( function ( e ) {
		e.preventDefault();
		$( this ).hide();
		$( this ).closest( '.order_data_column' ).find( 'div.address' ).hide();
		$( this ).closest( '.order_data_column' ).find( 'div.edit_address' ).show();
	});

	// When the page is loaded, store the unit costs
	$( '#order_line_items tr, #order_fee_line_items tr' ).each( function () {
		$( this ).trigger( 'init_row' );
		$( this ).find( '.edit' ).hide();
	});

	$( 'body' )
		.on( 'init_row', '#order_line_items tr.item', function() {
			var $row = $(this);
			var $qty = $row.find('input.quantity');
			var qty = $qty.val();

			var line_subtotal     = accounting.unformat( $row.find('input.line_subtotal').val(), woocommerce_admin.mon_decimal_point );
			var line_total        = accounting.unformat( $row.find('input.line_total').val(), woocommerce_admin.mon_decimal_point );
			var line_tax          = accounting.unformat( $row.find('input.line_tax').val(), woocommerce_admin.mon_decimal_point );
			var line_subtotal_tax = accounting.unformat( $row.find('input.line_subtotal_tax').val(), woocommerce_admin.mon_decimal_point );

			if ( qty ) {
				unit_subtotal 		= parseFloat( accounting.toFixed( ( line_subtotal / qty ), woocommerce_admin_meta_boxes.rounding_precision ) );
				unit_subtotal_tax 	= parseFloat( accounting.toFixed( ( line_subtotal_tax / qty ), woocommerce_admin_meta_boxes.rounding_precision ) );
				unit_total			= parseFloat( accounting.toFixed( ( line_total / qty ), woocommerce_admin_meta_boxes.rounding_precision ) );
				unit_total_tax		= parseFloat( accounting.toFixed( ( line_tax / qty ), woocommerce_admin_meta_boxes.rounding_precision ) );
			} else {
				unit_subtotal = unit_subtotal_tax = unit_total = unit_total_tax = 0;
			}

			$qty.attr( 'data-o_qty', qty );
			$row.attr( 'data-unit_subtotal', unit_subtotal );
			$row.attr( 'data-unit_subtotal_tax', unit_subtotal_tax );
			$row.attr( 'data-unit_total', unit_total );
			$row.attr( 'data-unit_total_tax', unit_total_tax );
		})
		.on( 'init_row', '#order_fee_line_items tr.fee', function() {
			var $row = $(this);

			var line_total        = accounting.unformat( $row.find('input.line_total').val(), woocommerce_admin.mon_decimal_point );
			var line_tax          = accounting.unformat( $row.find('input.line_tax').val(), woocommerce_admin.mon_decimal_point );

			unit_total			= parseFloat( accounting.toFixed( line_total, woocommerce_admin_meta_boxes.rounding_precision ) );
			unit_total_tax		= parseFloat( accounting.toFixed( line_tax, woocommerce_admin_meta_boxes.rounding_precision ) );

			$row.attr( 'data-unit_total', unit_total );
			$row.attr( 'data-unit_total_tax', unit_total_tax );
		})
		.on( 'click', 'a.edit-order-item', function() {
			$( this ).closest( 'tr' ).find( '.view' ).hide();
			$( this ).closest( 'tr' ).find( '.edit' ).show();
			$( this ).hide();
			$( 'button.add-line-item' ).click();
			$( 'button.cancel-action' ).attr( 'data-reload', true );

			return false;
		})
		.on( 'click', '.woocommerce_order_items a.delete-order-item', function () {
			var answer = window.confirm( woocommerce_admin_meta_boxes.remove_item_notice );

			if ( answer ) {
				var $item         = $( this ).closest( 'tr.item, tr.fee, tr.shipping' );
				var order_item_id = $item.attr( 'data-order_item_id' );

				addOrderItemsLoading();

				var data = {
					order_item_ids: order_item_id,
					action:         'woocommerce_remove_order_item',
					security:       woocommerce_admin_meta_boxes.order_item_nonce
				};

				$.ajax({
					url:     woocommerce_admin_meta_boxes.ajax_url,
					data:    data,
					type:    'POST',
					success: function ( response ) {
						$item.remove();
						removeOrderItemsLoading();
					}
				});
			}
			return false;
		})
		.on( 'click', '#order_refunds .delete_refund', function () {
			if ( window.confirm( woocommerce_admin_meta_boxes.i18n_delete_refund ) ) {
				var $refund   = $( this ).closest( 'tr.refund' );
				var refund_id = $refund.attr( 'data-order_refund_id' );

				addOrderItemsLoading();

				var data = {
					action:    'woocommerce_delete_refund',
					refund_id: refund_id,
					security:  woocommerce_admin_meta_boxes.order_item_nonce,
				};

				$.ajax({
					url:     woocommerce_admin_meta_boxes.ajax_url,
					data:    data,
					type:    'POST',
					success: function ( response ) {
						loadOrderItems();
					}
				});
			}
			return false;
		})
		// When the qty is changed, increase or decrease costs
		.on( 'change', '.woocommerce_order_items input.quantity', function() {
			var $row = $(this).closest('tr.item');
			var qty = $(this).val();

			var unit_subtotal 		= $row.attr('data-unit_subtotal');
			var unit_subtotal_tax 	= $row.attr('data-unit_subtotal_tax');
			var unit_total 			= $row.attr('data-unit_total');
			var unit_total_tax = $row.attr('data-unit_total_tax');
			var o_qty 				= $(this).attr('data-o_qty');

			var subtotal  = parseFloat( accounting.formatNumber( unit_subtotal * qty, woocommerce_admin_meta_boxes.rounding_precision, '' ) );
			var tax       = parseFloat( accounting.formatNumber( unit_subtotal_tax * qty, woocommerce_admin_meta_boxes.rounding_precision, '' ) );
			var total     = parseFloat( accounting.formatNumber( unit_total * qty, woocommerce_admin_meta_boxes.rounding_precision, '' ) );
			var total_tax = parseFloat( accounting.formatNumber( unit_total_tax * qty, woocommerce_admin_meta_boxes.rounding_precision, '' ) );

			subtotal  = subtotal.toString().replace( '.', woocommerce_admin.mon_decimal_point );
			tax       = tax.toString().replace( '.', woocommerce_admin.mon_decimal_point );
			total     = total.toString().replace( '.', woocommerce_admin.mon_decimal_point );
			total_tax = total_tax.toString().replace( '.', woocommerce_admin.mon_decimal_point );

			$row.find('input.line_subtotal').val( subtotal );
			$row.find('input.line_total').val( total );
			$row.find('input.line_subtotal_tax').val( tax );
			$row.find('input.line_tax').val( total_tax );

			$(this).trigger('quantity_changed');
		})
		// When subtotal is changed, update the unit costs
		.on( 'change', '.woocommerce_order_items input.line_subtotal', function() {
			var $row = $(this).closest('tr.item');
			var $qty = $row.find('input.quantity');
			var qty = $qty.val();
			var value = ( qty ) ? accounting.toFixed( ( $(this).val() / qty ), woocommerce_admin_meta_boxes.rounding_precision ) : 0;

			$row.attr( 'data-unit_subtotal', value );
		})
		// When total is changed, update the unit costs + discount amount
		.on( 'change', '.woocommerce_order_items input.line_total', function() {
			var $row = $(this).closest('tr.item');
			var $qty = $row.find('input.quantity');
			var qty = $qty.val();
			var value = ( qty ) ? accounting.toFixed( ( $(this).val() / qty ), woocommerce_admin_meta_boxes.rounding_precision ) : 0;

			$row.attr( 'data-unit_total', value );
		})
		// When total is changed, update the unit costs + discount amount
		.on( 'change', '.woocommerce_order_items input.line_subtotal_tax', function() {
			var $row = $(this).closest('tr.item');
			var $qty = $row.find('input.quantity');
			var qty = $qty.val();
			var value = ( qty ) ? accounting.toFixed( ( $(this).val() / qty ), woocommerce_admin_meta_boxes.rounding_precision ) : 0;

			$row.attr( 'data-unit_subtotal_tax', value );
		})
		// When total is changed, update the unit costs + discount amount
		.on( 'change', '.woocommerce_order_items input.line_tax', function() {
			var $row = $(this).closest('tr.item');
			var $qty = $row.find('input.quantity');
			var qty = $qty.val();
			var value = ( qty ) ? accounting.toFixed( ( $(this).val() / qty ), woocommerce_admin_meta_boxes.rounding_precision ) : 0;

			$row.attr( 'data-unit_total_tax', value );
		})
		.on( 'change', '.woocommerce_order_items .wc-order-item-refund-quantity input', function () {
			var refund_amount = 0;
			var $items        = $( '.woocommerce_order_items' ).find( 'tr.item, tr.fee, tr.shipping' );

			$items.each( function () {
				var $row       = $( this );
				var refund_qty = $row.find( '.wc-order-item-refund-quantity input' ).val();

				if ( refund_qty ) {
					var unit_total = $( this ).find( 'input.line_total' ).val() || 0;
					unit_total     = parseFloat( unit_total );

					$( this ).find( 'input.line_tax' ).each( function () {
						var cost   = $( this ).val() || 0;
						cost       = accounting.unformat( cost, woocommerce_admin.mon_decimal_point );
						unit_total = unit_total + parseFloat( cost );
					});

					refund_amount = parseFloat( refund_amount ) + ( refund_qty * parseFloat( unit_total ) );
				}
			});

			$( '#refund_amount' )
				.val( accounting.formatNumber(
					refund_amount,
					woocommerce_admin_meta_boxes.currency_format_num_decimals,
					'',
					woocommerce_admin.mon_decimal_point
				) )
				.change();
		})
		// Add some meta to a line item
		.on( 'click', '.woocommerce_order_items button.add_order_item_meta', function () {

			var $button = $( this );
			var $item = $button.closest( 'tr.item' );

			var data = {
				order_item_id: 	$item.attr( 'data-order_item_id' ),
				action: 	'woocommerce_add_order_item_meta',
				security: 	woocommerce_admin_meta_boxes.order_item_nonce
			};

			addOrderItemsLoading();

			$.ajax( {
				url: woocommerce_admin_meta_boxes.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					$item.find('tbody.meta_items').append( response );
					removeOrderItemsLoading();
				}
			} );

			return false;
		})
		// Remove some meta from a line item
		.on('click', '.woocommerce_order_items button.remove_order_item_meta', function(){
			var answer = confirm( woocommerce_admin_meta_boxes.remove_item_meta )
			if ( answer ) {
				var $row = $(this).closest('tr');

				var data = {
					meta_id: 			$row.attr( 'data-meta_id' ),
					action: 			'woocommerce_remove_order_item_meta',
					security: 			woocommerce_admin_meta_boxes.order_item_nonce
				};

				addOrderItemsLoading();

				$.ajax( {
					url: woocommerce_admin_meta_boxes.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						$row.hide();
						removeOrderItemsLoading();
					}
				} );
			}
			return false;
		});

	$( '#woocommerce-order-items' )
		.on( 'click', 'button.add-line-item', function () {
			$( 'div.wc-order-add-item' ).slideDown();
			$( 'div.wc-order-bulk-actions' ).slideUp();

			return false;
		})
		.on( 'click', 'button.refund-items', function () {
			$( 'div.wc-order-refund-items' ).slideDown();
			$( 'div.wc-order-bulk-actions' ).slideUp();
			$( 'div.wc-order-totals-items' ).slideUp();
			$( '.wc-order-item-refund-quantity' ).show();
			$( '.wc-order-edit-line-item' ).hide();

			return false;
		})
		.on( 'click', '.cancel-action', function () {
			$( this ).closest( 'div.wc-order-data-row' ).slideUp();
			$( 'div.wc-order-bulk-actions' ).slideDown();
			$( 'div.wc-order-totals-items' ).slideDown();
			$( '.wc-order-item-refund-quantity' ).hide();
			$( '.wc-order-edit-line-item' ).show();

			// Reload the items
			if ( 'true' === $( this ).attr( 'data-reload' ) ) {
				loadOrderItems();
			}

			return false;
		})
		.on( 'click', 'button.add-order-item', function () {
			$( this ).WCBackboneModal({
				template: '#wc-modal-add-products'
			});

			return false;
		})
		.on( 'click', 'button.add-order-fee', function () {
			addOrderItemsLoading();

			var data = {
				action:   'woocommerce_add_order_fee',
				order_id: woocommerce_admin_meta_boxes.post_id,
				security: woocommerce_admin_meta_boxes.order_item_nonce
			};

			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function ( response ) {
				$( 'table.woocommerce_order_items tbody#order_fee_line_items' ).append( response );
				removeOrderItemsLoading();
			});

			return false;
		})
		.on( 'click', 'button.add-order-shipping', function () {
			addOrderItemsLoading();

			var data = {
				action:   'woocommerce_add_order_shipping',
				order_id: woocommerce_admin_meta_boxes.post_id,
				security: woocommerce_admin_meta_boxes.order_item_nonce
			};

			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function ( response ) {
				$( 'table.woocommerce_order_items tbody#order_shipping_line_items' ).append( response );
				removeOrderItemsLoading();
			});

			return false;
		})
		.on( 'click', 'button.add-order-tax', function () {
			$( this ).WCBackboneModal({
				template: '#wc-modal-add-tax'
			});
			return false;
		})
		// Bulk actions for line items
		.on( 'click', 'input.check-column', function() {
			if ( $(this).is(':checked') ) {
				$('#woocommerce-order-items').find('.check-column input').attr('checked', 'checked');
			} else {
				$('#woocommerce-order-items').find('.check-column input').removeAttr('checked');
			}
		})
		.on( 'click', '.do_bulk_action', function() {
			var action = $(this).closest('.bulk-actions').find('select').val();
			var selected_rows = $('#woocommerce-order-items').find('.check-column input:checked');
			var item_ids = [];

			$(selected_rows).each( function() {
				var $item = $(this).closest('tr');

				if ( $item.attr( 'data-order_item_id' ) ) {
					item_ids.push( $item.attr( 'data-order_item_id' ) );
				}
			} );

			if ( item_ids.length == 0 ) {
				alert( woocommerce_admin_meta_boxes.i18n_select_items );
				return;
			}

			if ( action == 'delete' ) {

				var answer = confirm( woocommerce_admin_meta_boxes.remove_item_notice );

				if ( answer ) {

					addOrderItemsLoading();

					var data = {
						order_item_ids: 	item_ids,
						action: 			'woocommerce_remove_order_item',
						security: 			woocommerce_admin_meta_boxes.order_item_nonce
					};

					$.ajax( {
						url: woocommerce_admin_meta_boxes.ajax_url,
						data: data,
						type: 'POST',
						success: function( response ) {
							$(selected_rows).each( function() {
								$(this).closest('tr').remove();
							} );
							removeOrderItemsLoading();
						}
					} );
				}

			} else if ( action == 'reduce_stock' ) {

				addOrderItemsLoading();

				var quantities = {};

				$(selected_rows).each( function() {

					var $item = $(this).closest('tr.item, tr.fee');
					var $qty  = $item.find('input.quantity');

					quantities[ $item.attr( 'data-order_item_id' ) ] = $qty.val();
				} );

				var data = {
					order_id:			woocommerce_admin_meta_boxes.post_id,
					order_item_ids: 	item_ids,
					order_item_qty: 	quantities,
					action: 			'woocommerce_reduce_order_item_stock',
					security: 			woocommerce_admin_meta_boxes.order_item_nonce
				};

				$.ajax( {
					url: woocommerce_admin_meta_boxes.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						alert( response );
						removeOrderItemsLoading();
					}
				} );

			} else if ( action == 'increase_stock' ) {

				addOrderItemsLoading();

				var quantities = {};

				$(selected_rows).each( function() {

					var $item = $(this).closest('tr.item, tr.fee');
					var $qty  = $item.find('input.quantity');

					quantities[ $item.attr( 'data-order_item_id' ) ] = $qty.val();
				} );

				var data = {
					order_id:			woocommerce_admin_meta_boxes.post_id,
					order_item_ids: 	item_ids,
					order_item_qty: 	quantities,
					action: 			'woocommerce_increase_order_item_stock',
					security: 			woocommerce_admin_meta_boxes.order_item_nonce
				};

				$.ajax( {
					url: woocommerce_admin_meta_boxes.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						alert( response );
						removeOrderItemsLoading();
					}
				} );
			}

			return false;
		})
		.on( 'click', 'button.calculate-action', function () {
			if ( window.confirm( woocommerce_admin_meta_boxes.calc_totals ) ) {

				addOrderItemsLoading();

				// Get row totals
				var line_totals    = 0;
				var tax            = 0;
				var shipping       = 0;
				var order_discount = $( '#_order_discount' ).val() || '0';

				order_discount = accounting.unformat( order_discount.replace( ',', '.' ) );

				$( '.woocommerce_order_items tr.shipping input.line_total' ).each( function () {
					cost     = $( this ).val() || '0';
					cost     = accounting.unformat( cost, woocommerce_admin.mon_decimal_point );
					shipping = shipping + parseFloat( cost );
				});

				$( '.woocommerce_order_items input.line_tax' ).each( function () {
					cost = $( this ).val() || '0';
					cost = accounting.unformat( cost, woocommerce_admin.mon_decimal_point );
					tax  = tax + parseFloat( cost );
				});

				$( '.woocommerce_order_items tr.item, .woocommerce_order_items tr.fee' ).each( function () {
					line_total  = $( this ).find( 'input.line_total' ).val() || '0';
					line_totals = line_totals + accounting.unformat( line_total.replace( ',', '.' ) );
				});

				// Tax
				if ( 'yes' === woocommerce_admin_meta_boxes.round_at_subtotal ) {
					tax = parseFloat( accounting.toFixed( tax, woocommerce_admin_meta_boxes.rounding_precision ) );
				}

				// Set Total
				$( '#_order_total' )
					.val( accounting.formatNumber( line_totals + tax + shipping - order_discount, woocommerce_admin_meta_boxes.currency_format_num_decimals, '', woocommerce_admin.mon_decimal_point ) )
					.change();

				$( 'button.save-action' ).click();
			}

			return false;
		})
		.on( 'click', 'button.save-action', function () {
			var data = {
				order_id: woocommerce_admin_meta_boxes.post_id,
				items:    $( 'table.woocommerce_order_items :input[name], .wc-order-totals-items :input[name]' ).serialize(),
				action:   'woocommerce_save_order_items',
				security: woocommerce_admin_meta_boxes.order_item_nonce
			};

			addOrderItemsLoading();

			$.ajax({
				url:  woocommerce_admin_meta_boxes.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					$( '#woocommerce-order-items .inside' ).empty();
					$( '#woocommerce-order-items .inside' ).append( response );
					runTipTip();
					removeOrderItemsLoading();
				}
			});

			return false;
		})
		.on( 'click', 'a.delete-order-tax', function () {

			addOrderItemsLoading();

			var data = {
				action:   'woocommerce_remote_order_tax',
				rate_id:  $( this ).attr( 'data-rate_id' ),
				order_id: woocommerce_admin_meta_boxes.post_id,
				security: woocommerce_admin_meta_boxes.order_item_nonce
			};

			$.ajax({
				url:  woocommerce_admin_meta_boxes.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					$( '#woocommerce-order-items .inside' ).empty();
					$( '#woocommerce-order-items .inside' ).append( response );
					runTipTip();
					removeOrderItemsLoading();
				}
			});

			return false;
		});

	// Refund actions
	$( 'body' )
		.on( 'change', '.wc-order-refund-items #refund_amount', function () {
			$( 'button .wc-order-refund-amount .amount' ).text( accounting.formatMoney( $( this ).val(), {
				symbol:    woocommerce_admin_meta_boxes.currency_format_symbol,
				decimal:   woocommerce_admin_meta_boxes.currency_format_decimal_sep,
				thousand:  woocommerce_admin_meta_boxes.currency_format_thousand_sep,
				precision: woocommerce_admin_meta_boxes.currency_format_num_decimals,
				format:    woocommerce_admin_meta_boxes.currency_format
			} ) );
		})
		.on( 'click', '.wc-order-refund-items button.do-api-refund, .wc-order-refund-items button.do-manual-refund', function () {
			addOrderItemsLoading();

			if ( window.confirm( woocommerce_admin_meta_boxes.i18n_do_refund ) ) {
				var refund_amount = $( 'input#refund_amount' ).val();
				var refund_reason = $( 'input#refund_reason' ).val();
				var refund_qty    = $.map( $( 'input[type=number][name^=order_item_refund_qty]' ), function( item ) {
				var result        = [];
				result.push( $( item ).closest( 'tr.item,tr.fee' ).data( 'order_item_id' ), item.value );
					return result;
				});
				var data          = {
					action:        'woocommerce_refund_line_items',
					order_id:      woocommerce_admin_meta_boxes.post_id,
					refund_amount: refund_amount,
					refund_reason: refund_reason,
					refund_qty:    JSON.stringify( refund_qty, null, '' ),
					api_refund:    $( this ).is( '.do-api-refund' ),
					security:      woocommerce_admin_meta_boxes.order_item_nonce
				};
				$.post( woocommerce_admin_meta_boxes.ajax_url, data, function ( response ) {
					if ( response === true ) {
						loadOrderItems();
					} else if ( response.error ) {
						window.alert( response.error );
						removeOrderItemsLoading();
					}
				});
			} else {
				removeOrderItemsLoading();
			}
		});

	// Display a total for taxes
	$('#woocommerce-order-totals')
		// Calculate totals
		.on('click', 'button.calc_line_taxes', function(){
			// Block write panel
			$('.woocommerce_order_items_wrapper').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_admin_meta_boxes.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			var answer = confirm(woocommerce_admin_meta_boxes.calc_line_taxes);

			if (answer) {

				var $items = $('.woocommerce_order_items').find('tr.item, tr.fee');

				var shipping_country = $('#_shipping_country').val();
				var billing_country = $('#_billing_country').val();

				if (shipping_country) {
					var country = shipping_country;
					var state = $('#_shipping_state').val();
					var postcode = $('#_shipping_postcode').val();
					var city = $('#_shipping_city').val();
				} else if(billing_country) {
					var country = billing_country;
					var state = $('#_billing_state').val();
					var postcode = $('#_billing_postcode').val();
					var city = $('#_billing_city').val();
				} else {
					var country = woocommerce_admin_meta_boxes.base_country;
					var state = '';
					var postcode = '';
					var city = '';
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

				order_shipping = 0;

				$('#shipping_rows').find('input[type=number], .wc_input_price').each(function(){
					cost = $(this).val() || '0';
					cost = accounting.unformat( cost, woocommerce_admin.mon_decimal_point );
					order_shipping = order_shipping + parseFloat( cost );
				});

				var data = {
					action: 		'woocommerce_calc_line_taxes',
					order_id: 		woocommerce_admin_meta_boxes.post_id,
					items:			calculate_items,
					shipping:		order_shipping,
					country:		country,
					state:			state,
					postcode:		postcode,
					city:			city,
					security: 		woocommerce_admin_meta_boxes.calc_totals_nonce
				};

				$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
					if ( response ) {
						$items.each( function() {
							var $row = $(this);
							var item_id = $row.find('input.order_item_id').val();
							$row.find('.edit-order-item').click();

							if ( response['item_taxes'][ item_id ] ) {
								$row.find('input.line_tax').val( response['item_taxes'][ item_id ]['line_tax'] ).change();
								$row.find('input.line_subtotal_tax').val( response['item_taxes'][ item_id ]['line_subtotal_tax'] ).change();
							}

							if ( response['tax_row_html'] )
								$('#tax_rows').empty().append( response['tax_row_html'] );
						} );

						$('#tax_rows').find('input').change();
					}

					$('.woocommerce_order_items_wrapper').unblock();
				});

			} else {
				$('.woocommerce_order_items_wrapper').unblock();
			}
			return false;
		});

	// Adds new products in order items
	$( 'body' ).on( 'wc_backbone_modal_response', function ( e, target ) {
		if ( '#wc-modal-add-products' !== target ) {
			return;
		}

		var add_item_ids = $( 'select#add_item_id' ).val();

		if ( add_item_ids ) {

			count = add_item_ids.length;

			addOrderItemsLoading();

			$.each( add_item_ids, function ( index, value ) {

				var data = {
					action:      'woocommerce_add_order_item',
					item_to_add: value,
					order_id:    woocommerce_admin_meta_boxes.post_id,
					security:    woocommerce_admin_meta_boxes.order_item_nonce
				};

				$.post( woocommerce_admin_meta_boxes.ajax_url, data, function ( response ) {

					$( 'table.woocommerce_order_items tbody#order_line_items' ).append( response );

					if ( !--count ) {
						$( 'select#add_item_id, #add_item_id_chosen .chosen-choices' ).css( 'border-color', '' ).val( '' );
						runTipTip();
						$( 'select#add_item_id' ).trigger( 'chosen:updated' );
						removeOrderItemsLoading();
					}

					$( '#order_line_items tr.new_row' ).trigger( 'init_row' ).removeClass( 'new_row' );
				});

			});

		} else {
			$( 'select#add_item_id, #add_item_id_chosen .chosen-choices' ).css( 'border-color', 'red' );
		}
	});

	// Adds new tax in order items
	$( 'body' ).on( 'wc_backbone_modal_response', function ( e, target ) {
		if ( '#wc-modal-add-tax' !== target ) {
			return;
		}

		addOrderItemsLoading();

		var data = {
			action:   'woocommerce_add_order_tax',
			rate_id:  $( '#add-order-tax' ).val(),
			order_id: woocommerce_admin_meta_boxes.post_id,
			security: woocommerce_admin_meta_boxes.order_item_nonce
		};

		$.ajax({
			url:  woocommerce_admin_meta_boxes.ajax_url,
			data: data,
			type: 'POST',
			success: function( response ) {
				$( '#woocommerce-order-items .inside' ).empty();
				$( '#woocommerce-order-items .inside' ).append( response );
				runTipTip();
				removeOrderItemsLoading();
			}
		});

	});

	$('span.inline_total').closest('.totals_group').find('input').change();

	// Download permissions
	$('.order_download_permissions')
		.on('click', 'button.grant_access', function(){
			var products = $('select#grant_access_id').val();
				if (!products) return;

			$('.order_download_permissions').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_admin_meta_boxes.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			var data = {
				action: 		'woocommerce_grant_access_to_download',
				product_ids: 	products,
				loop:			$('.order_download_permissions .wc-metabox').size(),
				order_id: 		woocommerce_admin_meta_boxes.post_id,
				security: 		woocommerce_admin_meta_boxes.grant_access_nonce,
			};

			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {

				if ( response ) {
					$('.order_download_permissions .wc-metaboxes').append( response );
				} else {
					alert( woocommerce_admin_meta_boxes.i18n_download_permission_fail );
				}

				$( ".date-picker" ).datepicker({
					dateFormat: "yy-mm-dd",
					numberOfMonths: 1,
					showButtonPanel: true,
					showOn: "button",
					buttonImage: woocommerce_admin_meta_boxes.calendar_image,
					buttonImageOnly: true
				});
				$('#grant_access_id').val('').trigger('chosen:updated');
				$('.order_download_permissions').unblock();

			});

			return false;
		})
		.on('click', 'button.revoke_access', function(e){
			e.preventDefault();
			var answer = confirm( woocommerce_admin_meta_boxes.i18n_permission_revoke );
			if ( answer ) {
				var el = $(this).parent().parent();
				var product = $(this).attr('rel').split(",")[0];
				var file = $(this).attr('rel').split(",")[1];

				if ( product > 0 ) {
					$(el).block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_admin_meta_boxes.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

					var data = {
						action: 		'woocommerce_revoke_access_to_download',
						product_id: 	product,
						download_id:	file,
						order_id: 		woocommerce_admin_meta_boxes.post_id,
						security: 		woocommerce_admin_meta_boxes.revoke_access_nonce,
					};

					$.post( woocommerce_admin_meta_boxes.ajax_url, data, function(response) {
						// Success
						$(el).fadeOut('300', function(){
							$(el).remove();
						});
					});

				} else {
					$(el).fadeOut('300', function(){
						$(el).remove();
					});
				}
			}
			return false;
		});

	$('button.load_customer_billing').click(function(){
		var answer = confirm(woocommerce_admin_meta_boxes.load_billing);
		if (answer){

			// Get user ID to load data for
			var user_id = $('#customer_user').val();

			if (!user_id) {
				alert(woocommerce_admin_meta_boxes.no_customer_selected);
				return false;
			}

			var data = {
				user_id: 			user_id,
				type_to_load: 		'billing',
				action: 			'woocommerce_get_customer_details',
				security: 			woocommerce_admin_meta_boxes.get_customer_details_nonce
			};

			$(this).closest('.edit_address').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_admin_meta_boxes.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			$.ajax({
				url: woocommerce_admin_meta_boxes.ajax_url,
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

		var answer = confirm(woocommerce_admin_meta_boxes.load_shipping);
		if (answer){

			// Get user ID to load data for
			var user_id = $('#customer_user').val();

			if (!user_id) {
				alert(woocommerce_admin_meta_boxes.no_customer_selected);
				return false;
			}

			var data = {
				user_id: 			user_id,
				type_to_load: 		'shipping',
				action: 			'woocommerce_get_customer_details',
				security: 			woocommerce_admin_meta_boxes.get_customer_details_nonce
			};

			$(this).closest('.edit_address').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_admin_meta_boxes.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			$.ajax({
				url: woocommerce_admin_meta_boxes.ajax_url,
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
		var answer = confirm(woocommerce_admin_meta_boxes.copy_billing);
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

	$('.totals_group').on('click','a.add_total_row',function(){
		$(this).closest('.totals_group').find('.total_rows').append( $(this).data( 'row' ) );
		return false;
	});

	$('.total_rows').on('click','a.delete_total_row',function(){
		$row = $(this).closest('.total_row');

		var row_id = $row.attr( 'data-order_item_id' );

		if ( row_id ) {
			$row.append('<input type="hidden" name="delete_order_item_id[]" value="' + row_id + '" />').hide();
		} else {
			$row.remove();
		}

		return false;
	});

	// Order notes
	$('#woocommerce-order-notes')
		.on( 'click', 'a.add_note', function() {
			if ( ! $('textarea#add_order_note').val() ) return;

			$('#woocommerce-order-notes').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_admin_meta_boxes.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });
			var data = {
				action: 		'woocommerce_add_order_note',
				post_id:		woocommerce_admin_meta_boxes.post_id,
				note: 			$('textarea#add_order_note').val(),
				note_type:		$('select#order_note_type').val(),
				security: 		woocommerce_admin_meta_boxes.add_order_note_nonce,
			};

			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function(response) {
				$('ul.order_notes').prepend( response );
				$('#woocommerce-order-notes').unblock();
				$('#add_order_note').val('');
			});

			return false;

		})
		.on( 'click', 'a.delete_note', function() {
			var note = $(this).closest('li.note');
			$(note).block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_admin_meta_boxes.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			var data = {
				action: 		'woocommerce_delete_order_note',
				note_id:		$(note).attr('rel'),
				security: 		woocommerce_admin_meta_boxes.delete_order_note_nonce,
			};

			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function(response) {
				$(note).remove();
			});

			return false;
		});
});
