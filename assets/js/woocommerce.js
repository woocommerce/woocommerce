jQuery.fn.animateHighlight = function(highlightColor, duration) {
    var highlightBg = highlightColor || "#FFFF9C";
    var animateMs = duration || 1500;
    var originalBg = this.css("backgroundColor");
    this.stop().css("background-color", highlightBg).animate({backgroundColor: originalBg}, animateMs);
};

jQuery(function(){
	
	// Ajax add to cart
	jQuery('.add_to_cart_button').live('click', function() {
		
		// AJAX add to cart request
		var thisbutton = jQuery(this);
		
		if (thisbutton.is('.product_type_simple')) {
	
			jQuery(thisbutton).addClass('loading');
			
			var data = {
				action: 		'woocommerce_add_to_cart',
				product_id: 	jQuery(thisbutton).attr('rel'),
				security: 		woocommerce_params.add_to_cart_nonce
			};
			
			// Trigger event
			jQuery('body').trigger('adding_to_cart');
			
			// Ajax action
			jQuery.post( woocommerce_params.ajax_url, data, function(response) {
				
				// Get response
				data = jQuery.parseJSON( response );
				
				if (data.error) {
					alert(data.error);
					jQuery(thisbutton).removeClass('loading');
					return;
				}
				
				fragments = data;

				// Block fragments class
				if (fragments) {
					jQuery.each(fragments, function(key, value) {
						jQuery(key).addClass('updating');
					});
				}
				
				// Block widgets and fragments
				jQuery('.widget_shopping_cart, .shop_table.cart, .updating').fadeTo('400', '0.6').block({message: null, overlayCSS: {background: 'transparent url(' + woocommerce_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6}});
				
				// Changes button classes
				jQuery(thisbutton).addClass('added');
				jQuery(thisbutton).removeClass('loading');

				// Cart widget load
				jQuery('.widget_shopping_cart:eq(0)').load( window.location + ' .widget_shopping_cart:eq(0) > *', function() {
					
					// Replace fragments
					if (fragments) {
						jQuery.each(fragments, function(key, value) {
							jQuery(key).replaceWith(value);
						});
					}
					
					// Unblock
					jQuery('.widget_shopping_cart, .updating').css('opacity', '1').unblock();
				} );
				
				// Cart load
				jQuery('.shop_table.cart').load( window.location + ' .shop_table.cart:eq(0) > *', function() {
					
					jQuery("div.quantity:not(.buttons_added), td.quantity:not(.buttons_added)").addClass('buttons_added').append('<input type="button" value="+" id="add1" class="plus" />').prepend('<input type="button" value="-" id="minus1" class="minus" />');
					
					jQuery('.shop_table.cart').css('opacity', '1').unblock();
					
				} );
				
				// Trigger event so themes can refresh other areas
				jQuery('body').trigger('added_to_cart');
		
			});
			
			return false;
		
		} else {
			return true;
		}
		
	});
	
	// Orderby
	jQuery('select.orderby').change(function(){
		jQuery(this).closest('form').submit();
	});
	
	// Lightbox
	jQuery('a.zoom').fancybox({
		'transitionIn'	:	'elastic',
		'transitionOut'	:	'elastic',
		'speedIn'		:	600, 
		'speedOut'		:	200, 
		'overlayShow'	:	true
	});
	
	// Star ratings
	jQuery('#rating').hide().before('<p class="stars"><span><a class="star-1" href="#">1</a><a class="star-2" href="#">2</a><a class="star-3" href="#">3</a><a class="star-4" href="#">4</a><a class="star-5" href="#">5</a></span></p>');
	
	jQuery('p.stars a').click(function(){
		jQuery('#rating').val(jQuery(this).text());
		jQuery('p.stars a').removeClass('active');
		jQuery(this).addClass('active');
		return false;
	});

	// Price slider
	var min_price = jQuery('.price_slider_amount #min_price').val();
	var max_price = jQuery('.price_slider_amount #max_price').val();
	
	if (woocommerce_params.min_price) {
		current_min_price = woocommerce_params.min_price;
	} else {
		current_min_price = min_price;
	}
	
	if (woocommerce_params.max_price) {
		current_max_price = woocommerce_params.max_price;
	} else {
		current_max_price = max_price;
	}
	
	current_min_price = parseInt(current_min_price);
	current_max_price = parseInt(current_max_price);
	
	jQuery('.price_slider').slider({
		range: true,
		animate: true,
		min: min_price,
		max: max_price,
		values: [current_min_price,current_max_price],
		create : function( event, ui ) {

			if (woocommerce_params.currency_pos == "left"){
				jQuery( ".price_slider_amount span" ).html( woocommerce_params.currency_symbol + current_min_price + " - " + woocommerce_params.currency_symbol + current_max_price );
			} else if (woocommerce_params.currency_pos == "left_space") {
				jQuery( ".price_slider_amount span" ).html( woocommerce_params.currency_symbol + " " + current_min_price + " - " + woocommerce_params.currency_symbol + " " + current_max_price );
			} else if (woocommerce_params.currency_pos == "right") {
				jQuery( ".price_slider_amount span" ).html( current_min_price + woocommerce_params.currency_symbol + " - " + current_max_price + woocommerce_params.currency_symbol );
			} else if (woocommerce_params.currency_pos == "right_space") {
				jQuery( ".price_slider_amount span" ).html( current_min_price + " " + woocommerce_params.currency_symbol + " - " + current_max_price + " " + woocommerce_params.currency_symbol );
			}
			
			jQuery( ".price_slider_amount #min_price" ).val(current_min_price);
			jQuery( ".price_slider_amount #max_price" ).val(current_max_price);
		},
		slide: function( event, ui ) {
			
			if (woocommerce_params.currency_pos == "left"){
				jQuery( ".price_slider_amount span" ).html( woocommerce_params.currency_symbol + ui.values[ 0 ] + " - " + woocommerce_params.currency_symbol + ui.values[ 1 ] );
			} else if (woocommerce_params.currency_pos == "left_space") {
				jQuery( ".price_slider_amount span" ).html( woocommerce_params.currency_symbol + " " + ui.values[ 0 ] + " - " + woocommerce_params.currency_symbol + " " + ui.values[ 1 ] );
			} else if (woocommerce_params.currency_pos == "right") {
				jQuery( ".price_slider_amount span" ).html( ui.values[ 0 ] + woocommerce_params.currency_symbol + " - " + ui.values[ 1 ] + woocommerce_params.currency_symbol );
			} else if (woocommerce_params.currency_pos == "right_space") {
				jQuery( ".price_slider_amount span" ).html( ui.values[ 0 ] + " " + woocommerce_params.currency_symbol + " - " + ui.values[ 1 ] + " " + woocommerce_params.currency_symbol );
			}
			jQuery( "input#min_price" ).val(ui.values[ 0 ]);
			jQuery( "input#max_price" ).val(ui.values[ 1 ]);
		}
	});
			
	// Quantity buttons
	jQuery("div.quantity:not(.buttons_added), td.quantity:not(.buttons_added)").addClass('buttons_added').append('<input type="button" value="+" id="add1" class="plus" />').prepend('<input type="button" value="-" id="minus1" class="minus" />');
	
	jQuery(".plus").live('click', function()
	{
	    var currentVal = parseInt(jQuery(this).prev(".qty").val());
	   
	    if (!currentVal || currentVal=="" || currentVal == "NaN") currentVal = 0;
	    
	    jQuery(this).prev(".qty").val(currentVal + 1); 
	});
	
	jQuery(".minus").live('click', function()
	{
	    var currentVal = parseInt(jQuery(this).next(".qty").val());
	    if (currentVal == "NaN") currentVal = 1;
	    if (currentVal > 1)
	    {
	        jQuery(this).next(".qty").val(currentVal - 1);
	   }
	});
	
	/* states */
	var states_json = woocommerce_params.countries.replace(/&quot;/g, '"');
	var states = jQuery.parseJSON( states_json );			
	
	jQuery('select.country_to_state').change(function(){
		
		var country = jQuery(this).val();
		var state_box = jQuery('#' + jQuery(this).attr('rel'));
		
		var input_name = jQuery(state_box).attr('name');
		var input_id = jQuery(state_box).attr('id');

		if (states[country]) {
			var options = '';
			var state = states[country];
			for(var index in state) {
				options = options + '<option value="' + index + '">' + state[index] + '</option>';
			}
			if (jQuery(state_box).is('input')) {
				// Change for select
				jQuery(state_box).replaceWith('<select name="' + input_name + '" id="' + input_id + '"><option value="">' + woocommerce_params.select_state_text + '</option></select>');
				state_box = jQuery('#' + jQuery(this).attr('rel'));
			}
			jQuery(state_box).append(options);
		} else {
			if (jQuery(state_box).is('select')) {
				jQuery(state_box).replaceWith('<input type="text" placeholder="' + woocommerce_params.state_text + '" name="' + input_name + '" id="' + input_id + '" />');
				state_box = jQuery('#' + jQuery(this).attr('rel'));
			}
		}
		
	}).change();
	
	/* Tabs */
	jQuery('#tabs .panel:not(#tabs .panel)').hide();
	jQuery('#tabs li a').click(function(){
		var href = jQuery(this).attr('href');
		jQuery('#tabs li').removeClass('active');
		jQuery('div.panel').hide();
		jQuery('div' + href).show();
		jQuery(this).parent().addClass('active');
		jQuery.cookie('current_tab', href);
		return false;
	});
	if (jQuery('#tabs li.active').size()==0) {
		jQuery('#tabs li:first a').click();
	} else {
		jQuery('#tabs li.active a').click();
	}
	
	/* Shipping calculator */
	
	jQuery('.shipping-calculator-form').hide();
	
	jQuery('.shipping-calculator-button').click(function() {
	  jQuery('.shipping-calculator-form').slideToggle('slow', function() {
	    // Animation complete.
	 });
	}); 
	
	// Stop anchors moving the viewport

	jQuery(".shipping-calculator-button").click(function() {return false;});
	
	// Variations
	
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
        
        var current_attr_select = jQuery('.variations select').not('[disabled]').last();
        current_attr_select.find('option:gt(0)').attr('disabled', 'disabled');
        
        var current_attr_name = current_attr_select.attr('name');
        
        for(num in variations) {
            var attributes = variations[num].attributes;
            
            for(attr_name in attributes) {
                var attr_val = attributes[attr_name];
                
                if(attr_name == current_attr_name) {
                    current_attr_select.find('option[value="'+attr_val+'"]').removeAttr('disabled');
                }
            }
        }
        
    }
    
    //show single variation details (price, stock, image)
    function show_variation(variation) {
        var img = jQuery('div.images img:eq(0)');
        var link = jQuery('div.images a.zoom');
        var o_src = jQuery(img).attr('original-src');
        var o_link = jQuery(link).attr('original-href');

        var variation_image = variation.image_src;
        var variation_link = variation.image_link;

        jQuery('.single_variation').html( variation.price_html + variation.availability_html );

        if (!o_src) {
            jQuery(img).attr('original-src', jQuery(img).attr('src'));
        }

        if (!o_link) {
            jQuery(link).attr('original-href', jQuery(link).attr('href'));
        }

        if (variation_image && variation_image.length > 1) {	
            jQuery(img).attr('src', variation_image);
            jQuery(link).attr('href', variation_link);
        } else {
            jQuery(img).attr('src', o_src);
            jQuery(link).attr('href', o_link);
        }

        jQuery('.single_variation_wrap').slideDown();
    }
	
	//when one of attributes is changed - check everything to show only valid options
    function check_variations() {
        jQuery('form input[name=variation_id]').val('');
        jQuery('.single_variation_wrap').hide();
        jQuery('.single_variation').text('');
        
		var all_set = true;
		var current_settings = {};
        
		jQuery('.variations select').each(function(){
			if (jQuery(this).val().length == 0) {
                all_set = false;
            }
            current_settings[jQuery(this).attr('name')] = jQuery(this).val();
		});
        
        var matching_variations = find_matching_variations(current_settings);
        
        if(all_set) {
            var variation = matching_variations.pop();
            
            jQuery('form input[name=variation_id]').val(variation.variation_id);
            show_variation(variation);
        } else {
            update_variation_values(matching_variations);
        }
    }

	jQuery('.variations select').change(function(){
        //make sure that only selects before this one, and one after this are enabled
        var index = jQuery(this).data('index');
        
        if(jQuery(this).val().length > 0) {
            index += 1;
        }
        
        var selects = jQuery('.variations select');
        selects.filter(':lt('+index+')').removeAttr('disabled');
        selects.filter(':eq('+index+')').removeAttr('disabled').val('');
        selects.filter(':gt('+index+')').attr('disabled', 'disabled').val('');
        
		check_variations(jQuery(this));
		
		if(jQuery().uniform) {
			jQuery.uniform.update();
		}
	});
    
    //disable all but first select field
    jQuery('.variations select:gt(0)').attr('disabled', 'disabled');
    
    // index all selects
    jQuery.each(jQuery('.variations select'), function(i, item){
        jQuery(item).data('index', i);
    });
	
	
});

if (woocommerce_params.is_checkout==1) {

	var updateTimer;
	
	function update_checkout() {
	
		var method = jQuery('#shipping_method').val();
		
		var country 	= jQuery('#billing-country').val();
		var state 		= jQuery('#billing-state').val();
		var postcode 	= jQuery('input#billing-postcode').val();
			
		if (jQuery('#shiptobilling input').is(':checked') || jQuery('#shiptobilling input').size()==0) {
			var s_country 	= jQuery('#billing-country').val();
			var s_state 	= jQuery('#billing-state').val();
			var s_postcode 	= jQuery('input#billing-postcode').val();
			
		} else {
			var s_country 	= jQuery('#shipping-country').val();
			var s_state 	= jQuery('#shipping-state').val();
			var s_postcode 	= jQuery('input#shipping-postcode').val();
		}
		
		jQuery('#order_methods, #order_review').block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6}});
		
		var data = {
			action: 			'woocommerce_update_order_review',
			security: 			woocommerce_params.update_order_review_nonce,
			shipping_method: 	method, 
			country: 			country, 
			state: 				state, 
			postcode: 			postcode, 
			s_country: 			s_country, 
			s_state: 			s_state, 
			s_postcode: 		s_postcode
		};
			
		jQuery.post( woocommerce_params.ajax_url, data, function(response) {
		
			jQuery('#order_methods, #order_review').remove();
			jQuery('#order_review_heading').after(response);
			jQuery('#order_review input[name=payment_method]:checked').click();
		
		});
	
	}
		
	jQuery(function(){
		
		jQuery('p.password').hide();
		
		jQuery('input.show_password').change(function(){
			jQuery('p.password').slideToggle();
		});
		
		jQuery('div.shipping-address').hide();
		
		jQuery('#shiptobilling input').change(function(){
			jQuery('div.shipping-address').hide();
			if (!jQuery(this).is(':checked')) {
				jQuery('div.shipping-address').slideDown();
			}
		}).change();
		
		if (woocommerce_params.option_guest_checkout=='yes') {
			
			jQuery('div.create-account').hide();
			
			jQuery('input#createaccount').change(function(){
				jQuery('div.create-account').hide();
				if (jQuery(this).is(':checked')) {
					jQuery('div.create-account').slideDown();
				}
			}).change();
		
		}
		
		jQuery('.payment_methods input.input-radio').live('click', function(){
			jQuery('div.payment_box').hide();
			if (jQuery(this).is(':checked')) {
				jQuery('div.payment_box.' + jQuery(this).attr('ID')).slideDown();
			}
		});
		
		jQuery('#order_review input[name=payment_method]:checked').click();
		
		jQuery('form.login').hide();
		
		jQuery('a.showlogin').click(function(){
			jQuery('form.login').slideToggle();
		});
		
		/* Update totals */
		jQuery('#shipping_method').live('change', function(){
			clearTimeout(updateTimer);
			update_checkout();
		}).change();
		jQuery('input#billing-country, input#billing-state, #billing-postcode, input#shipping-country, input#shipping-state, #shipping-postcode').live('keydown', function(){
			clearTimeout(updateTimer);
			updateTimer = setTimeout("update_checkout()", '1000');
		});
		jQuery('select#billing-country, select#billing-state, select#shipping-country, select#shipping-state, #shiptobilling input').live('change', function(){
			clearTimeout(updateTimer);
			update_checkout();
		});
		
		/* AJAX Form Submission */
		jQuery('form.checkout').submit(function(){
			var form = this;
			jQuery(form).block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6}});
			jQuery.ajax({
				type: 		'POST',
				url: 		woocommerce_params.checkout_url,
				data: 		jQuery(form).serialize(),
				success: 	function( code ) {
								jQuery('.woocommerce_error, .woocommerce_message').remove();
								try {
									success = jQuery.parseJSON( code );					
									window.location = decodeURI(success.redirect);
								}
								catch(err) {
								  	jQuery(form).prepend( code );
									jQuery(form).unblock(); 
									
									jQuery('html, body').animate({
									    scrollTop: (jQuery('form.checkout').offset().top - 100)
									}, 1000);
								}
							},
				dataType: 	"html"
			});
			return false;
		});
	
	});
	
}