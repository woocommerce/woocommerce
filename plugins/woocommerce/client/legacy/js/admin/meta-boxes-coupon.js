/* global woocommerce_admin_meta_boxes_coupon */
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
				.trigger( 'change' );

            this.insert_generate_coupon_code_button();
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
         * Insert generate coupon code buttom HTML.
         */
        insert_generate_coupon_code_button: function() {
			$( '.post-type-shop_coupon' ).find( '#title' ).after(
				'<a href="#" class="button generate-coupon-code">' + woocommerce_admin_meta_boxes_coupon.generate_button_text + '</a>'
			);
        },

		/**
		 * Generate a random coupon code
		 */
		generate_coupon_code: function( e ) {
			e.preventDefault();
			var $coupon_code_field = $( '#title' ),
				$coupon_code_label = $( '#title-prompt-text' ),
			    $result = '';
			for ( var i = 0; i < woocommerce_admin_meta_boxes_coupon.char_length; i++ ) {
				$result += woocommerce_admin_meta_boxes_coupon.characters.charAt(
					Math.floor( Math.random() * woocommerce_admin_meta_boxes_coupon.characters.length )
				);
			}
			$result = woocommerce_admin_meta_boxes_coupon.prefix + $result + woocommerce_admin_meta_boxes_coupon.suffix;
			$coupon_code_field.trigger( 'focus' ).val( $result );
			$coupon_code_label.addClass( 'screen-reader-text' );
		}
	};

	/**
	 * Handles warning about coupons using password protection.
	 */
	const wc_coupon_password_warning = {
		init: function() {
			const $warning = $( '#wc-password-protected-coupon-warning' );
			// Bail out early if necessary.
			if ( 0 === $warning.length ) {
				return;
			}

			const $visibility = $( 'input[name="visibility"]' ),
				  $password_visibility = $( '#visibility-radio-password' ),
				  $password_label = $( 'label[for="visibility-radio-password"]' );

			// For coupons without password, prevent setting it.
			if ( ! $password_visibility.is( ':checked' ) ) {
				$password_visibility.prop( 'disabled', true );
				$password_label.css( 'text-decoration', 'line-through' );
				return;
			}

			$visibility.on(
				'change',
				function() {
					$warning.toggleClass( 'hidden', ! $password_visibility.is( ':checked' ) );
				}
			);
		}
	};

	wc_meta_boxes_coupon_actions.init();
	wc_coupon_password_warning.init();
});
