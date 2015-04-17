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

				if ( -1 != $.inArray( index, hide_if_set ) ) {
					$(':input[name="' + index + '"]').closest('tr').hide();
				}
			} );
		} else {
			$(':input[name="currency_code"]').val( '' ).change();
			$(':input[name="currency_pos"]').val( 'left' ).change().closest('tr').show();
			$(':input[name="thousand_sep"]').val( ',' ).change().closest('tr').show();
			$(':input[name="decimal_sep"]').val( '.' ).change().closest('tr').show();
			$(':input[name="num_decimals"]').val( '2' ).change().closest('tr').show();
		}
	}).change();

});