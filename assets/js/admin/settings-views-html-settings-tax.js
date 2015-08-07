/* global htmlSettingsTaxLocalizeScript */
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

	$('.wc_tax_rates .remove_tax_rates').click(function() {
		if ( $tbody.find('tr.current').size() > 0 ) {
			var $current = $tbody.find('tr.current');
			$current.find('input').val('');
			$current.find('input.remove_tax_rate').val('1');

			$current.each(function(){
				if ( $(this).is('.new') ) {
					$( this ).remove();
				} else {
					$( this ).hide();
				}
			});
		} else {
			window.alert( data.strings.no_rows_selected );
		}
		return false;
	});

	$('.wc_tax_rates .export').click(function() {

		var csv_data = 'data:application/csv;charset=utf-8,' + data.strings.csv_data_cols.join(',') + '\n';

		$('#rates tr:visible').each(function() {
			var row = '';
			$(this).find('td:not(.sort) input').each(function() {
				var val = '';

				if ( $(this).is('.checkbox') ) {
					if ( $(this).is(':checked') ) {
						val = 1;
					} else {
						val = 0;
					}
				} else {
					val = $(this).val();
					if ( ! val ) {
						val = $( this ).attr( 'placeholder' );
					}
				}
				row = row + val + ',';
			});
			row = row + data.current_class;
			//row.substring( 0, row.length - 1 );
			csv_data = csv_data + row + '\n';
		});

		$(this).attr( 'href', encodeURI( csv_data ) );

		return true;
	});

	$('.wc_tax_rates .insert').click(function() {
		var size = $tbody.find('tr').size();
		var code = wp.template( 'wc-tax-table-row' )( {
			tax_rate_id       : 'new-' + size,
			tax_rate_priority : 1,
			tax_rate_shipping : 1,
			newRow            : true
		} );

		if ( $tbody.find('tr.current').size() > 0 ) {
			$tbody.find('tr.current').after( code );
		} else {
			$tbody.append( code );
		}

		$( 'td.country input' ).autocomplete({
			source: data.countries,
			minLength: 3
		});

		$( 'td.state input' ).autocomplete({
			source: data.states,
			minLength: 3
		});

		return false;
	});

	$('.wc_tax_rates td.postcode, .wc_tax_rates td.city').find('input').change(function() {
		$(this).attr( 'name', $(this).attr( 'data-name' ) );
	});

	$( 'td.country input' ).autocomplete({
		source: data.countries,
		minLength: 3
	});

	$( 'td.state input' ).autocomplete({
		source: data.states,
		minLength: 3
	});
})(jQuery, htmlSettingsTaxLocalizeScript, wp);
