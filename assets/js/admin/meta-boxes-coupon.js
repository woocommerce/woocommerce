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
		},

		/**
		 * Show/hide fields by coupon type options
		 */
		type_options: function() {
			// Get value
			var select_val = $( this ).val();

			if ( select_val !== 'fixed_cart' ) {
				$( '.limit_usage_to_x_items_field' ).show();
				$( '#coupon_amount' ).removeClass( 'wc_input_price' ).addClass( 'wc_input_decimal' );
			} else {
				$( '.limit_usage_to_x_items_field' ).hide();
				$( '#coupon_amount' ).removeClass( 'wc_input_decimal' ).addClass( 'wc_input_price' );
			}
		}
	};

	wc_meta_boxes_coupon_actions.init();
});
