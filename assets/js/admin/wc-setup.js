jQuery(function( $ ) {

	var locale_info = $.parseJSON( wc_setup_params.locale_info );

	$('select[name="store_location"]').change(function(){
		var country_option      = $(this).val();
		var country             = country_option.split( ':' )[0];
		var country_locale_info = locale_info[ country ];

		if ( country_locale_info ) {
			$('select[name="store_currency"]').val( country_locale_info['currency_code'] ).change();
			$(':input[name="currency_pos"]').val( country_locale_info['currency_pos'] ).change().closest('tr').hide();
			$(':input[name="thousand_sep"]').val( country_locale_info['thousand_sep'] ).change().closest('tr').hide();
			$(':input[name="decimal_sep"]').val( country_locale_info['decimal_sep'] ).change().closest('tr').hide();
			$(':input[name="num_decimals"]').val( country_locale_info['num_decimals'] ).change().closest('tr').hide();
		} else {
			$(':input[name="currency_pos"]').val( 'left' ).change().closest('tr').show();
			$(':input[name="thousand_sep"]').val( '.' ).change().closest('tr').show();
			$(':input[name="decimal_sep"]').val( '.' ).change().closest('tr').show();
			$(':input[name="num_decimals"]').val( '2' ).change().closest('tr').show();
		}
	}).change();

});