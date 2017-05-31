/*global wc_setup_params */
jQuery( function( $ ) {

	var locale_info = $.parseJSON( wc_setup_params.locale_info );

	$( 'select[name="store_location"]' ).change( function() {
		var country_option      = $(this).val().split( ':' );
		var country             = country_option[0];
		var state               = country_option[1] || false;
		var country_locale_info = locale_info[ country ];
		var hide_if_set = [ 'thousand_sep', 'decimal_sep', 'num_decimals', 'currency_pos' ];

		if ( country_locale_info ) {
			$.each( country_locale_info, function( index, value ) {
				$(':input[name="' + index + '"]').val( value ).change();

				if ( -1 !== $.inArray( index, hide_if_set ) ) {
					$(':input[name="' + index + '"]').closest('tr').hide();
				}
			} );

			if ( ! $.isArray( country_locale_info.tax_rates ) ) {
				var tax_rates = [];

				if ( state && country_locale_info.tax_rates[ state ] ) {
					tax_rates = tax_rates.concat( country_locale_info.tax_rates[ state ] );
				} else if ( country_locale_info.tax_rates[ '' ] ) {
					tax_rates = tax_rates.concat( country_locale_info.tax_rates[ '' ] );
				}

				tax_rates = tax_rates.concat( country_locale_info.tax_rates[ '*' ] || [] );

				if ( tax_rates.length ) {
					var $rate_table = $( 'table.tax-rates tbody' ).empty();

					$.each( tax_rates, function( index, rate ) {
						$( '<tr>', {
							html: [
								$( '<td>', { 'class': 'readonly', text: rate.country || ''  } ),
								$( '<td>', { 'class': 'readonly', text: rate.state   || '*' } ),
								$( '<td>', { 'class': 'readonly', text: rate.rate    || ''  } ),
								$( '<td>', { 'class': 'readonly', text: rate.name    || ''  } )
							]
						} ).appendTo( $rate_table );
					} );

					$( '.tax-rates' ).show();
				} else {
					$( '.tax-rates' ).hide();
				}
			}
		} else {
			$(':input[name="currency_pos"]').closest('tr').show();
			$(':input[name="thousand_sep"]').closest('tr').show();
			$(':input[name="decimal_sep"]').closest('tr').show();
			$(':input[name="num_decimals"]').closest('tr').show();
			$( '.tax-rates' ).hide();
		}
	} ).change();

	$( 'input[name="woocommerce_calc_taxes"]' ).change( function() {
		if ( $(this).is( ':checked' ) ) {
			$(':input[name="woocommerce_prices_include_tax"], :input[name="woocommerce_import_tax_rates"]').closest('tr').show();
			$('tr.tax-rates').show();
		} else {
			$(':input[name="woocommerce_prices_include_tax"], :input[name="woocommerce_import_tax_rates"]').closest('tr').hide();
			$('tr.tax-rates').hide();
		}
	} ).change();

	$( '.button-next' ).on( 'click', function() {
		$('.wc-setup-content').block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});
		return true;
	} );

	$( '.wc-wizard-payment-gateways, .wc-wizard-shipping-methods' ).on( 'change', '.wc-wizard-gateway-enable input, .wc-wizard-shipping-enable input', function() {
		if ( $( this ).is( ':checked' ) ) {
			$( this ).closest( 'li' ).addClass( 'checked' );
		} else {
			$( this ).closest( 'li' ).removeClass( 'checked' );
		}
	} );

	$( '.wc-wizard-payment-gateways, .wc-wizard-shipping-methods' ).on( 'click', 'li.wc-wizard-gateway, li.wc-wizard-shipping', function() {
		var $enabled = $( this ).find( '.wc-wizard-gateway-enable input, .wc-wizard-shipping-enable input' );

		$enabled.prop( 'checked', ! $enabled.prop( 'checked' ) ).change();
	} );

	$( '.wc-wizard-payment-gateways li, .wc-wizard-shipping-methods li' ).on( 'click', 'table, a', function( e ) {
		e.stopPropagation();
	} );
} );
