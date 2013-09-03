jQuery( function($){

	var variation_sortable_options = {
		items:'.woocommerce_variation',
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
			variation_row_indexes();
		}
	};

	// Add a variation
	jQuery('#variable_product_options').on('click', 'button.add_variation', function(){

		jQuery('.woocommerce_variations').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_admin_meta_boxes_variations.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

		var loop = jQuery('.woocommerce_variation').size();

		var data = {
			action: 'woocommerce_add_variation',
			post_id: woocommerce_admin_meta_boxes_variations.post_id,
			loop: loop,
			security: woocommerce_admin_meta_boxes_variations.add_variation_nonce
		};

		jQuery.post( woocommerce_admin_meta_boxes_variations.ajax_url, data, function(response) {

			jQuery('.woocommerce_variations').append( response );

			jQuery(".tips").tipTip({
		    	'attribute' : 'data-tip',
		    	'fadeIn' : 50,
		    	'fadeOut' : 50
		    });

		    jQuery('input.variable_is_downloadable, input.variable_is_virtual').change();

			jQuery('.woocommerce_variations').unblock();
			jQuery('#variable_product_options').trigger('woocommerce_variations_added');
		});

		return false;

	});

	jQuery('#variable_product_options').on('click', 'button.link_all_variations', function() {

		var answer = confirm( woocommerce_admin_meta_boxes_variations.i18n_link_all_variations );

		if (answer) {

			jQuery('#variable_product_options').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_admin_meta_boxes_variations.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			var data = {
				action: 'woocommerce_link_all_variations',
				post_id: woocommerce_admin_meta_boxes_variations.post_id,
				security: woocommerce_admin_meta_boxes_variations.link_variation_nonce
			};

			jQuery.post( woocommerce_admin_meta_boxes_variations.ajax_url, data, function(response) {

				var count = parseInt( response );

				if (count==1) {
					alert( count + ' ' + woocommerce_admin_meta_boxes_variations.i18n_variation_added );
				} else if (count==0 || count>1) {
					alert( count + ' ' + woocommerce_admin_meta_boxes_variations.i18n_variations_added );
				} else {
					alert( woocommerce_admin_meta_boxes_variations.i18n_no_variations_added );
				}

				if (count>0) {
					var this_page = window.location.toString();

					this_page = this_page.replace( 'post-new.php?', 'post.php?post=' + woocommerce_admin_meta_boxes_variations.post_id + '&action=edit&' );

					$('#variable_product_options').load( this_page + ' #variable_product_options_inner', function() {
						$('#variable_product_options').unblock();
						jQuery('#variable_product_options').trigger('woocommerce_variations_added');
					} );
				} else {
					$('#variable_product_options').unblock();
				}
			});
		}
		return false;
	});

	jQuery('#variable_product_options').on('click', 'button.remove_variation', function(e){
		e.preventDefault();
		var answer = confirm( woocommerce_admin_meta_boxes_variations.i18n_remove_variation );
		if (answer){

			var el = jQuery(this).parent().parent();

			var variation = jQuery(this).attr('rel');

			if (variation>0) {

				jQuery(el).block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_admin_meta_boxes_variations.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

				var data = {
					action: 'woocommerce_remove_variation',
					variation_id: variation,
					security: woocommerce_admin_meta_boxes_variations.delete_variation_nonce
				};

				jQuery.post(woocommerce_admin_meta_boxes_variations.ajax_url, data, function(response) {
					// Success
					jQuery(el).fadeOut('300', function(){
						jQuery(el).remove();
					});
				});

			} else {
				jQuery(el).fadeOut('300', function(){
					jQuery(el).remove();
				});
			}

		}
		return false;
	});

	jQuery('.wc-metaboxes-wrapper').on('click', 'a.bulk_edit', function(event){
		var bulk_edit  = jQuery('select#field_to_edit').val();

		switch( bulk_edit ) {
			case 'toggle_enabled':
				var checkbox = jQuery('input[name^="variable_enabled"]');
	       		checkbox.attr('checked', !checkbox.attr('checked'));
			break;
			case 'toggle_downloadable':
				var checkbox = jQuery('input[name^="variable_is_downloadable"]');
	       		checkbox.attr('checked', !checkbox.attr('checked'));
	       		jQuery('input.variable_is_downloadable').change();
			break;
			case 'toggle_virtual':
				var checkbox = jQuery('input[name^="variable_is_virtual"]');
	       		checkbox.attr('checked', !checkbox.attr('checked'));
	       		jQuery('input.variable_is_virtual').change();
			break;
			case 'delete_all':
				var answer = confirm( woocommerce_admin_meta_boxes_variations.i18n_delete_all_variations );

				if (answer){

					var answer = confirm( woocommerce_admin_meta_boxes_variations.i18n_last_warning );

					if (answer) {

						var variation_ids = [];

						jQuery('.woocommerce_variations .woocommerce_variation').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_admin_meta_boxes_variations.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

						jQuery('.woocommerce_variations .woocommerce_variation .remove_variation').each(function(){

							var variation = jQuery(this).attr('rel');
							if (variation>0) {
								variation_ids.push(variation);
							}
						});

						var data = {
							action: 'woocommerce_remove_variations',
							variation_ids: variation_ids,
							security: woocommerce_admin_meta_boxes_variations.delete_variations_nonce
						};

						jQuery.post(woocommerce_admin_meta_boxes_variations.ajax_url, data, function(response) {
							jQuery('.woocommerce_variations .woocommerce_variation').fadeOut('300', function(){
								jQuery('.woocommerce_variations .woocommerce_variation').remove();
							});
						});
					}
				}
			break;
			case 'variable_regular_price_increase':
			case 'variable_regular_price_decrease':
			case 'variable_sale_price_increase':
			case 'variable_sale_price_decrease':
				if ( bulk_edit.lastIndexOf( 'variable_regular_price', 0 ) === 0 )
					var edit_field = 'variable_regular_price';
				else
					var edit_field = 'variable_sale_price';

				var value = prompt( woocommerce_admin_meta_boxes_variations.i18n_enter_a_value_fixed_or_percent );

				jQuery( ':input[name^="' + edit_field + '["]').each(function() {
					var current_value = Number( jQuery(this).val() );

					if ( value.indexOf("%") >= 0 )
						value = Number( ( Number( current_value ) / 100 ) * Number( value.replace(/\%/, "" ) ) );
					else
						value = Number( value );

					if ( bulk_edit.indexOf( "increase" ) != -1 )
						var new_value = current_value + value;
					else
						var new_value = current_value - value;

					jQuery(this).val( new_value ).change();
				});
			break;
			case 'variable_regular_price' :
			case 'variable_sale_price' :
			case 'variable_stock' :
			case 'variable_weight' :
			case 'variable_length' :
			case 'variable_width' :
			case 'variable_height' :
			case 'variable_file_paths' :
			case 'variable_download_limit' :
			case 'variable_download_expiry' :
				var value = prompt( woocommerce_admin_meta_boxes_variations.i18n_enter_a_value );

				jQuery( ':input[name^="' + bulk_edit + '["]').val( value ).change();
			break;
		}
	});

	jQuery('#variable_product_options').on('change', 'input.variable_is_downloadable', function(){

		jQuery(this).closest('.woocommerce_variation').find('.show_if_variation_downloadable').hide();

		if (jQuery(this).is(':checked')) {
			jQuery(this).closest('.woocommerce_variation').find('.show_if_variation_downloadable').show();
		}

	});

	jQuery('#variable_product_options').on('change', 'input.variable_is_virtual', function(){

		jQuery(this).closest('.woocommerce_variation').find('.hide_if_variation_virtual').show();

		if (jQuery(this).is(':checked')) {
			jQuery(this).closest('.woocommerce_variation').find('.hide_if_variation_virtual').hide();
		}

	});

	jQuery('input.variable_is_downloadable, input.variable_is_virtual').change();

	// Ordering
	$('#variable_product_options').on( 'woocommerce_variations_added', function() {
		$('.woocommerce_variations').sortable( variation_sortable_options );
	} );

	$('.woocommerce_variations').sortable( variation_sortable_options );

	function variation_row_indexes() {
		$('.woocommerce_variations .woocommerce_variation').each(function(index, el){
			$('.variation_menu_order', el).val( parseInt( $(el).index('.woocommerce_variations .woocommerce_variation') ) );
		});
	};

	// Uploader
	var variable_image_frame;
	var setting_variation_image_id;
	var setting_variation_image;
	var wp_media_post_id = wp.media.model.settings.post.id;

	jQuery('#variable_product_options').on('click', '.upload_image_button', function( event ) {

		var $button                = jQuery( this );
		var post_id                = $button.attr('rel');
		var $parent                = $button.closest('.upload_image');
		setting_variation_image    = $parent;
		setting_variation_image_id = post_id;

		event.preventDefault();

		if ( $button.is('.remove') ) {

			setting_variation_image.find( '.upload_image_id' ).val( '' );
			setting_variation_image.find( 'img' ).attr( 'src', woocommerce_admin_meta_boxes_variations.woocommerce_placeholder_img_src );
			setting_variation_image.find( '.upload_image_button' ).removeClass( 'remove' );

		} else {

			// If the media frame already exists, reopen it.
			if ( variable_image_frame ) {
				variable_image_frame.uploader.uploader.param( 'post_id', setting_variation_image_id );
				variable_image_frame.open();
				return;
			} else {
				wp.media.model.settings.post.id = setting_variation_image_id;
			}

			// Create the media frame.
			variable_image_frame = wp.media.frames.variable_image = wp.media({
				// Set the title of the modal.
				title: woocommerce_admin_meta_boxes_variations.i18n_choose_image,
				button: {
					text: woocommerce_admin_meta_boxes_variations.i18n_set_image
				}
			});

			// When an image is selected, run a callback.
			variable_image_frame.on( 'select', function() {

				attachment = variable_image_frame.state().get('selection').first().toJSON();

				setting_variation_image.find( '.upload_image_id' ).val( attachment.id );
				setting_variation_image.find( '.upload_image_button' ).addClass( 'remove' );
				setting_variation_image.find( 'img' ).attr( 'src', attachment.url );

				wp.media.model.settings.post.id = wp_media_post_id;
			});

			// Finally, open the modal.
			variable_image_frame.open();
		}
	});

	// Restore ID
	jQuery('a.add_media').on('click', function() {
		wp.media.model.settings.post.id = wp_media_post_id;
	} );

});