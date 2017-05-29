/*global wc_cart_update_variation_params */
jQuery( function( $ ) {

	/**
	 * Update variation from the cart.
	 */
	var wc_cart_update_variation = {

		variation_id: {},

		all_match: false,

		selected: {},
		/**
		 * Initialize wc_cart_update_variation
		 */
		init: function() {
			$( 'div.woocommerce' )
				.on( 'change', '.cart-update-variation', this.variation_field_changed );
		},

		/**
		 * Get the select fields and available variations.
		 */
		variation_field_changed: function() {
			var dl                   = $( this ).closest( '.variation' );
			var variation_selects    = $( 'select', dl );
			wc_cart_update_variation
			.variation_id            = $( '.variation_id', dl );
			var cart_item_key        = wc_cart_update_variation.variation_id.data( 'cart_item_key' );
			var available_variations = wc_cart_update_variation_params[ cart_item_key ];
			variation_selects.each( wc_cart_update_variation.get_selected_attribute );
			$.each( available_variations, wc_cart_update_variation.update_variation );
		},

		/**
		 * Get the selected attribute from the selct fields.
		 */
		get_selected_attribute: function( i, select ) {
			var attribute_name          = $( select ).data( 'attribute_name' );
			wc_cart_update_variation
			.selected[ attribute_name ] = $( select ).val();
		},

		/**
		 * Update the .variation_id hidden input
		 */
		update_variation: function( i, available_variation ) {
			$.each( available_variation.attributes, wc_cart_update_variation.compare_selected_to_variation );
			if ( wc_cart_update_variation.all_match ) {
				wc_cart_update_variation.variation_id.val( available_variation.variation_id );
				return false;
			}
		},

		/**
		 * Check which variation the select field configuration matches.
		 */
		compare_selected_to_variation: function( attribute, term ) {
			wc_cart_update_variation.all_match = false;
			if ( term !== wc_cart_update_variation.selected[ attribute ] && term !== '' ) {
				return false;
			}
			wc_cart_update_variation.all_match = true;
		}
	};

	wc_cart_update_variation.init();

});
