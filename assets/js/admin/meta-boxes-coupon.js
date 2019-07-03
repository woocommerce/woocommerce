jQuery(function( $ ) {

	/**
	 * Coupon actions
	 */
	var wc_meta_boxes_coupon_actions = {

		/**
		 * Initialize variations actions
		 */
		init: function() {
			$( 'select#discount_type' )
				.on( 'change', this.type_options )
				.change();

			// Add buttons to coupon screen
			var $coupon_screen = $( '.post-new-php.post-type-shop_coupon' ),
			$code_action       = $coupon_screen.find( '#title' );
			$code_action.after( '<a href="#" class="button generate-coupon-code">Generate code</a>' );
			$( '.button.generate-coupon-code' ).on( 'click', this.generate_coupon_code );
		},

		/**
		 * Show/hide fields by coupon type options
		 */
		type_options: function() {
			// Get value
			var select_val = $( this ).val();

			if ( 'percent' === select_val ) {
				$( '#coupon_amount' ).removeClass( 'wc_input_price' ).addClass( 'wc_input_decimal' );
			} else {
				$( '#coupon_amount' ).removeClass( 'wc_input_decimal' ).addClass( 'wc_input_price' );
			}

			if ( select_val !== 'fixed_cart' ) {
				$( '.limit_usage_to_x_items_field' ).show();
			} else {
				$( '.limit_usage_to_x_items_field' ).hide();
			}
		},

		/**
		 * Generate a random coupon code
		 */
		generate_coupon_code: function( e ) {
			e.preventDefault();
			var $coupon_code_field = $( '#title' ),
				$coupon_code_label = $( '#title-prompt-text' ),
			    $result = '';
			for ( var i = 0; i < woocommerce_admin_meta_boxes_coupon.char_limit; i++ ) {
				$result += woocommerce_admin_meta_boxes_coupon.characters.charAt(
					Math.floor( Math.random() * woocommerce_admin_meta_boxes_coupon.characters.length )
				);
			}
			$result = woocommerce_admin_meta_boxes_coupon.prefix + $result + woocommerce_admin_meta_boxes_coupon.postfix;
			$coupon_code_field.focus().val( $result );
			$coupon_code_label.addClass( 'screen-reader-text' );
		}
	};

	wc_meta_boxes_coupon_actions.init();
});
