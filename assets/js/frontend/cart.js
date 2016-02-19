/* global wc_cart_params */
jQuery( function( $ ) {

	// wc_cart_params is required to continue, ensure the object exists
	if ( typeof wc_cart_params === 'undefined' ) {
		return false;
	}

	// Utility functions for the file.

	/**
	 * Gets a url for a given AJAX endpoint.
	 *
	 * @param {String} endpoint The AJAX Endpoint
	 * @return {String} The URL to use for the request
	 */
	var get_url = function( endpoint ) {
		return wc_cart_params.wc_ajax_url.toString().replace(
			'%%endpoint%%',
			endpoint
		);
	};

	/**
	 * Check if a node is blocked for processing.
	 *
	 * @param {JQuery Object} $node
	 * @return {bool} True if the DOM Element is UI Blocked, false if not.
	 */
	var is_blocked = function( $node ) {
		return $node.is( '.processing' );
	};

	/**
	 * Block a node visually for processing.
	 *
	 * @param {JQuery Object} $node
	 */
	var block = function( $node ) {
		$node.addClass( 'processing' ).block( {
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		} );
	};

	/**
	 * Unblock a node after processing is complete.
	 *
	 * @param {JQuery Object} $node
	 */
	var unblock = function( $node ) {
		$node.removeClass( 'processing' ).unblock();
	};

	/**
	 * Update the .woocommerce div with a string of html.
	 *
	 * @param {String} html_str The HTML string with which to replace the div.
	 */
	var update_wc_div = function( html_str ) {
		var $html = $.parseHTML( html_str );
		var $new_div = $( 'div.woocommerce', $html );
		$( 'div.woocommerce' ).replaceWith( $new_div );
	};


	/**
	 * Object to handle AJAX calls for cart shipping changes.
	 */
	var cart_shipping = {

		/**
		 * Initialize event handlers and UI state.
		 */
		init: function() {
			$( document ).on(
				'click',
				'.shipping-calculator-button',
				this.toggle_shipping
			);
			$( document ).on(
				'change',
				'select.shipping_method, input[name^=shipping_method]',
				this.shipping_method_selected
			);
			$( document ).on(
				'submit',
				'form.woocommerce-shipping-calculator',
				this.shipping_calculator_submit
			);

			$( '.shipping-calculator-form' ).hide();
		},

		/**
		 * Toggle Shipping Calculator panel
		 */
		toggle_shipping: function() {
			$( '.shipping-calculator-form' ).slideToggle( 'slow' );
			return false;
		},

		/**
		 * Handles when a shipping method is selected.
		 *
		 * @param {Object} evt The JQuery event.
		 */
		shipping_method_selected: function( evt ) {
			var target = evt.target;

			var shipping_methods = [];

			$( 'select.shipping_method, input[name^=shipping_method][type=radio]:checked, input[name^=shipping_method][type=hidden]' ).each( function() {
				shipping_methods[ $( target ).data( 'index' ) ] = $( target ).val();
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
		},

		/**
		 * Handles a shipping calculator form submit.
		 *
		 * @param {Object} evt The JQuery event.
		 */
		shipping_calculator_submit: function( evt ) {
			evt.preventDefault();

			var $form = $( evt.target );

			block( $form );

			// Provide the submit button value because wc-form-handler expects it.
			$( '<input />' ).attr( 'type', 'hidden' )
											.attr( 'name', 'calc_shipping' )
											.attr( 'value', 'x' )
											.appendTo( $form );

			// Make call to actual form post URL.
			$.ajax( {
				type:     $form.attr( 'method' ),
				url:      $form.attr( 'action' ),
				data:     $form.serialize(),
				dataType: 'html',
				success:  function( response ) {
					update_wc_div(response );
				},
				complete: function() {
					unblock( $form );
				}
			} );
		}
	};

	/**
	 * Update the cart after something has changed.
	 */
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

	/**
	 * Clear previous notices and shows new one above form.
	 *
	 * @param {Object} The Notice HTML Element in string or object form.
	 */
	var show_notice = function( html_element ) {
		var $form = $( 'div.woocommerce > form' );

		$( '.woocommerce-error, .woocommerce-message' ).remove();
		$form.before( html_element );
	};

	/**
	 * Handle cart form submit and route to correct logic.
	 *
	 * @param {Object} evt The JQuery event
	 */
	var cart_submit = function( evt ) {
		evt.preventDefault();

		var $form = $( evt.target );
		var $submit = $( document.activeElement );

		window.console.log( $submit );

		if ( is_blocked( $form ) ) {
			return false;
		}

		if ( $submit.is( '[name="update_cart"]' ) || $submit.is( 'input.qty' ) ) {
			window.console.log( 'update cart' );
			quantity_update( $form );

		} else if ( $submit.is( '[name="apply_coupon"]' ) || $submit.is( '#coupon_code' ) ) {
			window.console.log( 'apply coupon' );
			apply_coupon( $form );
		}
	};

	/**
	 * Apply Coupon code
	 *
	 * @param {JQuery Object} $form The cart form.
	 */
	var apply_coupon = function( $form ) {
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
	};

	/**
	 * Handle when a remove coupon link is clicked.
	 *
	 * @param {Object} evt The JQuery event
	 */
	var remove_coupon_clicked = function( evt ) {
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
	};

	/**
	 * Handle a cart Quantity Update
	 *
	 * @param {JQuery Object} $form The cart form.
	 */
	var quantity_update = function( $form ) {

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
	};

	/**
	 * Handle when a remvoe item link is clicked.
	 *
	 * @param {Object} evt The JQuery event
	 */
	var item_remove_clicked = function( evt ) {
		evt.preventDefault();

		var $a = $( evt.target );
		var $form = $a.parents( 'form' );

		block( $form );

		$.ajax( {
			type:     'GET',
			url:      $a.attr( 'href' ),
			dataType: 'html',
			success: update_wc_div,
			complete: function() {
				unblock( $form );
			}
		} );
	};

	$( document ).on( 'submit', 'div.woocommerce > form', cart_submit );
	$( document ).on( 'click', 'a.woocommerce-remove-coupon', remove_coupon_clicked );
	$( document ).on( 'click', 'td.product-remove > a', item_remove_clicked );

	cart_shipping.init();
} );
