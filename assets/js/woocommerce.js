jQuery.fn.animateHighlight = function(highlightColor, duration) {
    var highlightBg = highlightColor || "#FFFF9C";
    var animateMs = duration || 1500;
    var originalBg = this.css("backgroundColor");
    this.stop().css("background-color", highlightBg).animate({backgroundColor: originalBg}, animateMs);
};

jQuery(document).ready(function($) {
	
	// Ajax add to cart
	$('.add_to_cart_button').live('click', function() {
		
		// AJAX add to cart request
		var thisbutton = $(this);
		
		if (thisbutton.is('.product_type_simple')) {
	
			$(thisbutton).addClass('loading');
			
			var data = {
				action: 		'woocommerce_add_to_cart',
				product_id: 	$(thisbutton).attr('rel'),
				security: 		woocommerce_params.add_to_cart_nonce
			};
			
			// Trigger event
			$('body').trigger('adding_to_cart');
			
			// Ajax action
			$.post( woocommerce_params.ajax_url, data, function(response) {
				
				// Get response
				data = $.parseJSON( response );
				
				if (data.error) {
					alert(data.error);
					$(thisbutton).removeClass('loading');
					return;
				}
				
				fragments = data;

				// Block fragments class
				if (fragments) {
					$.each(fragments, function(key, value) {
						$(key).addClass('updating');
					});
				}
				
				// Block widgets and fragments
				$('.widget_shopping_cart, .shop_table.cart, .updating').fadeTo('400', '0.6').block({message: null, overlayCSS: {background: 'transparent url(' + woocommerce_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6}});
				
				// Changes button classes
				$(thisbutton).addClass('added');
				$(thisbutton).removeClass('loading');

				// Cart widget load
				$('.widget_shopping_cart:eq(0)').load( window.location + ' .widget_shopping_cart:eq(0) > *', function() {
					
					// Replace fragments
					if (fragments) {
						$.each(fragments, function(key, value) {
							$(key).replaceWith(value);
						});
					}
					
					// Unblock
					$('.widget_shopping_cart, .updating').css('opacity', '1').unblock();
				} );
				
				// Cart load
				$('.shop_table.cart').load( window.location + ' .shop_table.cart:eq(0) > *', function() {
					
					$("div.quantity:not(.buttons_added), td.quantity:not(.buttons_added)").addClass('buttons_added').append('<input type="button" value="+" id="add1" class="plus" />').prepend('<input type="button" value="-" id="minus1" class="minus" />');
					
					$('.shop_table.cart').css('opacity', '1').unblock();
					
				} );
				
				// Trigger event so themes can refresh other areas
				$('body').trigger('added_to_cart');
		
			});
			
			return false;
		
		} else {
			return true;
		}
		
	});
	
	// Orderby
	$('select.orderby').change(function(){
		$(this).closest('form').submit();
	});
	
	// Star ratings
	$('#rating').hide().before('<p class="stars"><span><a class="star-1" href="#">1</a><a class="star-2" href="#">2</a><a class="star-3" href="#">3</a><a class="star-4" href="#">4</a><a class="star-5" href="#">5</a></span></p>');
	
	$('p.stars a').click(function(){
		$('#rating').val($(this).text());
		$('p.stars a').removeClass('active');
		$(this).addClass('active');
		return false;
	});

	// Price slider
	var min_price = $('.price_slider_amount #min_price').val();
	var max_price = $('.price_slider_amount #max_price').val();
	
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
	
	$('.price_slider').slider({
		range: true,
		animate: true,
		min: min_price,
		max: max_price,
		values: [current_min_price,current_max_price],
		create : function( event, ui ) {

			if (woocommerce_params.currency_pos == "left"){
				$( ".price_slider_amount span" ).html( woocommerce_params.currency_symbol + current_min_price + " - " + woocommerce_params.currency_symbol + current_max_price );
			} else if (woocommerce_params.currency_pos == "left_space") {
				$( ".price_slider_amount span" ).html( woocommerce_params.currency_symbol + " " + current_min_price + " - " + woocommerce_params.currency_symbol + " " + current_max_price );
			} else if (woocommerce_params.currency_pos == "right") {
				$( ".price_slider_amount span" ).html( current_min_price + woocommerce_params.currency_symbol + " - " + current_max_price + woocommerce_params.currency_symbol );
			} else if (woocommerce_params.currency_pos == "right_space") {
				$( ".price_slider_amount span" ).html( current_min_price + " " + woocommerce_params.currency_symbol + " - " + current_max_price + " " + woocommerce_params.currency_symbol );
			}
			
			$( ".price_slider_amount #min_price" ).val(current_min_price);
			$( ".price_slider_amount #max_price" ).val(current_max_price);
		},
		slide: function( event, ui ) {
			
			if (woocommerce_params.currency_pos == "left"){
				$( ".price_slider_amount span" ).html( woocommerce_params.currency_symbol + ui.values[ 0 ] + " - " + woocommerce_params.currency_symbol + ui.values[ 1 ] );
			} else if (woocommerce_params.currency_pos == "left_space") {
				$( ".price_slider_amount span" ).html( woocommerce_params.currency_symbol + " " + ui.values[ 0 ] + " - " + woocommerce_params.currency_symbol + " " + ui.values[ 1 ] );
			} else if (woocommerce_params.currency_pos == "right") {
				$( ".price_slider_amount span" ).html( ui.values[ 0 ] + woocommerce_params.currency_symbol + " - " + ui.values[ 1 ] + woocommerce_params.currency_symbol );
			} else if (woocommerce_params.currency_pos == "right_space") {
				$( ".price_slider_amount span" ).html( ui.values[ 0 ] + " " + woocommerce_params.currency_symbol + " - " + ui.values[ 1 ] + " " + woocommerce_params.currency_symbol );
			}
			$( "input#min_price" ).val(ui.values[ 0 ]);
			$( "input#max_price" ).val(ui.values[ 1 ]);
		}
	});
			
	// Quantity buttons
	$("div.quantity:not(.buttons_added), td.quantity:not(.buttons_added)").addClass('buttons_added').append('<input type="button" value="+" id="add1" class="plus" />').prepend('<input type="button" value="-" id="minus1" class="minus" />');
	
	$(".plus").live('click', function()
	{
	    var currentVal = parseInt($(this).prev(".qty").val());
	   
	    if (!currentVal || currentVal=="" || currentVal == "NaN") currentVal = 0;
	    
	    $(this).prev(".qty").val(currentVal + 1); 
	});
	
	$(".minus").live('click', function()
	{
	    var currentVal = parseInt($(this).next(".qty").val());
	    if (currentVal == "NaN") currentVal = 1;
	    if (currentVal > 1)
	    {
	        $(this).next(".qty").val(currentVal - 1);
	   }
	});
	
	/* states */
	var states_json = woocommerce_params.countries.replace(/&quot;/g, '"');
	var states = $.parseJSON( states_json );			
	
	$('select.country_to_state').change(function(){
		
		var country = $(this).val();
		var state_box = $('#' + $(this).attr('rel'));
		
		var input_name = $(state_box).attr('name');
		var input_id = $(state_box).attr('id');

		if (states[country]) {
			var options = '';
			var state = states[country];
			for(var index in state) {
				options = options + '<option value="' + index + '">' + state[index] + '</option>';
			}
			if ($(state_box).is('input')) {
				// Change for select
				$(state_box).replaceWith('<select name="' + input_name + '" id="' + input_id + '"><option value="">' + woocommerce_params.select_state_text + '</option></select>');
				state_box = $('#' + $(this).attr('rel'));
			}
			$(state_box).append(options);
		} else {
			if ($(state_box).is('select')) {
				$(state_box).replaceWith('<input type="text" placeholder="' + woocommerce_params.state_text + '" name="' + input_name + '" id="' + input_id + '" />');
				state_box = $('#' + $(this).attr('rel'));
			}
		}
		
	}).change();
	
	/* Tabs */
	$('div.woocommerce_tabs .panel').hide();
	$('div.woocommerce_tabs ul.tabs li a').click(function(){
	
		var tabs_wrapper = $(this).closest('div.woocommerce_tabs');
		var href = $(this).attr('href');
		
		$('ul.tabs li.active', tabs_wrapper).removeClass('active');
		$('div.panel', tabs_wrapper).hide();
		$('div' + href).show();
		$(this).parent().addClass('active');
		$.cookie('current_tab', href);
		
		return false;	
	});
	$('div.woocommerce_tabs').each(function() {
		if ($('ul.tabs li.active', $(this)).size()==0) {
			$('ul.tabs li:first a', $(this)).click();
		} else { 
			$('ul.tabs li.active a', $(this)).click();
		}
	});
	
	/* Shipping calculator */
	$('.shipping-calculator-form').hide();
	
	$('.shipping-calculator-button').click(function() {
	  $('.shipping-calculator-form').slideToggle('slow', function() {
	    // Animation complete.
	 });
	}); 
	
	// Stop anchors moving the viewport

	$(".shipping-calculator-button").click(function() {return false;});
	
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
        
        var current_attr_select = $('.variations select').not('[disabled]').last();
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
        var img = $('div.images img:eq(0)');
        var link = $('div.images a.zoom');
        var o_src = $(img).attr('original-src');
        var o_link = $(link).attr('original-href');

        var variation_image = variation.image_src;
        var variation_link = variation.image_link;

        $('.single_variation').html( variation.price_html + variation.availability_html );

        if (!o_src) {
            $(img).attr('original-src', $(img).attr('src'));
        }

        if (!o_link) {
            $(link).attr('original-href', $(link).attr('href'));
        }

        if (variation_image && variation_image.length > 1) {	
            $(img).attr('src', variation_image);
            $(link).attr('href', variation_link);
        } else {
            $(img).attr('src', o_src);
            $(link).attr('href', o_link);
        }

        $('.single_variation_wrap').slideDown();
    }
	
	//when one of attributes is changed - check everything to show only valid options
    function check_variations() {
        $('form input[name=variation_id]').val('');
        $('.single_variation_wrap').hide();
        $('.single_variation').text('');
        
		var all_set = true;
		var current_settings = {};
        
		$('.variations select').each(function(){
			if ($(this).val().length == 0) {
                all_set = false;
            }
            current_settings[$(this).attr('name')] = $(this).val();
		});
        
        var matching_variations = find_matching_variations(current_settings);
        
        if(all_set) {
            var variation = matching_variations.pop();
            
            $('form input[name=variation_id]').val(variation.variation_id);
            show_variation(variation);
        } else {
            update_variation_values(matching_variations);
        }
    }

	$('.variations select').change(function(){
        //make sure that only selects before this one, and one after this are enabled
        var index = $(this).data('index');
        
        if($(this).val().length > 0) {
            index += 1;
        }
        
        var selects = $('.variations select');
        selects.filter(':lt('+index+')').removeAttr('disabled');
        selects.filter(':eq('+index+')').removeAttr('disabled').val('');
        selects.filter(':gt('+index+')').attr('disabled', 'disabled').val('');
        
		check_variations($(this));
		
		if($().uniform) {
			$.uniform.update();
		}
	});
    
    //disable all but first select field
    $('.variations select:gt(0)').attr('disabled', 'disabled');
    
    // index all selects
    $.each($('.variations select'), function(i, item){
        $(item).data('index', i);
    });
	
	if (woocommerce_params.is_checkout==1) {
	
		var updateTimer;
		
		function update_checkout() {
		
			var method = $('#shipping_method').val();
			
			var country 	= $('#billing-country').val();
			var state 		= $('#billing-state').val();
			var postcode 	= $('input#billing-postcode').val();
				
			if ($('#shiptobilling input').is(':checked') || $('#shiptobilling input').size()==0) {
				var s_country 	= $('#billing-country').val();
				var s_state 	= $('#billing-state').val();
				var s_postcode 	= $('input#billing-postcode').val();
				
			} else {
				var s_country 	= $('#shipping-country').val();
				var s_state 	= $('#shipping-state').val();
				var s_postcode 	= $('input#shipping-postcode').val();
			}
			
			$('#order_methods, #order_review').block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6}});
			
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
				
			$.post( woocommerce_params.ajax_url, data, function(response) {
			
				$('#order_methods, #order_review').remove();
				$('#order_review_heading').after(response);
				$('#order_review input[name=payment_method]:checked').click();
			
			});
		
		}
			
		$(function(){
			
			$('p.password').hide();
			
			$('input.show_password').change(function(){
				$('p.password').slideToggle();
			});
			
			$('div.shipping-address').hide();
			
			$('#shiptobilling input').change(function(){
				$('div.shipping-address').hide();
				if (!$(this).is(':checked')) {
					$('div.shipping-address').slideDown();
				}
			}).change();
			
			if (woocommerce_params.option_guest_checkout=='yes') {
				
				$('div.create-account').hide();
				
				$('input#createaccount').change(function(){
					$('div.create-account').hide();
					if ($(this).is(':checked')) {
						$('div.create-account').slideDown();
					}
				}).change();
			
			}
			
			$('.payment_methods input.input-radio').live('click', function(){
				$('div.payment_box').hide();
				if ($(this).is(':checked')) {
					$('div.payment_box.' + $(this).attr('ID')).slideDown();
				}
			});
			
			$('#order_review input[name=payment_method]:checked').click();
			
			$('form.login').hide();
			
			$('a.showlogin').click(function(){
				$('form.login').slideToggle();
			});
			
			/* Update totals */
			$('#shipping_method').live('change', function(){
				clearTimeout(updateTimer);
				update_checkout();
			}).change();
			$('input#billing-country, input#billing-state, #billing-postcode, input#shipping-country, input#shipping-state, #shipping-postcode').live('keydown', function(){
				clearTimeout(updateTimer);
				updateTimer = setTimeout("update_checkout()", '1000');
			});
			$('select#billing-country, select#billing-state, select#shipping-country, select#shipping-state, #shiptobilling input').live('change', function(){
				clearTimeout(updateTimer);
				update_checkout();
			});
			
			/* AJAX Form Submission */
			$('form.checkout').submit(function(){
				var form = this;
				$(form).block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6}});
				$.ajax({
					type: 		'POST',
					url: 		woocommerce_params.checkout_url,
					data: 		$(form).serialize(),
					success: 	function( code ) {
									$('.woocommerce_error, .woocommerce_message').remove();
									try {
										success = $.parseJSON( code );					
										window.location = decodeURI(success.redirect);
									}
									catch(err) {
									  	$(form).prepend( code );
										$(form).unblock(); 
										
										$('html, body').animate({
										    scrollTop: ($('form.checkout').offset().top - 100)
										}, 1000);
									}
								},
					dataType: 	"html"
				});
				return false;
			});
		
		});
	}

});