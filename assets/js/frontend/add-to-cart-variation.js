/*!
 * Variations Plugin
 */
;(function ( $, window, document, undefined ) {

    $.fn.wc_variation_form = function () {

        $.fn.wc_variation_form.find_matching_variations = function( product_variations, settings ) {
	        var matching = [];

	        for (var i = 0; i < product_variations.length; i++) {
	        	var variation = product_variations[i];
	        	var variation_id = variation.variation_id;

				if ( $.fn.wc_variation_form.variations_match( variation.attributes, settings ) ) {
	                matching.push(variation);
	            }
	        }
	        return matching;
        }

        $.fn.wc_variation_form.variations_match = function( attrs1, attrs2 ) {
	        var match = true;
	        for ( attr_name in attrs1 ) {
	            var val1 = attrs1[ attr_name ];
	            var val2 = attrs2[ attr_name ];
	            if ( val1 !== undefined && val2 !== undefined && val1.length != 0 && val2.length != 0 && val1 != val2 ) {
	                match = false;
	            }
	        }
	        return match;
        }

        // Unbind any existing events
        this.unbind( 'check_variations update_variation_values found_variation' );
        this.find('.reset_variations').unbind( 'click' );
        this.find('.variations select').unbind( 'change focusin' );

        // Bind events
        return this

				// On clicking the reset variation button
				.on( 'click', '.reset_variations', function( event ) {

					$(this).closest('form.variations_form').find('.variations select').val('').change();

					var $sku 		= $(this).closest('.product').find('.sku');
					var $weight 	= $(this).closest('.product').find('.product_weight');
					var $dimensions = $(this).closest('.product').find('.product_dimensions');

					if ( $sku.attr( 'data-o_sku' ) )
						$sku.text( $sku.attr( 'data-o_sku' ) );

					if ( $weight.attr( 'data-o_weight' ) )
						$weight.text( $weight.attr( 'data-o_weight' ) );

					if ( $dimensions.attr( 'data-o_dimensions' ) )
						$dimensions.text( $dimensions.attr( 'data-o_dimensions' ) );

					return false;
				} )

				// Upon changing an option
				.on( 'change', '.variations select', function( event ) {

					$variation_form = $(this).closest('form.variations_form');
					$variation_form.find('input[name=variation_id]').val('').change();

					$variation_form
						.trigger( 'woocommerce_variation_select_change' )
						.trigger( 'check_variations', [ '', false ] );

					$(this).blur();

					if( $().uniform && $.isFunction( $.uniform.update ) ) {
						$.uniform.update();
					}

				} )

				// Upon gaining focus
				.on( 'focusin', '.variations select', function( event ) {

					$variation_form = $(this).closest('form.variations_form');

					$variation_form
						.trigger( 'woocommerce_variation_select_focusin' )
						.trigger( 'check_variations', [ $(this).attr('name'), true ] );

				} )

				// Check variations
				.on( 'check_variations', function( event, exclude, focus ) {
					var all_set 			= true;
					var any_set 			= false;
					var showing_variation 	= false;
					var current_settings 	= {};
					var $variation_form 	= $(this);
					var $reset_variations	= $variation_form.find('.reset_variations');

					$variation_form.find('.variations select').each( function() {

						if ( $(this).val().length == 0 ) {
							all_set = false;
						} else {
							any_set = true;
						}

						if ( exclude && $(this).attr('name') == exclude ) {

							all_set = false;
							current_settings[$(this).attr('name')] = '';

						} else {

			            	// Encode entities
			            	value = $(this).val();

							// Add to settings array
							current_settings[ $(this).attr('name') ] = value;
						}

					});

					var product_id			= parseInt( $variation_form.data( 'product_id' ) );
					var all_variations		= $variation_form.data( 'product_variations' )

					// Fallback to window property if not set - backwards compat
					if ( ! all_variations )
						all_variations = window[ "product_variations" ][ product_id ];
					if ( ! all_variations )
						all_variations = window[ "product_variations" ];
					if ( ! all_variations )
						all_variations = window[ "product_variations_" + product_id ];

			        var matching_variations = $.fn.wc_variation_form.find_matching_variations( all_variations, current_settings );

			        if ( all_set ) {

			        	var variation = matching_variations.pop();

			        	if ( variation ) {

			        		// Found - set ID
			            	$variation_form
			            		.find('input[name=variation_id]')
			            		.val( variation.variation_id )
			            		.change();

			            	$variation_form.trigger( 'found_variation', [ variation ] );

			            } else {

			            	// Nothing found - reset fields
			            	$variation_form.find('.variations select').val('');

			            	if ( ! focus )
			            		$variation_form.trigger( 'reset_image' );

			            	alert( woocommerce_params.i18n_no_matching_variations_text );

			            }

			        } else {

			            $variation_form.trigger( 'update_variation_values', [ matching_variations ] );

			            if ( ! focus )
			            	$variation_form.trigger( 'reset_image' );

						if ( ! exclude ) {
							$variation_form.find('.single_variation_wrap').slideUp('200');
						}

			        }

			        if ( any_set ) {

			        	if ( $reset_variations.css('visibility') == 'hidden' )
			        		$reset_variations.css('visibility','visible').hide().fadeIn();

			        } else {

						$reset_variations.css('visibility','hidden');

					}

				} )

				// Reset product image
				.on( 'reset_image', function( event ) {

					var $product 		= $(this).closest( '.product' );
					var $product_img 	= $product.find( 'div.images img:eq(0)' );
					var $product_link 	= $product.find( 'div.images a.zoom:eq(0)' );
					var o_src 			= $product_img.attr('data-o_src');
					var o_title 		= $product_img.attr('data-o_title');
					var o_alt           = $product_img.attr('data-o_alt');
			        var o_href 			= $product_link.attr('data-o_href');

			        if ( o_src != undefined ) {
				        $product_img
				        	.attr( 'src', o_src );
			        }
			        if ( o_href != undefined ) {
			            $product_link
			            	.attr( 'href', o_href );
			        }
			        if ( o_title != undefined ) {
				        $product_img
				        	.attr( 'title', o_title );
			            $product_link
							.attr( 'title', o_title );
			        }
			        if ( o_alt != undefined ) {
			        	 $product_img
				        	.attr( 'alt', o_alt );
			        }
				} )

				// Disable option fields that are unavaiable for current set of attributes
				.on( 'update_variation_values', function( event, variations ) {

			    	$variation_form = $(this).closest('form.variations_form');

			        // Loop through selects and disable/enable options based on selections
			        $variation_form.find('.variations select').each(function( index, el ) {

			        	current_attr_select = $(el);

			        	// Reset options
			        	if ( ! current_attr_select.data( 'attribute_options' ) )
			        		current_attr_select.data( 'attribute_options', current_attr_select.find('option:gt(0)').get() )

			        	current_attr_select.find('option:gt(0)').remove();
			        	current_attr_select.append( current_attr_select.data( 'attribute_options' ) );
			        	current_attr_select.find('option:gt(0)').removeClass('active');

			        	// Get name
				        var current_attr_name 	= current_attr_select.attr('name');

				        // Loop through variations
				        for ( num in variations ) {

				        	if ( typeof( variations[ num ] ) != "undefined" ) {

					            var attributes = variations[ num ].attributes;

					            for ( attr_name in attributes ) {

					                var attr_val = attributes[ attr_name ];

					                if ( attr_name == current_attr_name ) {

					                    if ( attr_val ) {

					                    	// Decode entities
					                    	attr_val = $("<div/>").html( attr_val ).text();

					                    	// Add slashes
					                    	attr_val = attr_val.replace(/'/g, "\\'");
					                    	attr_val = attr_val.replace(/"/g, "\\\"");

					                    	// Compare the meercat
					                    	current_attr_select.find('option[value="' + attr_val + '"]').addClass('active');

					                    } else {

					                    	current_attr_select.find('option:gt(0)').addClass('active');

					                    }

					                }

					            }

				            }

				        }

				        // Detach inactive
				        current_attr_select.find('option:gt(0):not(.active)').remove();

			        });

					// Custom event for when variations have been updated
					$variation_form.trigger('woocommerce_update_variation_values');

				} )

				// Show single variation details (price, stock, image)
				.on( 'found_variation', function( event, variation ) {
			      	var $variation_form = $(this);

			        var $product 		= $(this).closest( '.product' );
					var $product_img 	= $product.find( 'div.images img:eq(0)' );
					var $product_link 	= $product.find( 'div.images a.zoom:eq(0)' );

					var o_src 			= $product_img.attr('data-o_src');
					var o_title 		= $product_img.attr('data-o_title');
					var o_alt 		    = $product_img.attr('data-o_alt');
			        var o_href 			= $product_link.attr('data-o_href');

			        var variation_image = variation.image_src;
			        var variation_link  = variation.image_link;
					var variation_title = variation.image_title;
					var variation_alt   = variation.image_alt;

					$variation_form.find('.variations_button').show();
			        $variation_form.find('.single_variation').html( variation.price_html + variation.availability_html );

			        if ( o_src == undefined ) {
			        	o_src = ( ! $product_img.attr('src') ) ? '' : $product_img.attr('src');
			            $product_img.attr('data-o_src', o_src );
			        }

			        if ( o_href == undefined ) {
			        	o_href = ( ! $product_link.attr('href') ) ? '' : $product_link.attr('href');
			            $product_link.attr('data-o_href', o_href );
			        }

			        if ( o_title == undefined ) {
			        	o_title = ( ! $product_img.attr('title') ) ? '' : $product_img.attr('title');
			            $product_img.attr('data-o_title', o_title );
			        }

			        if ( o_alt == undefined ) {
			        	o_alt = ( ! $product_img.attr('alt') ) ? '' : $product_img.attr('alt');
			            $product_img.attr('data-o_alt', o_alt );
			        }

			        if ( variation_image && variation_image.length > 1 ) {
			            $product_img
			            	.attr( 'src', variation_image )
			            	.attr( 'alt', variation_alt )
			            	.attr( 'title', variation_title );
			            $product_link
			            	.attr( 'href', variation_link )
							.attr( 'title', variation_title );
			        } else {
			            $product_img
			            	.attr( 'src', o_src )
			            	.attr( 'alt', o_alt )
			            	.attr( 'title', o_title );
			            $product_link
			            	.attr( 'href', o_href )
							.attr( 'title', o_title );
			        }

			        var $single_variation_wrap = $variation_form.find('.single_variation_wrap');

			        var $sku 		= $product.find('.product_meta').find('.sku');
			        var $weight 	= $product.find('.product_weight');
					var $dimensions = $product.find('.product_dimensions');

			        if ( ! $sku.attr( 'data-o_sku' ) )
			        	$sku.attr( 'data-o_sku', $sku.text() );

			        if ( ! $weight.attr( 'data-o_weight' ) )
			        	$weight.attr( 'data-o_weight', $weight.text() );

			        if ( ! $dimensions.attr( 'data-o_dimensions' ) )
			        	$dimensions.attr( 'data-o_dimensions', $dimensions.text() );

			        if ( variation.sku ) {
				    	$sku.text( variation.sku );
			        } else {
			        	$sku.text( $sku.attr( 'data-o_sku' ) );
			        }

			        if ( variation.weight ) {
				    	$weight.text( variation.weight );
			        } else {
			        	$weight.text( $weight.attr( 'data-o_weight' ) );
			        }

			        if ( variation.dimensions ) {
				    	$dimensions.text( variation.dimensions );
			        } else {
			        	$dimensions.text( $dimensions.attr( 'data-o_dimensions' ) );
			        }

			        $single_variation_wrap.find('.quantity').show();

			        if ( ! variation.is_in_stock && ! variation.backorders_allowed ) {
				        $variation_form.find('.variations_button').hide();
			        }

			        if ( variation.min_qty )
			        	$single_variation_wrap.find('input[name=quantity]').attr( 'min', variation.min_qty ).val( variation.min_qty );
			        else
			        	$single_variation_wrap.find('input[name=quantity]').removeAttr('min');

			        if ( variation.max_qty )
			        	$single_variation_wrap.find('input[name=quantity]').attr('max', variation.max_qty);
			        else
			        	$single_variation_wrap.find('input[name=quantity]').removeAttr('max');

			        if ( variation.is_sold_individually == 'yes' ) {
			        	$single_variation_wrap.find('input[name=quantity]').val('1');
			        	$single_variation_wrap.find('.quantity').hide();
			        }

			        $single_variation_wrap.slideDown('200').trigger( 'show_variation', [ variation ] );

				});
    };

    $('form.variations_form').wc_variation_form();
    $('form.variations_form .variations select').change();

})( jQuery, window, document );