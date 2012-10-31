jQuery(document).ready(function($) {

	// Ajax add to cart
	$('.add_to_cart_button').live('click', function() {
		
		// AJAX add to cart request
		var $thisbutton = $(this);
		
		if ($thisbutton.is('.product_type_simple, .product_type_downloadable, .product_type_virtual')) {
			
			if (!$thisbutton.attr('data-product_id')) return true;
			
			$thisbutton.removeClass('added');
			$thisbutton.addClass('loading');
			
			var data = {
				action: 		'woocommerce_add_to_cart',
				product_id: 	$thisbutton.attr('data-product_id'),
				security: 		woocommerce_params.add_to_cart_nonce
			};
			
			// Trigger event
			$('body').trigger('adding_to_cart');
			
			// Ajax action
			$.post( woocommerce_params.ajax_url, data, function(response) {
				
				var this_page = window.location.toString();
				
				this_page = this_page.replace( 'add-to-cart', 'added-to-cart' );
				
				$thisbutton.removeClass('loading');

				// Get response
				data = $.parseJSON( response );
				
				if (data.error && data.product_url) {
					window.location = data.product_url;
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
				$('.widget_shopping_cart, .shop_table.cart, .updating, .cart_totals').fadeTo('400', '0.6').block({message: null, overlayCSS: {background: 'transparent url(' + woocommerce_params.ajax_loader_url + ') no-repeat center', opacity: 0.6 } } );
				
				// Changes button classes
				$thisbutton.addClass('added');

				// Cart widget load
				if ($('.widget_shopping_cart').size()>0) {
					$('.widget_shopping_cart:eq(0)').load( this_page + ' .widget_shopping_cart:eq(0) > *', function() {

						// Replace fragments
						if (fragments) {
							$.each(fragments, function(key, value) {
								$(key).replaceWith(value);
							});
						}
						
						// Unblock
						$('.widget_shopping_cart, .updating').stop(true).css('opacity', '1').unblock();
						
						$('body').trigger('cart_widget_refreshed');
					} );
				} else {
					// Replace fragments
					if (fragments) {
						$.each(fragments, function(key, value) {
							$(key).replaceWith(value);
						});
					}
					
					// Unblock
					$('.widget_shopping_cart, .updating').stop(true).css('opacity', '1').unblock();
				}
				
				// Cart page elements
				$('.shop_table.cart').load( this_page + ' .shop_table.cart:eq(0) > *', function() {
					
					$("div.quantity:not(.buttons_added), td.quantity:not(.buttons_added)").addClass('buttons_added').append('<input type="button" value="+" id="add1" class="plus" />').prepend('<input type="button" value="-" id="minus1" class="minus" />');
					
					$('.shop_table.cart').stop(true).css('opacity', '1').unblock();
					
					$('body').trigger('cart_page_refreshed');
				});
				
				$('.cart_totals').load( this_page + ' .cart_totals:eq(0) > *', function() {
					$('.cart_totals').stop(true).css('opacity', '1').unblock();
				});
				
				// Trigger event so themes can refresh other areas
				$('body').trigger('added_to_cart');
		
			});
			
			return false;
		
		} else {
			return true;
		}
		
	});

});