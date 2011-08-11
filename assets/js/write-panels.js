jQuery( function($){

	// TABS
	jQuery('ul.tabs').show();
	jQuery('div.panel-wrap').each(function(){
		jQuery('div.panel:not(div.panel:first)', this).hide();
	});
	jQuery('ul.tabs a').click(function(){
		var panel_wrap =  jQuery(this).closest('div.panel-wrap');
		jQuery('ul.tabs li', panel_wrap).removeClass('active');
		jQuery(this).parent().addClass('active');
		jQuery('div.panel', panel_wrap).hide();
		jQuery( jQuery(this).attr('href') ).show();
		return false;
	});
	
	// ORDERS
	
	jQuery('#order_items_list button.remove_row').live('click', function(){
		var answer = confirm(params.remove_item_notice);
		if (answer){
			jQuery(this).parent().parent().remove();
		}
		return false;
	});
	
	jQuery('button.calc_totals').live('click', function(){
		var answer = confirm(params.cart_total);
		if (answer){
			
			var item_count = jQuery('#order_items_list tr.item').size();
			var subtotal = 0;
			var discount = jQuery('input#order_discount').val();
			var shipping = jQuery('input#order_shipping').val();
			var shipping_tax = parseFloat(jQuery('input#order_shipping_tax').val());
			var tax = 0;
			var itemTotal = 0;
			var total = 0;
			
			if (!discount) discount = 0;
			if (!shipping) shipping = 0;
			if (!shipping_tax) shipping_tax = 0;
			
			// Items
			if (item_count>0) {
				for (i=0; i<item_count; i++) {
					
					itemCost 	= jQuery('input[name^=item_cost]:eq(' + i + ')').val();
					itemQty 	= parseInt(jQuery('input[name^=item_quantity]:eq(' + i + ')').val());
					itemTax		= jQuery('input[name^=item_tax_rate]:eq(' + i + ')').val();
					
					if (!itemCost) itemCost = 0;
					if (!itemTax) itemTax = 0;
					
					totalItemTax = 0;
					
					totalItemCost = itemCost * itemQty;
					
					if (itemTax && itemTax>0) {
						
						taxRate = itemTax/100;
						
						itemTaxAmount = ((itemCost * taxRate) * 100 );
						
						itemTaxAmount = itemTaxAmount.toFixed(2);
						
						totalItemTax = Math.round( itemTaxAmount ) / 100;
						
						totalItemTax = totalItemTax * itemQty;
						
					}
					
					itemTotal = itemTotal + totalItemCost;
					
					tax = tax + totalItemTax;
				}
			}
			
			subtotal = itemTotal;
			
			total = parseFloat(subtotal) + parseFloat(tax) - parseFloat(discount) + parseFloat(shipping) + parseFloat(shipping_tax);
			
			if (total < 0 ) total = 0;

			jQuery('input#order_subtotal').val( subtotal.toFixed(2) );
			jQuery('input#order_tax').val( tax.toFixed(2) );
			jQuery('input#order_shipping_tax').val( shipping_tax.toFixed(2) );
			jQuery('input#order_total').val( total.toFixed(2) );

		}
		return false;
	});
	
	jQuery('button.add_shop_order_item').click(function(){
		
		var item_id = jQuery('select.add_item_id').val();
		
		if (item_id) {

			jQuery('table.woocommerce_order_items').block({ message: null, overlayCSS: { background: '#fff url(' + params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });
			
			var data = {
				action: 		'woocommerce_add_order_item',
				item_to_add: 	jQuery('select.add_item_id').val(),
				security: 		params.add_order_item_nonce
			};

			jQuery.post( params.ajax_url, data, function(response) {
				
				jQuery('table.woocommerce_order_items tbody#order_items_list').append( response );
				jQuery('table.woocommerce_order_items').unblock();
				jQuery('select.add_item_id').css('border-color', '').val('');
			
			});

		} else {
			jQuery('select.add_item_id').css('border-color', 'red');
		}

	});
	
	jQuery('button.add_meta').live('click', function(){
		
		jQuery(this).parent().parent().parent().parent().append('<tr><td><input type="text" name="meta_name[][]" placeholder="' + params.meta_name + '" /></td><td><input type="text" name="meta_value[][]" placeholder="' + params.meta_value + '" /></td></tr>');
		
	});
	
	jQuery('button.billing-same-as-shipping').live('click', function(){
		var answer = confirm(params.copy_billing);
		if (answer){
			jQuery('input#shipping_first_name').val( jQuery('input#billing_first_name').val() );
			jQuery('input#shipping_last_name').val( jQuery('input#billing_last_name').val() );
			jQuery('input#shipping_company').val( jQuery('input#billing_company').val() );
			jQuery('input#shipping_address_1').val( jQuery('input#billing_address_1').val() );
			jQuery('input#shipping_address_2').val( jQuery('input#billing_address_2').val() );
			jQuery('input#shipping_city').val( jQuery('input#billing_city').val() );
			jQuery('input#shipping_postcode').val( jQuery('input#billing_postcode').val() );
			jQuery('input#shipping_country').val( jQuery('input#billing_country').val() );
			jQuery('input#shipping_state').val( jQuery('input#billing_state').val() );			
		}
		return false;
	});
	
	// PRODUCT TYPE SPECIFIC OPTIONS
	$('select#product-type').change(function(){
		
		// Get value
		var select_val = jQuery(this).val();
		
		// Hide options
		$('#woocommerce-product-type-options .inside > div').hide();
		$('#'+select_val+'_product_options').show();
		
		// Show option
		if (select_val=='variable') {
			jQuery('.inventory_tab, .pricing_tab').show();
			jQuery('.menu_order_field, .parent_id_field').val('').hide();
		} else if (select_val=='simple') {
			jQuery('.inventory_tab, .pricing_tab').show();
			jQuery('.menu_order_field, .parent_id_field').show();
		} else if (select_val=='grouped') {
			jQuery('.inventory_tab, .pricing_tab').hide();
			jQuery('.menu_order_field, .parent_id_field').val('').hide();
		} else if (select_val=='downloadable') {
			jQuery('.inventory_tab, .pricing_tab').show();
			jQuery('.menu_order_field, .parent_id_field').show();
		} else if (select_val=='virtual') {
			jQuery('.inventory_tab, .pricing_tab').show();
			jQuery('.menu_order_field, .parent_id_field').show();
		}
		
		$('body').trigger('woocommerce-product-type-change', select_val, $(this) );
		
	}).change();

	// STOCK OPTIONS
	jQuery('input#manage_stock').change(function(){
		if (jQuery(this).is(':checked')) jQuery('div.stock_fields').show();
		else jQuery('div.stock_fields').hide();
	}).change();
	
	
	// DATE PICKER FIELDS
	Date.firstDayOfWeek = 1;
	Date.format = 'yyyy-mm-dd';
	jQuery('.date-pick').datePicker();
	jQuery('#sale_price_dates_from').bind(
		'dpClosed',
		function(e, selectedDates)
		{
			var d = selectedDates[0];
			if (d) {
				d = new Date(d);
				jQuery('#sale_price_dates_to').dpSetStartDate(d.addDays(1).asString());
			}
		}
	);
	jQuery('#sale_price_dates_to').bind(
		'dpClosed',
		function(e, selectedDates)
		{
			var d = selectedDates[0];
			if (d) {
				d = new Date(d);
				jQuery('#sale_price_dates_from').dpSetEndDate(d.addDays(-1).asString());
			}
		}
	);
	
	
	// ATTRIBUTE TABLES
	
		// Initial order
		var woocommerce_attributes_table_items = jQuery('#attributes_list').children('tr').get();
		woocommerce_attributes_table_items.sort(function(a, b) {
		   var compA = jQuery(a).attr('rel');
		   var compB = jQuery(b).attr('rel');
		   return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
		})
		jQuery(woocommerce_attributes_table_items).each( function(idx, itm) { jQuery('#attributes_list').append(itm); } );
		
		// Show
		function show_attribute_table() {
			jQuery('table.woocommerce_attributes, table.woocommerce_variable_attributes').each(function(){
				if (jQuery('tbody tr', this).size()==0) 
					jQuery(this).parent().hide();
				else 
					jQuery(this).parent().show();
			});
		}
		show_attribute_table();
		
		function row_indexes() {
			jQuery('#attributes_list tr').each(function(index, el){ jQuery('.attribute_position', el).val( parseInt( jQuery(el).index('#attributes_list tr') ) ); });
		};
		
		// Add rows
		jQuery('button.add_attribute').click(function(){
			
			var size = jQuery('table.woocommerce_attributes tbody tr').size();
			
			var attribute_type = jQuery('select.attribute_taxonomy').val();
			
			if (!attribute_type) {
				
				// Add custom attribute row
				jQuery('table.woocommerce_attributes tbody').append('<tr><td class="center"><button type="button" class="button move_up">&uarr;</button><button type="button" class="move_down button">&darr;</button><input type="hidden" name="attribute_position[' + size + ']" class="attribute_position" value="' + size + '" /></td><td><input type="text" name="attribute_names[' + size + ']" /><input type="hidden" name="attribute_is_taxonomy[' + size + ']" value="0" /></td><td><input type="text" name="attribute_values[' + size + ']" /></td><td class="center"><input type="checkbox" checked="checked" name="attribute_visibility[' + size + ']" value="1" /></td><td class="center"><input type="checkbox" name="attribute_variation[' + size + ']" value="1" /></td><td class="center"><button type="button" class="remove_row button">&times;</button></td></tr>');
				
			} else {
				
				// Reveal taxonomy row
				var thisrow = jQuery('table.woocommerce_attributes tbody tr.' + attribute_type);
				jQuery('table.woocommerce_attributes tbody').append( jQuery(thisrow) );
				jQuery(thisrow).show();
				row_indexes();
				
			}
	
			show_attribute_table();
		});
		
		jQuery('button.hide_row').live('click', function(){
			var answer = confirm("Remove this attribute?")
			if (answer){
				jQuery(this).parent().parent().find('select, input[type=text]').val('');
				jQuery(this).parent().parent().hide();
				show_attribute_table();
			}
			return false;
		});
		
		jQuery('#attributes_list button.remove_row').live('click', function(){
			var answer = confirm("Remove this attribute?")
			if (answer){
				jQuery(this).parent().parent().remove();
				show_attribute_table();
				row_indexes();
			}
			return false;
		});
		
		jQuery('button.move_up').live('click', function(){
			var row = jQuery(this).parent().parent();
			var prev_row = jQuery(row).prev('tr');
			jQuery(row).after(prev_row);
			row_indexes();
		});
		jQuery('button.move_down').live('click', function(){
			var row = jQuery(this).parent().parent();
			var next_row = jQuery(row).next('tr');
			jQuery(row).before(next_row);
			row_indexes();
		});

	// Cross sells/Up sells
	jQuery('.multi_select_products button').live('click', function(){
		var wrapper = jQuery(this).parent().parent().parent().parent();
		
		if (jQuery(this).parent().parent().is('.multi_select_products_target')) {	
			jQuery(this).parent().remove();
		} else {
			var target = jQuery('.multi_select_products_target', jQuery(wrapper));
			
			var exists = jQuery('li[rel=' + jQuery(this).parent().attr('rel') + ']', target);
			
			if (jQuery(exists).size()>0) return false;
			
			jQuery(this).parent().clone().appendTo(target).find('button').html('X').parent().find('input').val( jQuery(this).parent().attr('rel') );
		}
	});
	
	jQuery('.multi_select_products #product_search').bind('keyup click', function(){
		
		jQuery('.multi_select_products_source li:not(.product_search)').remove();
		
		var search = encodeURI( jQuery(this).val() );
		var input = this;
		var name = jQuery(this).attr('rel');
		
		if (search.length<3) return;
		
		var data = {
			name: 			name,
			search: 		search,
			action: 		'woocommerce_upsell_crosssell_search_products',
			security: 		params.upsell_crosssell_search_products_nonce
		};
		
	    jQuery.post( params.ajax_url, data, function( response ) {
			
			jQuery('.multi_select_products_source li:not(.product_search)').remove();
			jQuery(input).parent().parent().append( response );
			
		} );
 			
	});

});