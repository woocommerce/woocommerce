/* global wc_cart_params */
jQuery( function( $ ) {

	// Check if a node is blocked for processing.
	var is_blocked = function( $node ) {
		return $node.is( '.processing' );
	};

	// Block a node for processing.
	var block = function( $node ) {
		$node.addClass( 'processing' ).block( {
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});
	};

	// Unblock a node after processing is complete.
	var unblock = function( $node ) {
		$node.removeClass( 'processing' ).unblock();
	};

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

		block( $( 'div.cart_totals' ) );

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
		block( $( 'div.cart_totals' ) );

		var url = wc_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'get_cart_totals' );

		$.ajax( {
			url:     url,
			success: function( response ) {
				$( 'div.cart_totals' ).replaceWith( response );
			}
		});
	};

	// Clears previous notices and shows new one above form.
	var show_notice = function( html_element ) {
		var $form = $( 'div.woocommerce > form' );

		$( '.woocommerce-error, .woocommerce-message' ).remove();
		$form.before( html_element );
	};

	// Coupon code
	$( '[name="apply_coupon"]' ).on( 'click', function( evt ) {
		evt.preventDefault();

		var $form = $( 'div.woocommerce > form' );

		if ( is_blocked( $form ) ) {
			return false;
		}

		block( $form );

		var $text_field = $( '#coupon_code' );
		var coupon_code = $text_field.val();

		var url = wc_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'apply_coupon' );
		var data = {
			security: wc_cart_params.apply_coupon_nonce,
			coupon_code: coupon_code
		};

		$.ajax( {
			type:     'POST',
			url:      url,
			data:     data,
			dataType: 'html',
			success: function( response ) {
				show_notice( response );
			},
			complete: function() {
				unblock( $form );
				$text_field.val( '' );
				update_cart_totals();
			}
		});
	});

	$( document ).on( 'click', 'a.woocommerce-remove-coupon', function( evt ) {
		evt.preventDefault();

		var $tr = $( this ).parents( 'tr' );
		var coupon = $( this ).attr( 'data-coupon' );

		block( $tr.parents( 'table' ) );

		var url = wc_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'remove_coupon' );
		var data = {
			security: wc_cart_params.remove_coupon_nonce,
			coupon: coupon
		};

		$.ajax( {
			type:    'POST',
			url:      url,
			data:     data,
			dataType: 'html',
			success: function( response ) {
				show_notice( response );
				unblock( $tr.parents( 'table' ) );
			},
			complete: function() {
				update_cart_totals();
			}
		});
	});
});
