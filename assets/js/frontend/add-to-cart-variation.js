jQuery(document).ready(function($) {

	/**
	 * Variations form handling
	 */
	$('form.variations_form')

		// On clicking the reset variation button
		.on( 'click', '.reset_variations', function( event ) {

			$(this).closest('form.variations_form').find('.variations select').val('').change();

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
	            	value = $(this).val()
			            .replace(/&/g, '&amp;')
			            .replace(/"/g, '&quot;')
			            .replace(/'/g, '&#039;')
			            .replace(/</g, '&lt;')
			            .replace(/>/g, '&gt;');

					// Add to settings array
					current_settings[ $(this).attr('name') ] = value;
				}

			});

			var product_id			= parseInt( $variation_form.attr( 'data-product_id' ) );
			var all_variations		= window[ "product_variations_" + product_id ];
			
			// Fallback
			if ( ! all_variations ) 
				all_variations = window[ "product_variations" ];
			
	        var matching_variations = find_matching_variations( all_variations, current_settings );

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
	        var o_href 			= $product_link.attr('data-o_href');

	        if ( o_src && o_href && o_title ) {
		        $product_img
		        	.attr( 'src', o_src )
		        	.attr( 'alt', o_title )
		        	.attr( 'title', o_title );
	            $product_link
	            	.attr( 'href', o_href );
	        }

		} )

		// Disable option fields that are unavaiable for current set of attributes
		.on( 'update_variation_values', function( event, variations ) {

	    	$variation_form = $(this).closest('form.variations_form');

	        // Loop through selects and disable/enable options based on selections
	        $variation_form.find('.variations select').each(function( index, el ){

	        	current_attr_select = $(el);

	        	// Disable all
	        	current_attr_select.find('option:gt(0)').attr('disabled', 'disabled');

	        	// Get name
		        var current_attr_name 	= current_attr_select.attr('name');

		        // Loop through variations
		        for ( num in variations ) {

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
		                    	current_attr_select.find('option[value="' + attr_val + '"]').removeAttr('disabled');

		                    } else {
		                    	current_attr_select.find('option').removeAttr('disabled');
		                    }

		                }

		            }

		        }

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
	        var o_href 			= $product_link.attr('data-o_href');

	        var variation_image = variation.image_src;
	        var variation_link = variation.image_link;
			var variation_title = variation.image_title;

			$variation_form.find('.variations_button').show();
	        $variation_form.find('.single_variation').html( variation.price_html + variation.availability_html );

	        if ( ! o_src ) {
	        	o_src = ( ! $product_img.attr('src') ) ? '' : $product_img.attr('src');
	            $product_img.attr('data-o_src', o_src );
	        }

	        if ( ! o_href ) {
	        	o_href = ( ! $product_link.attr('href') ) ? '' : $product_link.attr('href');
	            $product_link.attr('data-o_href', o_href );
	        }

	        if ( ! o_title ) {
	        	o_title = ( ! $product_img.attr('title') ) ? '' : $product_img.attr('title');
	            $product_img.attr('data-o_title', o_title );
	        }

	        if ( variation_image && variation_image.length > 1 ) {
	            $product_img
	            	.attr( 'src', variation_image )
	            	.attr( 'alt', variation_title )
	            	.attr( 'title', variation_title );
	            $product_link
	            	.attr( 'href', variation_link );
	        } else {
	            $product_img
	            	.attr( 'src', o_src )
	            	.attr( 'alt', o_title )
	            	.attr( 'title', o_title );
	            $product_link
	            	.attr( 'href', o_href );
	        }

	        var $single_variation_wrap = $variation_form.find('.single_variation_wrap');

	        if ( variation.sku )
	        	 $variation_form.find('.product_meta').find('.sku').text( variation.sku );
	        else
	        	 $variation_form.find('.product_meta').find('.sku').text('');

	        $single_variation_wrap.find('.quantity').show();
	        
	        if ( ! variation.is_in_stock && ! variation.backorders_allowed ) {
		        $variation_form.find('.variations_button').hide();
	        }
	        
	        if ( variation.min_qty )
	        	$single_variation_wrap.find('input[name=quantity]').attr( 'data-min', variation.min_qty ).val( variation.min_qty );
	        else
	        	$single_variation_wrap.find('input[name=quantity]').removeAttr('data-min');

	        if ( variation.max_qty )
	        	$single_variation_wrap.find('input[name=quantity]').attr('data-max', variation.max_qty);
	        else
	        	$single_variation_wrap.find('input[name=quantity]').removeAttr('data-max');

	        if ( variation.is_sold_individually == 'yes' ) {
	        	$single_variation_wrap.find('input[name=quantity]').val('1');
	        	$single_variation_wrap.find('.quantity').hide();
	        }

	        $single_variation_wrap.slideDown('200').trigger( 'show_variation', [ variation ] );

		} );

	/**
	 * Initial states and loading
	 */
	$('form.variations_form .variations select').change();


	/**
	 * Helper functions for variations
	 */

    // Search for matching variations for given set of attributes
    function find_matching_variations( product_variations, settings ) {
        var matching = [];

        for (var i = 0; i < product_variations.length; i++) {
        	var variation = product_variations[i];
        	var variation_id = variation.variation_id;

			if ( variations_match( variation.attributes, settings ) ) {
                matching.push(variation);
            }
        }
        return matching;
    }

	// Check if two arrays of attributes match
    function variations_match( attrs1, attrs2 ) {
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

});