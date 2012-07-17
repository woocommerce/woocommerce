jQuery(document).ready(function($) {

	// Reset buttons
	$('.reset_variations').click(function(){
		$('.variations select').val('').change();
		return false;
	}).css('visibility','hidden');
	
	//check if two arrays of attributes match
    function variations_match(attrs1, attrs2) {        
        var match = true;
        for (name in attrs1) {
            var val1 = attrs1[name];
            var val2 = attrs2[name];
            
            if(val1.length != 0 && val2.length != 0 && val1 != val2) {
                match = false;
            }
        }
        
        return match;
    }
    
    //search for matching variations for given set of attributes
    function find_matching_variations(settings) {
        var matching = [];
        
        for (var i = 0; i < product_variations.length; i++) {
        	var variation = product_variations[i];
        	var variation_id = variation.variation_id;
        	
			if(variations_match(variation.attributes, settings)) {
                matching.push(variation);
            }
        }
        return matching;
    }
    
    //disable option fields that are unavaiable for current set of attributes
    function update_variation_values(variations) {
        
        // Loop through selects and disable/enable options based on selections
        $('.variations select').each(function( index, el ){
        	
        	current_attr_select = $(el);
        	
        	// Disable all
        	current_attr_select.find('option:gt(0)').attr('disabled', 'disabled');
        	
        	// Get name
	        var current_attr_name 	= current_attr_select.attr('name');
	        
	        // Loop through variations
	        for(num in variations) {
	            var attributes = variations[num].attributes;
	            
	            for(attr_name in attributes) {
	                var attr_val = attributes[attr_name];
	                
	                if(attr_name == current_attr_name) {
	                    if (attr_val) {
	                    	
	                    	// Decode entities
	                    	attr_val = $("<div/>").html( attr_val ).text();
	                    	
	                    	// Add slashes
	                    	attr_val = attr_val.replace(/'/g, "\\'");
	                    	attr_val = attr_val.replace(/"/g, "\\\"");
	                    	
	                    	// Compare the meercat
	                    	current_attr_select.find('option[value="'+attr_val+'"]').removeAttr('disabled');
	                    	
	                    } else {
	                    	current_attr_select.find('option').removeAttr('disabled');
	                    }
	                }
	            }
	        }
        	
        });

		// Custom event for when variations have been updated
		$(document).trigger('woocommerce_update_variation_values');
        
    }
    
    //show single variation details (price, stock, image)
    function show_variation(variation) {
        var img = $('div.images img:eq(0)');
        var link = $('div.images a.zoom:eq(0)');
        var o_src = $(img).attr('data-o_src');
        var o_href = $(link).attr('data-o_href');

        var variation_image = variation.image_src;
        var variation_link = variation.image_link;
		
		$('.variations_button').show();
        $('.single_variation').html( variation.price_html + variation.availability_html );

        if (!o_src) {
            $(img).attr('data-o_src', $(img).attr('src'));
        }

        if (!o_href) {
            $(link).attr('data-o_href', $(link).attr('href'));
        }

        if (variation_image && variation_image.length > 1) {	
            $(img).attr('src', variation_image);
            $(link).attr('href', variation_link);
        } else {
            $(img).attr('src', o_src);
            $(link).attr('href', o_href);
        }
        
        if (variation.sku) {
        	 $('.product_meta').find('.sku').text( variation.sku );
        } else {
        	 $('.product_meta').find('.sku').text('');
        }
        
        $('.single_variation_wrap').find('.quantity').show();
        
        if (variation.min_qty) {
        	$('.single_variation_wrap').find('input[name=quantity]').attr('data-min', variation.min_qty).val(variation.min_qty);
        } else {
        	$('.single_variation_wrap').find('input[name=quantity]').removeAttr('data-min');
        }
        
        if ( variation.max_qty ) {
        	$('.single_variation_wrap').find('input[name=quantity]').attr('data-max', variation.max_qty);
        } else {
        	$('.single_variation_wrap').find('input[name=quantity]').removeAttr('data-max');
        }
        
        if ( variation.is_sold_individually == 'yes' ) {
        	$('.single_variation_wrap').find('input[name=quantity]').val('1');
        	$('.single_variation_wrap').find('.quantity').hide();
        }

        $('.single_variation_wrap').slideDown('200').trigger('variationWrapShown').trigger('show_variation'); // depreciated variationWrapShown
    }
	
	//when one of attributes is changed - check everything to show only valid options
    function check_variations( exclude, focus ) {
		var all_set = true;
		var any_set = false;
		var showing_variation = false;
		var current_settings = {};
		        
		$('.variations select').each(function(){
			
			if ( exclude && $(this).attr('name') == exclude ) {
				
				all_set = false;
				current_settings[$(this).attr('name')] = '';
				
			} else {
				if ($(this).val().length == 0) {
					all_set = false;
				} else {
					any_set = true;
				}
				
            	// Encode entities
            	value = $(this).val()
		            .replace(/&/g, '&amp;')
		            .replace(/"/g, '&quot;')
		            .replace(/'/g, '&#039;')
		            .replace(/</g, '&lt;')
		            .replace(/>/g, '&gt;');

				// Add to settings array
				current_settings[$(this).attr('name')] = value;
			}
				
		});
        
        var matching_variations = find_matching_variations(current_settings);
        
        if (all_set) {
        	var variation = matching_variations.pop();
        	if (variation) {
            	$('form input[name=variation_id]').val(variation.variation_id).change();
            	show_variation(variation);
            } else {
            	// Nothing found - reset fields
            	$('.variations select').val('');
            	if ( ! focus ) reset_variation_image();
            }
        } else {
            update_variation_values(matching_variations);
            if ( ! focus ) reset_variation_image();
        }
        
        if (any_set) {
        	if ($('.reset_variations').css('visibility') == 'hidden') $('.reset_variations').css('visibility','visible').hide().fadeIn();
        } else {
			$('.reset_variations').css('visibility','hidden');
		}
    }
    
    function reset_variation_image() {
	    // Reset image
		var img = $('div.images img:eq(0)');
        var link = $('div.images a.zoom:eq(0)');
		var o_src = $(img).attr('data-o_src');
        var o_href = $(link).attr('data-o_href');
        
        if ( o_src && o_href ) {
	        $(img).attr('src', o_src);
            $(link).attr('href', o_href);
        }
    }

	$('.variations select').change(function(){
		
		$('form input[name=variation_id]').val('').change();
        $('.single_variation_wrap').hide();
        $('.single_variation').text('');
		check_variations( '', false );
		$(this).blur();
		if( $().uniform && $.isFunction($.uniform.update) ) {
			$.uniform.update();
		}
		
	}).bind( 'focusin', function() {
		
		check_variations( $(this).attr('name'), true );

	}).change();

});