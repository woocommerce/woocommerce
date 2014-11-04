/*global woocommerce_admin_meta_boxes, woocommerce_admin, accounting */
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
	$( '#woocommerce-order-actions input, #woocommerce-order-actions a' ).click(function() {
		window.onbeforeunload = '';
	});

	$( 'a.edit_address' ).click(function( e ) {
		e.preventDefault();
		$( this ).hide();
		$( this ).closest( '.order_data_column' ).find( 'div.address' ).hide();
		$( this ).closest( '.order_data_column' ).find( 'div.edit_address' ).show();
	});

	$( 'body' )
		.on( 'click', 'a.edit-order-item', function() {
			$( this ).closest( 'tr' ).find( '.view' ).hide();
			$( this ).closest( 'tr' ).find( '.edit' ).show();
			$( this ).hide();
			$( 'button.add-line-item' ).click();
			$( 'button.cancel-action' ).attr( 'data-reload', true );

			return false;
		})
		.on( 'click', '.woocommerce_order_items a.delete-order-item', function() {
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
					success: function( response ) {
						$item.remove();
						removeOrderItemsLoading();
					}
				});
			}
			return false;
		})
		.on( 'click', '#order_refunds .delete_refund', function() {
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
					success: function( response ) {
						loadOrderItems();
					}
				});
			}
			return false;
		})
		// When the qty is changed, increase or decrease costs
		.on( 'change', '.woocommerce_order_items input.quantity', function() {
			var $row          = $( this ).closest( 'tr.item' );
			var qty           = $( this ).val();
			var o_qty         = $( this ).attr( 'data-qty' );
			var line_total    = $( 'input.line_total', $row );
			var line_subtotal = $( 'input.line_subtotal', $row );

			// Totals
			var unit_total = accounting.unformat( line_total.attr( 'data-total' ), woocommerce_admin.mon_decimal_point ) / o_qty;
			line_total.val(
				parseFloat( accounting.formatNumber( unit_total * qty, woocommerce_admin_meta_boxes.rounding_precision, '' ) )
					.toString()
					.replace( '.', woocommerce_admin.mon_decimal_point )
			);

			var unit_subtotal = accounting.unformat( line_subtotal.attr( 'data-subtotal' ), woocommerce_admin.mon_decimal_point ) / o_qty;
			line_subtotal.val(
				parseFloat( accounting.formatNumber( unit_subtotal * qty, woocommerce_admin_meta_boxes.rounding_precision, '' ) )
					.toString()
					.replace( '.', woocommerce_admin.mon_decimal_point )
			);

			// Taxes
			$( 'td.line_tax', $row ).each(function() {
				var line_total_tax = $( 'input.line_tax', $( this ) );
				var unit_total_tax = accounting.unformat( line_total_tax.attr( 'data-total_tax' ), woocommerce_admin.mon_decimal_point ) / o_qty;
				if ( 0 < unit_total_tax ) {
					line_total_tax.val(
						parseFloat( accounting.formatNumber( unit_total_tax * qty, woocommerce_admin_meta_boxes.rounding_precision, '' ) )
							.toString()
							.replace( '.', woocommerce_admin.mon_decimal_point )
					);
				}

				var line_subtotal_tax = $( 'input.line_subtotal_tax', $( this ) );
				var unit_subtotal_tax = accounting.unformat( line_subtotal_tax.attr( 'data-subtotal_tax' ), woocommerce_admin.mon_decimal_point ) / o_qty;
				if ( 0 < unit_subtotal_tax ) {
					line_subtotal_tax.val(
						parseFloat( accounting.formatNumber( unit_subtotal_tax * qty, woocommerce_admin_meta_boxes.rounding_precision, '' ) )
							.toString()
							.replace( '.', woocommerce_admin.mon_decimal_point )
					);
				}
			});

			$( this ).trigger( 'quantity_changed' );
		})
		// When the refund qty is changed, increase or decrease costs
		.on( 'change', '.woocommerce_order_items input.refund_order_item_qty', function() {
			var $row              = $( this ).closest( 'tr.item' );
			var qty               = $row.find( 'input.quantity' ).val();
			var refund_qty        = $( this ).val();
			var line_total        = $( 'input.line_total', $row );
			var refund_line_total = $( 'input.refund_line_total', $row );

			// Totals
			var unit_total = accounting.unformat( line_total.attr( 'data-total' ), woocommerce_admin.mon_decimal_point ) / qty;

			refund_line_total.val(
				parseFloat( accounting.formatNumber( unit_total * refund_qty, woocommerce_admin_meta_boxes.rounding_precision, '' ) )
					.toString()
					.replace( '.', woocommerce_admin.mon_decimal_point )
			);

			// Taxes
			$( 'td.line_tax', $row ).each( function() {
				var line_total_tax        = $( 'input.line_tax', $( this ) );
				var refund_line_total_tax = $( 'input.refund_line_tax', $( this ) );
				var unit_total_tax = accounting.unformat( line_total_tax.attr( 'data-total_tax' ), woocommerce_admin.mon_decimal_point ) / qty;

				if ( 0 < unit_total_tax ) {
					refund_line_total_tax.val(
						parseFloat( accounting.formatNumber( unit_total_tax * refund_qty, woocommerce_admin_meta_boxes.rounding_precision, '' ) )
							.toString()
							.replace( '.', woocommerce_admin.mon_decimal_point )
					);
				}
			});

			$( this ).trigger( 'refund_quantity_changed' );
		})
		.on( 'change', '.woocommerce_order_items .refund input', function() {
			var refund_amount = 0;
			var $items        = $( '.woocommerce_order_items' ).find( 'tr.item, tr.fee, tr.shipping' );

			$items.each(function() {
				var $row               = $( this );
				var refund_cost_fields = $row.find( '.refund input:not(.refund_order_item_qty)' );

				refund_cost_fields.each(function( index, el ) {
					refund_amount += parseFloat( accounting.unformat( $( el ).val() || 0, woocommerce_admin.mon_decimal_point ) );
				});
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
		.on( 'click', '.woocommerce_order_items button.add_order_item_meta', function() {

			var $button = $( this );
			var $item = $button.closest( 'tr.item' );

			var data = {
				order_item_id: $item.attr( 'data-order_item_id' ),
				action:        'woocommerce_add_order_item_meta',
				security:      woocommerce_admin_meta_boxes.order_item_nonce
			};

			addOrderItemsLoading();

			$.ajax({
				url: woocommerce_admin_meta_boxes.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					$item.find('tbody.meta_items').append( response );
					removeOrderItemsLoading();
				}
			});

			return false;
		})
		// Remove some meta from a line item
		.on( 'click', '.woocommerce_order_items button.remove_order_item_meta', function() {
			if ( window.confirm( woocommerce_admin_meta_boxes.remove_item_meta ) ) {
				var $row = $( this ).closest( 'tr' );

				var data = {
					meta_id:  $row.attr( 'data-meta_id' ),
					action:   'woocommerce_remove_order_item_meta',
					security: woocommerce_admin_meta_boxes.order_item_nonce
				};

				addOrderItemsLoading();

				$.ajax({
					url: woocommerce_admin_meta_boxes.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						$row.hide();
						removeOrderItemsLoading();
					}
				});
			}
			return false;
		})
		// Subtotal/total inputs
		.on( 'keyup', '.woocommerce_order_items .split-input input:eq(0)', function() {
			var $subtotal = $( this ).next();
			if ( $subtotal.val() === '' || $subtotal.is( '.match-total' ) ) {
				$subtotal.val( $( this ).val() ).addClass( 'match-total' );
			}
		})
		.on( 'keyup', '.woocommerce_order_items .split-input input:eq(0)', function() {
			$( this ).removeClass( 'match-total' );
		});

	$( '#woocommerce-order-items' )
		.on( 'click', 'button.add-line-item', function() {
			$( 'div.wc-order-add-item' ).slideDown();
			$( 'div.wc-order-bulk-actions' ).slideUp();

			return false;
		})
		.on( 'click', 'button.refund-items', function() {
			$( 'div.wc-order-refund-items' ).slideDown();
			$( 'div.wc-order-bulk-actions' ).slideUp();
			$( 'div.wc-order-totals-items' ).slideUp();
			$( '#woocommerce-order-items div.refund' ).show();
			$( '.wc-order-edit-line-item .wc-order-edit-line-item-actions' ).hide();

			return false;
		})
		.on( 'click', '.cancel-action', function() {
			$( this ).closest( 'div.wc-order-data-row' ).slideUp();
			$( 'div.wc-order-bulk-actions' ).slideDown();
			$( 'div.wc-order-totals-items' ).slideDown();
			$( '#woocommerce-order-items div.refund' ).hide();
			$( '.wc-order-edit-line-item .wc-order-edit-line-item-actions' ).show();

			// Reload the items
			if ( 'true' === $( this ).attr( 'data-reload' ) ) {
				loadOrderItems();
			}

			return false;
		})
		.on( 'click', 'button.add-order-item', function() {
			$( this ).WCBackboneModal({
				template: '#wc-modal-add-products'
			});

			return false;
		})
		.on( 'click', 'button.add-order-fee', function() {
			addOrderItemsLoading();

			var data = {
				action:   'woocommerce_add_order_fee',
				order_id: woocommerce_admin_meta_boxes.post_id,
				security: woocommerce_admin_meta_boxes.order_item_nonce
			};

			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
				$( 'table.woocommerce_order_items tbody#order_fee_line_items' ).append( response );
				removeOrderItemsLoading();
			});

			return false;
		})
		.on( 'click', 'button.add-order-shipping', function() {
			addOrderItemsLoading();

			var data = {
				action:   'woocommerce_add_order_shipping',
				order_id: woocommerce_admin_meta_boxes.post_id,
				security: woocommerce_admin_meta_boxes.order_item_nonce
			};

			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
				$( 'table.woocommerce_order_items tbody#order_shipping_line_items' ).append( response );
				removeOrderItemsLoading();
			});

			return false;
		})
		.on( 'click', 'button.add-order-tax', function() {
			$( this ).WCBackboneModal({
				template: '#wc-modal-add-tax'
			});
			return false;
		})
		// Bulk actions for line items
		.on( 'click', 'input.check-column', function() {
			if ( $( this ).is( ':checked' ) ) {
				$( '#woocommerce-order-items' ).find( '.check-column input' ).attr( 'checked', 'checked' );
			} else {
				$( '#woocommerce-order-items' ).find( '.check-column input' ).removeAttr( 'checked' );
			}
		})
		.on( 'click', '.do_bulk_action', function() {
			var action        = $( this ).closest( '.bulk-actions' ).find( 'select' ).val();
			var selected_rows = $( '#woocommerce-order-items' ).find( '.check-column input:checked' );
			var item_ids      = [];

			$( selected_rows ).each( function() {
				var $item = $( this ).closest( 'tr' );

				if ( $item.attr( 'data-order_item_id' ) ) {
					item_ids.push( $item.attr( 'data-order_item_id' ) );
				}
			} );

			if ( item_ids.length === 0 ) {
				window.alert( woocommerce_admin_meta_boxes.i18n_select_items );
				return;
			}

			if ( action === 'delete' ) {
				if ( window.confirm( woocommerce_admin_meta_boxes.remove_item_notice ) ) {

					addOrderItemsLoading();

					var data = {
						order_item_ids: item_ids,
						action:         'woocommerce_remove_order_item',
						security:       woocommerce_admin_meta_boxes.order_item_nonce
					};

					$.ajax({
						url: woocommerce_admin_meta_boxes.ajax_url,
						data: data,
						type: 'POST',
						success: function( response ) {
							$( selected_rows ).each(function() {
								$( this ).closest( 'tr' ).remove();
							});
							removeOrderItemsLoading();
						}
					});
				}

			} else if ( action === 'reduce_stock' ) {

				addOrderItemsLoading();

				var quantities = {};

				$( selected_rows ).each(function() {

					var $item = $( this ).closest( 'tr.item, tr.fee' );
					var $qty  = $item.find( 'input.quantity' );

					quantities[ $item.attr( 'data-order_item_id' ) ] = $qty.val();
				});

				var data = {
					order_id:       woocommerce_admin_meta_boxes.post_id,
					order_item_ids: item_ids,
					order_item_qty: quantities,
					action:         'woocommerce_reduce_order_item_stock',
					security:       woocommerce_admin_meta_boxes.order_item_nonce
				};

				$.ajax({
					url: woocommerce_admin_meta_boxes.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						window.alert( response );
						removeOrderItemsLoading();
					}
				} );

			} else if ( action === 'increase_stock' ) {

				addOrderItemsLoading();

				var quantities = {};

				$( selected_rows ).each(function() {

					var $item = $( this ).closest( 'tr.item, tr.fee' );
					var $qty  = $item.find( 'input.quantity' );

					quantities[ $item.attr( 'data-order_item_id' ) ] = $qty.val();
				});

				var data = {
					order_id:       woocommerce_admin_meta_boxes.post_id,
					order_item_ids: item_ids,
					order_item_qty: quantities,
					action:         'woocommerce_increase_order_item_stock',
					security:       woocommerce_admin_meta_boxes.order_item_nonce
				};

				$.ajax({
					url: woocommerce_admin_meta_boxes.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						window.alert( response );
						removeOrderItemsLoading();
					}
				});
			}

			return false;
		})
		.on( 'click', 'button.calculate-action', function() {
			if ( window.confirm( woocommerce_admin_meta_boxes.calc_totals ) ) {

				addOrderItemsLoading();

				// Get row totals
				var line_totals    = 0;
				var tax            = 0;
				var shipping       = 0;
				var order_discount = $( '#_order_discount' ).val() || '0';

				order_discount = accounting.unformat( order_discount.replace( ',', '.' ) );

				$( '.woocommerce_order_items tr.shipping input.line_total' ).each(function() {
					var cost  = $( this ).val() || '0';
					cost      = accounting.unformat( cost, woocommerce_admin.mon_decimal_point );
					shipping  = shipping + parseFloat( cost );
				});

				$( '.woocommerce_order_items input.line_tax' ).each(function() {
					var cost = $( this ).val() || '0';
					cost     = accounting.unformat( cost, woocommerce_admin.mon_decimal_point );
					tax      = tax + parseFloat( cost );
				});

				$( '.woocommerce_order_items tr.item, .woocommerce_order_items tr.fee' ).each(function() {
					var line_total = $( this ).find( 'input.line_total' ).val() || '0';
					line_totals    = line_totals + accounting.unformat( line_total.replace( ',', '.' ) );
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
		.on( 'click', 'button.save-action', function() {
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

			$( this ).trigger( 'items_saved' );

			return false;
		})
		.on( 'click', 'a.delete-order-tax', function() {
			if ( window.confirm( woocommerce_admin_meta_boxes.i18n_delete_tax ) ) {
				addOrderItemsLoading();

				var data = {
					action:   'woocommerce_remove_order_tax',
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
			}
			return false;
		})
		.on( 'click', 'button.calculate-tax-action', function() {
			if ( window.confirm( woocommerce_admin_meta_boxes.calc_line_taxes ) ) {
				addOrderItemsLoading();

				var shipping_country = $( '#_shipping_country' ).val();
				var billing_country  = $( '#_billing_country' ).val();
				var country          = woocommerce_admin_meta_boxes.base_country;
				var state            = '';
				var postcode         = '';
				var city             = '';

				if ( shipping_country ) {
					country  = shipping_country;
					state    = $( '#_shipping_state' ).val();
					postcode = $( '#_shipping_postcode' ).val();
					city     = $( '#_shipping_city' ).val();
				} else if ( billing_country ) {
					country  = billing_country;
					state    = $( '#_billing_state' ).val();
					postcode = $( '#_billing_postcode' ).val();
					city     = $( '#_billing_city' ).val();
				}

				var data = {
					action:   'woocommerce_calc_line_taxes',
					order_id: woocommerce_admin_meta_boxes.post_id,
					items:    $( 'table.woocommerce_order_items :input[name], .wc-order-totals-items :input[name]' ).serialize(),
					country:  country,
					state:    state,
					postcode: postcode,
					city:     city,
					security: woocommerce_admin_meta_boxes.calc_totals_nonce
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
			}

			return false;
		});

	// Refund actions
	$( 'body' )
		.on( 'change keyup', '.wc-order-refund-items #refund_amount', function() {
			var total = accounting.unformat( $( this ).val(), woocommerce_admin.mon_decimal_point );

			$( 'button .wc-order-refund-amount .amount' ).text( accounting.formatMoney( total, {
				symbol:    woocommerce_admin_meta_boxes.currency_format_symbol,
				decimal:   woocommerce_admin_meta_boxes.currency_format_decimal_sep,
				thousand:  woocommerce_admin_meta_boxes.currency_format_thousand_sep,
				precision: woocommerce_admin_meta_boxes.currency_format_num_decimals,
				format:    woocommerce_admin_meta_boxes.currency_format
			} ) );
		})
		.on( 'click', '.wc-order-refund-items button.do-api-refund, .wc-order-refund-items button.do-manual-refund', function() {
			addOrderItemsLoading();

			if ( window.confirm( woocommerce_admin_meta_boxes.i18n_do_refund ) ) {
				var refund_amount = $( 'input#refund_amount' ).val();
				var refund_reason = $( 'input#refund_reason' ).val();

				// Get line item refunds
				var line_item_qtys       = {};
				var line_item_totals     = {};
				var line_item_tax_totals = {};

				$( '.refund input.refund_order_item_qty' ).each(function( index, item ) {
					if ( $( item ).closest( 'tr' ).data( 'order_item_id' ) ) {
						if ( item.value ) {
							line_item_qtys[ $( item ).closest( 'tr' ).data( 'order_item_id' ) ] = item.value;
						}
					}
				});

				$( '.refund input.refund_line_total' ).each(function( index, item ) {
					if ( $( item ).closest( 'tr' ).data( 'order_item_id' ) ) {
						line_item_totals[ $( item ).closest( 'tr' ).data( 'order_item_id' ) ] = accounting.unformat( item.value, woocommerce_admin.mon_decimal_point );
					}
				});

				$( '.refund input.refund_line_tax' ).each(function( index, item ) {
					if ( $( item ).closest( 'tr' ).data( 'order_item_id' ) ) {
						var tax_id = $( item ).data( 'tax_id' );

						if ( ! line_item_tax_totals[ $( item ).closest( 'tr' ).data( 'order_item_id' ) ] ) {
							line_item_tax_totals[ $( item ).closest( 'tr' ).data( 'order_item_id' ) ] = {};
						}

						line_item_tax_totals[ $( item ).closest( 'tr' ).data( 'order_item_id' ) ][ tax_id ] = accounting.unformat( item.value, woocommerce_admin.mon_decimal_point );
					}
				});

				var data = {
					action:                 'woocommerce_refund_line_items',
					order_id:               woocommerce_admin_meta_boxes.post_id,
					refund_amount:          refund_amount,
					refund_reason:          refund_reason,
					line_item_qtys:         JSON.stringify( line_item_qtys, null, '' ),
					line_item_totals:       JSON.stringify( line_item_totals, null, '' ),
					line_item_tax_totals:   JSON.stringify( line_item_tax_totals, null, '' ),
					api_refund:             $( this ).is( '.do-api-refund' ),
					restock_refunded_items: $( '#restock_refunded_items:checked' ).size() ? 'true' : 'false',
					security:               woocommerce_admin_meta_boxes.order_item_nonce
				};

				$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
					if ( response === true ) {
						loadOrderItems();
					} else if ( response.error ) {
						window.alert( response.error );
						removeOrderItemsLoading();
					} else {
						console.log(response);
					}
				});
			} else {
				removeOrderItemsLoading();
			}
		});

	// Adds new products in order items
	$( 'body' ).on( 'wc_backbone_modal_response', function( e, target ) {
		if ( '#wc-modal-add-products' !== target ) {
			return;
		}

		var add_item_ids = $( 'select#add_item_id' ).val();

		if ( add_item_ids ) {

			var count = add_item_ids.length;

			addOrderItemsLoading();

			$.each( add_item_ids, function( index, value ) {

				var data = {
					action:      'woocommerce_add_order_item',
					item_to_add: value,
					order_id:    woocommerce_admin_meta_boxes.post_id,
					security:    woocommerce_admin_meta_boxes.order_item_nonce
				};

				$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {

					$( 'table.woocommerce_order_items tbody#order_line_items' ).append( response );

					if ( !--count ) {
						$( 'select#add_item_id, #add_item_id_chosen .chosen-choices' ).css( 'border-color', '' ).val( '' );
						runTipTip();
						$( 'select#add_item_id' ).trigger( 'chosen:updated' );
						removeOrderItemsLoading();
					}
				});

			});

		} else {
			$( 'select#add_item_id, #add_item_id_chosen .chosen-choices' ).css( 'border-color', 'red' );
		}
	});

	// Adds new tax in order items
	$( 'body' ).on( 'wc_backbone_modal_response', function( e, target ) {
		if ( '#wc-modal-add-tax' !== target ) {
			return;
		}

		var manual_rate_id = $( '#manual_tax_rate_id' ).val();
		var rate_id        = $( 'input[name=add_order_tax]:checked' ).val();

		if ( manual_rate_id ) {
			rate_id = manual_rate_id;
		}

		if ( ! rate_id ) {
			return false;
		}

		var rates = $( '.order-tax-id' ).map( function() {
				return $( this ).val();
		}).get();

		// Test if already exists
		if ( -1 === $.inArray( rate_id, rates ) ) {
			addOrderItemsLoading();

			var data = {
				action:   'woocommerce_add_order_tax',
				rate_id:  rate_id,
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
		} else {
			window.alert( woocommerce_admin_meta_boxes.i18n_tax_rate_already_exists );
		}
	});

	$( 'span.inline_total' ).closest( '.totals_group' ).find( 'input' ).change();

	// Download permissions
	$( '.order_download_permissions' )
		.on( 'click', 'button.grant_access', function() {
			var products = $( 'select#grant_access_id' ).val();

			if ( ! products ) {
				return;
			}

			$( '.order_download_permissions' ).block({
				message: null,
				overlayCSS: {
					background: '#fff url(' + woocommerce_admin_meta_boxes.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
					opacity: 0.6
				}
			});

			var data = {
				action:      'woocommerce_grant_access_to_download',
				product_ids: products,
				loop:        $('.order_download_permissions .wc-metabox').size(),
				order_id:    woocommerce_admin_meta_boxes.post_id,
				security:    woocommerce_admin_meta_boxes.grant_access_nonce,
			};

			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {

				if ( response ) {
					$( '.order_download_permissions .wc-metaboxes' ).append( response );
				} else {
					window.alert( woocommerce_admin_meta_boxes.i18n_download_permission_fail );
				}

				$( '.date-picker' ).datepicker({
					dateFormat:      'yy-mm-dd',
					numberOfMonths:  1,
					showButtonPanel: true,
					showOn:          'button',
					buttonImage:     woocommerce_admin_meta_boxes.calendar_image,
					buttonImageOnly: true
				});

				$( '#grant_access_id' ).val( '' ).trigger( 'chosen:updated' );
				$( '.order_download_permissions' ).unblock();

			});

			return false;
		})
		.on( 'click', 'button.revoke_access', function() {
			if ( window.confirm( woocommerce_admin_meta_boxes.i18n_permission_revoke ) ) {
				var el      = $( this ).parent().parent();
				var product = $( this ).attr( 'rel' ).split( ',' )[0];
				var file    = $( this ).attr( 'rel' ).split( ',' )[1];

				if ( product > 0 ) {
					$( el ).block({
						message: null,
						overlayCSS: {
							background: '#fff url(' + woocommerce_admin_meta_boxes.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
							opacity: 0.6
						}
					});

					var data = {
						action:      'woocommerce_revoke_access_to_download',
						product_id:  product,
						download_id: file,
						order_id:    woocommerce_admin_meta_boxes.post_id,
						security:    woocommerce_admin_meta_boxes.revoke_access_nonce,
					};

					$.post( woocommerce_admin_meta_boxes.ajax_url, data, function ( response ) {
						// Success
						$( el ).fadeOut( '300', function () {
							$( el ).remove();
						});
					});

				} else {
					$( el ).fadeOut( '300', function () {
						$( el ).remove();
					});
				}
			}
			return false;
		});

	$( 'button.load_customer_billing' ).on( 'click', function() {
		if ( window.confirm( woocommerce_admin_meta_boxes.load_billing ) ) {

			// Get user ID to load data for
			var user_id = $( '#customer_user' ).val();

			if ( ! user_id ) {
				window.alert( woocommerce_admin_meta_boxes.no_customer_selected );
				return false;
			}

			var data = {
				user_id:      user_id,
				type_to_load: 'billing',
				action:       'woocommerce_get_customer_details',
				security:     woocommerce_admin_meta_boxes.get_customer_details_nonce
			};

			$( this ).closest( '.edit_address' ).block({
				message: null,
				overlayCSS: {
					background: '#fff url(' + woocommerce_admin_meta_boxes.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
					opacity: 0.6
				}
			});

			$.ajax({
				url: woocommerce_admin_meta_boxes.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					var info = response;

					if ( info ) {
						$( 'input#_billing_first_name' ).val( info.billing_first_name );
						$( 'input#_billing_last_name' ).val( info.billing_last_name );
						$( 'input#_billing_company' ).val( info.billing_company );
						$( 'input#_billing_address_1' ).val( info.billing_address_1 );
						$( 'input#_billing_address_2' ).val( info.billing_address_2 );
						$( 'input#_billing_city' ).val( info.billing_city );
						$( 'input#_billing_postcode' ).val( info.billing_postcode );
						$( '#_billing_country' ).val( info.billing_country );
						$( 'input#_billing_state' ).val( info.billing_state );
						$( 'input#_billing_email' ).val( info.billing_email );
						$( 'input#_billing_phone' ).val( info.billing_phone );
					}

					$( '.edit_address' ).unblock();
				}
			});
		}
		return false;
	});

	$( 'button.load_customer_shipping' ).on( 'click', function() {
		if ( window.confirm( woocommerce_admin_meta_boxes.load_shipping ) ) {

			// Get user ID to load data for
			var user_id = $( '#customer_user' ).val();

			if ( ! user_id ) {
				window.alert( woocommerce_admin_meta_boxes.no_customer_selected );
				return false;
			}

			var data = {
				user_id:      user_id,
				type_to_load: 'shipping',
				action:       'woocommerce_get_customer_details',
				security:     woocommerce_admin_meta_boxes.get_customer_details_nonce
			};

			$( this ).closest( '.edit_address' ).block({
				message: null,
				overlayCSS: {
					background: '#fff url(' + woocommerce_admin_meta_boxes.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
					opacity: 0.6
				}
			});

			$.ajax({
				url: woocommerce_admin_meta_boxes.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					var info = response;

					if ( info ) {
						$( 'input#_shipping_first_name' ).val( info.shipping_first_name );
						$( 'input#_shipping_last_name' ).val( info.shipping_last_name );
						$( 'input#_shipping_company' ).val( info.shipping_company );
						$( 'input#_shipping_address_1' ).val( info.shipping_address_1 );
						$( 'input#_shipping_address_2' ).val( info.shipping_address_2 );
						$( 'input#_shipping_city' ).val( info.shipping_city );
						$( 'input#_shipping_postcode' ).val( info.shipping_postcode );
						$( '#_shipping_country' ).val( info.shipping_country );
						$( 'input#_shipping_state' ).val( info.shipping_state );
					}

					$( '.edit_address' ).unblock();
				}
			});
		}
		return false;
	});

	$( 'button.billing-same-as-shipping' ).on( 'click', function() {
		if ( window.confirm( woocommerce_admin_meta_boxes.copy_billing ) ) {
			$( 'input#_shipping_first_name' ).val( $( 'input#_billing_first_name' ).val() );
			$( 'input#_shipping_last_name' ).val( $( 'input#_billing_last_name' ).val() );
			$( 'input#_shipping_company' ).val( $( 'input#_billing_company' ).val() );
			$( 'input#_shipping_address_1' ).val( $( 'input#_billing_address_1' ).val() );
			$( 'input#_shipping_address_2' ).val( $( 'input#_billing_address_2' ).val() );
			$( 'input#_shipping_city' ).val( $( 'input#_billing_city' ).val() );
			$( 'input#_shipping_postcode' ).val( $( 'input#_billing_postcode' ).val() );
			$( '#_shipping_country' ).val( $( '#_billing_country' ).val() );
			$( 'input#_shipping_state' ).val( $( 'input#_billing_state' ).val() );
		}
		return false;
	});

	$( '.totals_group' ).on( 'click', 'a.add_total_row', function() {
		$( this ).closest( '.totals_group' ).find( '.total_rows' ).append( $( this ).data( 'row' ) );
		return false;
	});

	$( '.total_rows' ).on( 'click', 'a.delete_total_row', function() {
		var $row   = $( this ).closest( '.total_row' );
		var row_id = $row.attr( 'data-order_item_id' );

		if ( row_id ) {
			$row.append( '<input type="hidden" name="delete_order_item_id[]" value="' + row_id + '" />' ).hide();
		} else {
			$row.remove();
		}

		return false;
	});

	// Order notes
	$( '#woocommerce-order-notes' )
		.on( 'click', 'a.add_note', function() {
			if ( ! $( 'textarea#add_order_note' ).val() ) {
				return;
			}

			$( '#woocommerce-order-notes' ).block({
				message: null,
				overlayCSS: {
					background: '#fff url(' + woocommerce_admin_meta_boxes.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
					opacity: 0.6
				}
			});

			var data = {
				action:    'woocommerce_add_order_note',
				post_id:   woocommerce_admin_meta_boxes.post_id,
				note:      $( 'textarea#add_order_note' ).val(),
				note_type: $( 'select#order_note_type' ).val(),
				security:  woocommerce_admin_meta_boxes.add_order_note_nonce,
			};

			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
				$( 'ul.order_notes' ).prepend( response );
				$( '#woocommerce-order-notes' ).unblock();
				$( '#add_order_note' ).val( '' );
			});

			return false;

		})
		.on( 'click', 'a.delete_note', function() {
			var note = $( this ).closest( 'li.note' );

			$( note ).block({
				message: null,
				overlayCSS: {
					background: '#fff url(' + woocommerce_admin_meta_boxes.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
					opacity: 0.6
				}
			});

			var data = {
				action:   'woocommerce_delete_order_note',
				note_id:  $( note ).attr( 'rel' ),
				security: woocommerce_admin_meta_boxes.delete_order_note_nonce,
			};

			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
				$( note ).remove();
			});

			return false;
		});
});
