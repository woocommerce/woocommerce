/**
 * Used by woocommerce/includes/admin/settings/views/html-settings-tax.php
 */

(function($, data, wp){
	var rowTemplate = wp.template( 'wc-tax-table-row' ),
		$tbody = $('#rates');

	$(function() {
		$tbody.innerHTML = '';
		$.each( data.rates, function ( id, rowData ) {
			$tbody.append( rowTemplate( rowData ) );
		} );
	});

	jQuery('.wc_tax_rates .remove_tax_rates').click(function() {
		var $tbody = jQuery('.wc_tax_rates').find('tbody');
		if ( $tbody.find('tr.current').size() > 0 ) {
			$current = $tbody.find('tr.current');
			$current.find('input').val('');
			$current.find('input.remove_tax_rate').val('1');

			$current.each(function(){
				if ( jQuery(this).is('.new') )
					jQuery(this).remove();
				else
					jQuery(this).hide();
			});
		} else {
			alert('<?php echo esc_js( __( 'No row(s) selected', 'woocommerce' ) ); ?>');
		}
		return false;
	});

	jQuery('.wc_tax_rates .export').click(function() {

		var csv_data = 'data:application/csv;charset=utf-8,' + data.strings.csv_data_cols.join(',') + '\n';

		jQuery('#rates tr:visible').each(function() {
			var row = '';
			jQuery(this).find('td:not(.sort) input').each(function() {

				if ( jQuery(this).is('.checkbox') ) {

					if ( jQuery(this).is(':checked') ) {
						val = 1;
					} else {
						val = 0;
					}

				} else {

					var val = jQuery(this).val();

					if ( ! val )
						val = jQuery(this).attr('placeholder');
				}

				row = row + val + ',';
			});
			row = row + '<?php echo $current_class; ?>';
			//row.substring( 0, row.length - 1 );
			csv_data = csv_data + row + "\n";
		});

		jQuery(this).attr( 'href', encodeURI( csv_data ) );

		return true;
	});

	jQuery('.wc_tax_rates .insert').click(function() {
		var $tbody = jQuery('.wc_tax_rates').find('tbody');
		var size = $tbody.find('tr').size();
		var code = wp.template( 'wc-tax-table-row' )( {
			tax_rate_id       : 'new-' + size,
			tax_rate_priority : 1,
			tax_rate_shipping : 1,
			new               : true
		} );

		if ( $tbody.find('tr.current').size() > 0 ) {
			$tbody.find('tr.current').after( code );
		} else {
			$tbody.append( code );
		}

		jQuery( "td.country input" ).autocomplete({
			source: availableCountries,
			minLength: 3
		});

		jQuery( "td.state input" ).autocomplete({
			source: availableStates,
			minLength: 3
		});

		return false;
	});

	jQuery('.wc_tax_rates td.postcode, .wc_tax_rates td.city').find('input').change(function() {
		jQuery(this).attr( 'name', jQuery(this).attr( 'data-name' ) );
	});

	jQuery( "td.country input" ).autocomplete({
		source: data.countries,
		minLength: 3
	});

	jQuery( "td.state input" ).autocomplete({
		source: data.states,
		minLength: 3
	});
})(jQuery, htmlSettingsTaxLocalizeScript, wp);
