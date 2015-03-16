jQuery(function($) {

	// Coupon type options
	$('select#discount_type').change(function() {

		// Get value
		var select_val = $(this).val();

		if ( select_val === 'fixed_product' || select_val === 'percent_product' ) {
			$( '.limit_usage_to_x_items_field' ).show();
		} else {
			$( '.limit_usage_to_x_items_field' ).hide();
		}

	}).change();

});
