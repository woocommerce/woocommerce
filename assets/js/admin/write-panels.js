jQuery( function($){
	
	// Prevent enter submitting post form
	$("#upsell_product_data").bind("keypress", function(e) {
		if (e.keyCode == 13) return false;
	});
	
	// TABS
	$('ul.tabs').show();
	$('div.panel-wrap').each(function(){
		$('div.panel:not(div.panel:first)', this).hide();
	});
	$('ul.tabs a').click(function(){
		var panel_wrap =  $(this).closest('div.panel-wrap');
		$('ul.tabs li', panel_wrap).removeClass('active');
		$(this).parent().addClass('active');
		$('div.panel', panel_wrap).hide();
		$( $(this).attr('href') ).show();
		return false;
	});
	
	// ORDERS
	$('#order_items_list button.remove_row').live('click', function(){
		var answer = confirm(woocommerce_writepanel_params.remove_item_notice);
		if (answer){
			$(this).closest('tr.item').hide();
			$('input', $(this).closest('tr.item')).val('');
		}
		return false;
	});
	
	$('button.calc_totals').live('click', function(){
		var answer = confirm(woocommerce_writepanel_params.cart_total);
		if (answer){
			
			var item_count = $('#order_items_list tr.item').size();
			var subtotal = 0;
			var discount = $('input#_order_discount').val();
			var shipping = $('input#_order_shipping').val();
			var shipping_tax = parseFloat($('input#_order_shipping_tax').val());
			var tax = 0;
			var itemTotal = 0;
			var total = 0;
			
			if (!discount) discount = 0;
			if (!shipping) shipping = 0;
			if (!shipping_tax) shipping_tax = 0;
			
			// Items
			if (item_count>0) {
				for (i=0; i<item_count; i++) {
					
					itemCost 	= $('input[name^=item_cost]:eq(' + i + ')').val();
					itemQty 	= parseInt($('input[name^=item_quantity]:eq(' + i + ')').val());
					itemTax		= $('input[name^=item_tax_rate]:eq(' + i + ')').val();
					
					if (!itemCost) itemCost = 0;
					if (!itemTax) itemTax = 0;
					if (!itemQty) itemQty = 0;
					
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

			$('input#_order_subtotal').val( subtotal.toFixed(2) );
			$('input#_order_tax').val( tax.toFixed(2) );
			$('input#_order_shipping_tax').val( shipping_tax.toFixed(2) );
			$('input#_order_total').val( total.toFixed(2) );

		}
		return false;
	});
	
	$('button.add_shop_order_item').click(function(){
		
		var add_item_id = $('select.add_item_id').val();
		
		if (add_item_id) {
			
			$('table.woocommerce_order_items').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });
			
			var size = $('table.woocommerce_order_items tbody tr.item').size();
			
			var data = {
				action: 		'woocommerce_add_order_item',
				item_to_add: 	$('select.add_item_id').val(),
				index:			size,
				security: 		woocommerce_writepanel_params.add_order_item_nonce
			};

			$.post( woocommerce_writepanel_params.ajax_url, data, function(response) {
				
				$('table.woocommerce_order_items tbody#order_items_list').append( response );
				$('table.woocommerce_order_items').unblock();
				$('select.add_item_id').css('border-color', '').val('');
				$(".tips").easyTooltip();
				
			});

		} else {
			$('select.add_item_id').css('border-color', 'red');
		}

	});
	
	$('button.add_meta').live('click', function(){
		
		var index = $(this).closest('tr.item').attr('rel');
		
		$(this).closest('table.meta').find('.meta_items').append('<tr><td><input type="text" name="meta_name[' + index + '][]" placeholder="' + woocommerce_writepanel_params.meta_name + '" /></td><td><input type="text" name="meta_value[' + index + '][]" placeholder="' + woocommerce_writepanel_params.meta_value + '" /></td><td><button class="remove_meta button">&times;</button></td></tr>');
		
		return false;
		
	});
	
	$('button.remove_meta').live('click', function(){
		var answer = confirm("Remove this meta key?")
		if (answer){
			$(this).closest('tr').remove();
		}
		return false;
	});
	
	$('button.billing-same-as-shipping').live('click', function(){
		var answer = confirm(woocommerce_writepanel_params.copy_billing);
		if (answer){
			$('input#shipping_first_name').val( $('input#billing_first_name').val() );
			$('input#shipping_last_name').val( $('input#billing_last_name').val() );
			$('input#shipping_company').val( $('input#billing_company').val() );
			$('input#shipping_address_1').val( $('input#billing_address_1').val() );
			$('input#shipping_address_2').val( $('input#billing_address_2').val() );
			$('input#shipping_city').val( $('input#billing_city').val() );
			$('input#shipping_postcode').val( $('input#billing_postcode').val() );
			$('input#shipping_country').val( $('input#billing_country').val() );
			$('input#shipping_state').val( $('input#billing_state').val() );			
		}
		return false;
	});
	
	// PRODUCT TYPE SPECIFIC OPTIONS
	$('select#product-type').change(function(){
		
		// Get value
		var select_val = $(this).val();
		
		$('.show_if_simple, .show_if_variable, .show_if_grouped, .show_if_external').hide();
		
		if (select_val=='simple') {
			$('.show_if_simple').show();
			$('input#manage_stock').change();
		}
		
		else if (select_val=='variable') {
			$('.show_if_variable').show();
			$('input#manage_stock').change();
			$('input#downloadable').prop('checked', false).change();
			$('input#virtual').removeAttr('checked').change();
		}
		
		else if (select_val=='grouped') {
			$('.show_if_grouped').show();
			$('input#downloadable').prop('checked', false).change();
			$('input#virtual').removeAttr('checked').change();
		}
		
		else if (select_val=='external') {
			$('.show_if_external').show();
			$('input#downloadable').prop('checked', false).change();
			$('input#virtual').removeAttr('checked').change();
		}
		
		$('ul.tabs li:visible').eq(0).find('a').click();
		
		$('body').trigger('woocommerce-product-type-change', select_val, $(this) );
		
	}).change();
	
	$('input#downloadable').change(function(){
	
		$('.show_if_downloadable').hide();
		
		if ($('input#downloadable').is(':checked')) {
			$('.show_if_downloadable').show();
		}
		
		if ($('.downloads_tab').is('.active')) $('ul.tabs li:visible').eq(0).find('a').click();
		
	}).change();
	
	$('input#virtual').change(function(){
	
		$('.show_if_virtual').hide();
		
		if ($('input#virtual').is(':checked')) {
			$('.show_if_virtual').show();
		}
		
	}).change();

	// STOCK OPTIONS
	$('input#manage_stock').change(function(){
		if ($(this).is(':checked')) $('div.stock_fields').show();
		else $('div.stock_fields').hide();
	}).change();
	
	
	// DATE PICKER FIELDS
	var dates = $( "#sale_price_dates_from, #sale_price_dates_to" ).datepicker({
		defaultDate: "",
		dateFormat: "yy-mm-dd",
		numberOfMonths: 1,
		showButtonPanel: true,
		showOn: "button",
		buttonImage: woocommerce_writepanel_params.calendar_image,
		buttonImageOnly: true,
		onSelect: function( selectedDate ) {
			var option = this.id == "sale_price_dates_from" ? "minDate" : "maxDate",
				instance = $( this ).data( "datepicker" ),
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
		
	
	// ATTRIBUTE TABLES
		
		// Multiselect attributes
		$("#attributes_list select.multiselect").multiselect({
			noneSelectedText: woocommerce_writepanel_params.select_terms,
			selectedList: 4
		});	
		
		// Initial order
		var woocommerce_attributes_table_items = $('#attributes_list').children('tr').get();
		woocommerce_attributes_table_items.sort(function(a, b) {
		   var compA = $(a).attr('rel');
		   var compB = $(b).attr('rel');
		   return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
		})
		$(woocommerce_attributes_table_items).each( function(idx, itm) { $('#attributes_list').append(itm); } );
		
		// Show
		function show_attribute_table() {
			$('table.woocommerce_attributes, table.woocommerce_variable_attributes').each(function(){
				if ($('tbody tr', this).size()==0) 
					$(this).parent().hide();
				else 
					$(this).parent().show();
			});
		}
		show_attribute_table();
		
		function row_indexes() {
			$('#attributes_list tr').each(function(index, el){ $('.attribute_position', el).val( parseInt( $(el).index('#attributes_list tr') ) ); });
		};
		
		// Add rows
		$('button.add_attribute').click(function(){
			
			var size = $('table.woocommerce_attributes tbody tr').size();
			
			var attribute_type = $('select.attribute_taxonomy').val();
			
			if (!attribute_type) {
			
				var product_type = $('select#product-type').val();
				if (product_type!='variable') enable_variation = 'style="display:none;"'; else enable_variation = '';
				
				// Add custom attribute row
				$('table.woocommerce_attributes tbody').append('<tr><td class="handle"></td><td><input type="text" name="attribute_names[' + size + ']" /><input type="hidden" name="attribute_is_taxonomy[' + size + ']" value="0" /><input type="hidden" name="attribute_position[' + size + ']" class="attribute_position" value="' + size + '" /></td><td><input type="text" name="attribute_values[' + size + ']" /></td><td class="center"><input type="checkbox" checked="checked" name="attribute_visibility[' + size + ']" value="1" /></td><td class="center enable_variation" ' + enable_variation + '><input type="checkbox" name="attribute_variation[' + size + ']" value="1" /></td><td class="center"><button type="button" class="remove_row button">&times;</button></td></tr>');
				
			} else {
				
				// Reveal taxonomy row
				var thisrow = $('table.woocommerce_attributes tbody tr.' + attribute_type);
				$('table.woocommerce_attributes tbody').append( $(thisrow) );
				$(thisrow).show();
				row_indexes();
				
			}
	
			show_attribute_table();
		});
		
		$('button.hide_row').live('click', function(){
			var answer = confirm("Remove this attribute?")
			if (answer){
				$(this).parent().parent().find('select, input[type=text]').val('');
				$(this).parent().parent().hide();
				show_attribute_table();
			}
			return false;
		});
		
		$('#attributes_list button.remove_row').live('click', function(){
			var answer = confirm("Remove this attribute?")
			if (answer){
				$(this).parent().parent().remove();
				show_attribute_table();
				row_indexes();
			}
			return false;
		});
		
		// Attribute ordering
		$('table.woocommerce_attributes tbody').sortable({
			items:'tr',
			cursor:'move',
			axis:'y',
			handle: '.handle',
			scrollSensitivity:40,
			helper:function(e,ui){
				ui.children().each(function(){
					$(this).width($(this).width());
				});
				ui.css('left', '0');
				return ui;
			},
			start:function(event,ui){
				ui.item.css('background-color','#f6f6f6');
			},
			stop:function(event,ui){
				ui.item.removeAttr('style');
				row_indexes();
			}
		});

		

	// Cross sells/Up sells
	$('.multi_select_products button').live('click', function(){
		
		var wrapper = $(this).parent().parent().parent().parent();
		
		var button = $(this);
		var button_parent = button.parent().parent();
		
		if (button_parent.is('.multi_select_products_target_upsell') || button_parent.is('.multi_select_products_target_crosssell')) {	
			button.parent().remove();
		} else {
			if (button.is('.add_upsell')) {
				var target = $('.multi_select_products_target_upsell', $(wrapper));
				var product_id_field_name = 'upsell_ids[]';
			} else {
				var target = $('.multi_select_products_target_crosssell', $(wrapper));
				var product_id_field_name = 'crosssell_ids[]';
			}
		
			var exists = $('li[rel=' + button.parent().attr('rel') + ']', target);
			
			if ($(exists).size()>0) return false;
			
			var cloned_item = button.parent().clone();
			
			cloned_item.find('button:eq(0)').html('&times;');
			cloned_item.find('button:eq(1)').remove();
			cloned_item.find('input').val( button.parent().attr('rel') );
			cloned_item.find('.product_id').attr('name', product_id_field_name);
			
			cloned_item.appendTo(target);
		}
	});
	
	var xhr;
	
	$('.multi_select_products #product_search').bind('keyup click', function(){
		
		$('.multi_select_products_source').addClass('loading');
		$('.multi_select_products_source li:not(.product_search)').remove();
		
		if (xhr) xhr.abort();
		
		var search = $(this).val();
		var input = this;
		var name = $(this).attr('rel');
		
		if (search.length<3) {
			$('.multi_select_products_source').removeClass('loading');
			return;
		}
		
		var data = {
			name: 			name,
			search: 		encodeURI(search),
			action: 		'woocommerce_upsell_crosssell_search_products',
			security: 		woocommerce_writepanel_params.upsell_crosssell_search_products_nonce
		};
		
		xhr = $.ajax({
			url: woocommerce_writepanel_params.ajax_url,
			data: data,
			type: 'POST',
			success: function( response ) {
			
				$('.multi_select_products_source').removeClass('loading');
				$('.multi_select_products_source li:not(.product_search)').remove();
				$(input).parent().parent().append( response );
				
			}
		});
 			
	});
	
	// Uploading files
	var file_path_field;
	
	window.send_to_editor_default = window.send_to_editor;

	jQuery('.upload_file_button').live('click', function(){
		
		file_path_field = jQuery(this).parent().find('.file_path');
		
		formfield = jQuery(file_path_field).attr('name');
		
		window.send_to_editor = window.send_to_download_url;
		
		tb_show('', 'media-upload.php?post_id=' + woocommerce_writepanel_params.post_id + '&amp;type=file&amp;from=wc01&amp;TB_iframe=true');
		return false;
	});

	window.send_to_download_url = function(html) {
		
		file_url = jQuery(html).attr('href');
		if (file_url) {
			jQuery(file_path_field).val(file_url);
		}
		tb_remove();
		window.send_to_editor = window.send_to_editor_default;
		
	}

});