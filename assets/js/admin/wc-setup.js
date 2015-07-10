/*global wc_setup_params */
jQuery(function( $ ) {

	var locale_info = $.parseJSON( wc_setup_params.locale_info );

	$('select[name="store_location"]').change(function(){
		var country_option      = $(this).val();
		var country             = country_option.split( ':' )[0];
		var country_locale_info = locale_info[ country ];
		var hide_if_set = [ 'thousand_sep', 'decimal_sep', 'num_decimals', 'currency_pos' ];

		if ( country_locale_info ) {
			$.each( country_locale_info, function( index, value) {
				$(':input[name="' + index + '"]').val( value ).change();

				if ( -1 !== $.inArray( index, hide_if_set ) ) {
					$(':input[name="' + index + '"]').closest('tr').hide();
				}
			} );
		} else {
			$(':input[name="currency_pos"]').closest('tr').show();
			$(':input[name="thousand_sep"]').closest('tr').show();
			$(':input[name="decimal_sep"]').closest('tr').show();
			$(':input[name="num_decimals"]').closest('tr').show();
		}
	}).change();

	$('input[name="woocommerce_calc_shipping"]').change(function(){
		if ( $(this).is( ':checked' ) ) {
			$(':input[name="shipping_cost_domestic"]').closest('tr').show();
			$(':input[name="shipping_cost_international"]').closest('tr').show();
		} else {
			$(':input[name="shipping_cost_domestic"]').closest('tr').hide();
			$(':input[name="shipping_cost_international"]').closest('tr').hide();
		}
	}).change();

	$('input[name="woocommerce_calc_taxes"]').change(function(){
		if ( $(this).is( ':checked' ) ) {
			$(':input[name="woocommerce_prices_include_tax"], :input[name="woocommerce_import_tax_rates"]').closest('tr').show();
			$('tr.tax-rates').show();
		} else {
			$(':input[name="woocommerce_prices_include_tax"], :input[name="woocommerce_import_tax_rates"]').closest('tr').hide();
			$('tr.tax-rates').hide();
		}
	}).change();

	$('input[name="woocommerce_import_tax_rates"]').change(function(){
		if ( $(this).is( ':checked' ) ) {
			$('.importing-tax-rates').show();
		} else {
			$('.importing-tax-rates').hide();
		}
	}).change();

});
