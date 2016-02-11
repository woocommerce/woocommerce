/* global wc_cart_params */
jQuery( function( $ ) {

	// wc_cart_params is required to continue, ensure the object exists
	if ( typeof wc_cart_params === 'undefined' ) {
		return false;
	}

	// Shipping calculator
	$( document ).on( 'click', '.shipping-calculator-button', function() {
		$( '.shipping-calculator-form' ).slideToggle( 'slow' );
		return false;
	}).on( 'change', 'select.shipping_method, input[name^=shipping_method]', function() {
		var shipping_methods = [];

		$( 'select.shipping_method, input[name^=shipping_method][type=radio]:checked, input[name^=shipping_method][type=hidden]' ).each( function() {
			shipping_methods[ $( this ).data( 'index' ) ] = $( this ).val();
		});

		$( 'div.cart_totals' ).block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});

		var data = {
			security: wc_cart_params.update_shipping_method_nonce,
			shipping_method: shipping_methods
		};

		$.post( wc_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'update_shipping_method' ), data, function( response ) {
			$( 'div.cart_totals' ).replaceWith( response );
			$( document.body ).trigger( 'updated_shipping_method' );
		});
	});

	$( '.shipping-calculator-form' ).hide();

	// Update the cart after something has changed.
	var update_cart_totals = function() {
		$( 'div.cart_totals' ).block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});

		var url = wc_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'get_cart_totals' );

		$.ajax( {
			url:     url,
			success: function( response ) {
				$( 'div.cart_totals' ).replaceWith( response );
			}
		});
	};

	// Coupon code
	$( '[name="apply_coupon"]' ).on( 'click', function( evt ) {
		evt.preventDefault();

		var text_field = $( '#coupon_code' );
		var coupon_code = text_field.val();
		text_field.val( '' );

		var url = wc_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'apply_coupon' );
		var data = {
			security: wc_cart_params.apply_coupon_nonce,
			coupon_code: coupon_code
		};

		$.ajax( {
			type:    'POST',
			url:     url,
			data:    data,
			success: function( response ) {
				window.console.log( 'apply coupon response:' );
				window.console.log( response );
			},
			complete: function() {
				update_cart_totals();
			}
		});
	});
});
