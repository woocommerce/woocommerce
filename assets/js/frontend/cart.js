/* global wc_cart_params */
jQuery( function( $ ) {

	// wc_cart_params is required to continue, ensure the object exists
	if ( typeof wc_cart_params === 'undefined' ) {
		return false;
	}

	// Gets a url for a given AJAX endpoint.
	var get_url = function( endpoint ) {
		return wc_cart_params.wc_ajax_url.toString().replace(
			'%%endpoint%%',
			endpoint
		);
	};

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
		} );
	};

	// Unblock a node after processing is complete.
	var unblock = function( $node ) {
		$node.removeClass( 'processing' ).unblock();
	};

	// Updates the .woocommerce div with a string of html.
	var update_wc_div = function( html_str ) {
		var $html = $.parseHTML( html_str );
		var $new_div = $( 'div.woocommerce', $html );
		$( 'div.woocommerce' ).replaceWith( $new_div );
	};

	// Shipping calculator
	$( document ).on( 'click', '.shipping-calculator-button', function() {
		$( '.shipping-calculator-form' ).slideToggle( 'slow' );
		return false;
	} ).on( 'change', 'select.shipping_method, input[name^=shipping_method]', function() {
		var shipping_methods = [];

		$( 'select.shipping_method, input[name^=shipping_method][type=radio]:checked, input[name^=shipping_method][type=hidden]' ).each( function() {
			shipping_methods[ $( this ).data( 'index' ) ] = $( this ).val();
		} );

		block( $( 'div.cart_totals' ) );

		var data = {
			security: wc_cart_params.update_shipping_method_nonce,
			shipping_method: shipping_methods
		};

		$.post( get_url( 'update_shipping_method' ), data, function( response ) {
			$( 'div.cart_totals' ).replaceWith( response );
			$( document.body ).trigger( 'updated_shipping_method' );
		} );
	} );

	$( '.shipping-calculator-form' ).hide();

	// Update the cart after something has changed.
	var update_cart_totals = function() {
		block( $( 'div.cart_totals' ) );

		$.ajax( {
			url:      get_url( 'get_cart_totals' ),
			dataType: 'html',
			success: function( response ) {
				$( 'div.cart_totals' ).replaceWith( response );
			}
		} );
	};

	// clears previous notices and shows new one above form.
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

		var data = {
			security: wc_cart_params.apply_coupon_nonce,
			coupon_code: coupon_code
		};

		$.ajax( {
			type:     'POST',
			url:      get_url( 'apply_coupon' ),
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
		} );
	} );

	$( document ).on( 'click', 'a.woocommerce-remove-coupon', function( evt ) {
		evt.preventDefault();

		var $tr = $( this ).parents( 'tr' );
		var coupon = $( this ).attr( 'data-coupon' );

		block( $tr.parents( 'table' ) );

		var data = {
			security: wc_cart_params.remove_coupon_nonce,
			coupon: coupon
		};

		$.ajax( {
			type:    'POST',
			url:      get_url( 'remove_coupon' ),
			data:     data,
			dataType: 'html',
			success: function( response ) {
				show_notice( response );
				unblock( $tr.parents( 'table' ) );
			},
			complete: function() {
				update_cart_totals();
			}
		} );
	} );

	// Quantity Update
	$( document ).on( 'click', '[name=update_cart]', function( evt ) {
		evt.preventDefault();

		var $form = $( 'div.woocommerce > form' );

		// Provide the submit button value because wc-form-handler expects it.
		$( '<input />' ).attr( 'type', 'hidden' )
		                .attr( 'name', 'update_cart' )
		                .attr( 'value', 'Update Cart' )
		                .appendTo( $form );

		block( $form );

		// Make call to actual form post URL.
		$.ajax( {
			type:     $form.attr( 'method' ),
			url:      $form.attr( 'action' ),
			data:     $form.serialize(),
			dataType: 'html',
			success:  update_wc_div,
			complete: function() {
				unblock( $form );
			}
		} );
	} );

	// Item Remove
	$( document ).on( 'click', 'td.product-remove > a', function( evt ) {
		evt.preventDefault();

		var $a = $( evt.target );

		block( $a.parent( 'form' ) );

		$.ajax( {
			type:     'GET',
			url:      $a.attr( 'href' ),
			dataType: 'html',
			success: update_wc_div,
			complete: function() {
				unblock( $a.parent( 'form' ) );
			}
		} );
	} );
} );
