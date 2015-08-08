/* global htmlSettingsTaxLocalizeScript */
/**
 * Used by woocommerce/includes/admin/settings/views/html-settings-tax.php
 */

(function($, data, wp){
	$(function() {

		var rowTemplate        = wp.template( 'wc-tax-table-row' ),
			paginationTemplate = wp.template( 'wc-tax-table-pagination' ),
			$table             = $( '.wc_tax_rates' );
			$tbody             = $( '#rates' ),
			$pagination        = $( '#rates-pagination' );

		/**
		 * Build the table contents.
		 * @param rates
		 */
		function renderTableContents( rates ) {
			// Blank out the contents.
			$tbody.empty();

			// Populate $tbody with the current page of results.
			$.each( rates, function ( id, rowData ) {
				$tbody.append( rowTemplate( rowData ) );
			});

			// Initialize autocomplete for countries.
			$tbody.find( 'td.country input' ).autocomplete({
				source: data.countries,
				minLength: 3
			});

			// Initialize autocomplete for states.
			$tbody.find( 'td.state input' ).autocomplete({
				source: data.states,
				minLength: 3
			});

			// Postcode and city don't have `name` values by default. They're only created if the contents changes, to save on database queries (I think)
			$tbody.find( 'td.postcode input, td.city input').change(function() {
				$(this).attr( 'name', $(this).data( 'name' ) );
			});
		}

		/**
		 * Renders table contents by page.
		 */
		function renderPage( page_num ) {
			var qty_pages = Math.ceil( data.rates.length / data.limit );

			page_num = parseInt( page_num, 10 );
			if ( page_num < 1 ) {
				page_num = 1;
			} else if ( page_num > qty_pages ) {
				page_num = qty_pages;
			}

			var first_index = data.limit * ( page_num - 1),
				last_index  = data.limit * page_num;

			renderTableContents( data.rates.slice( first_index, last_index ) );

			if ( data.rates.length > data.limit ) {
				// We've now displayed our initial page, time to render the pagination box.
				$pagination.html( paginationTemplate( {
					qty_rates    : data.rates.length,
					current_page : page_num,
					qty_pages    : qty_pages
				} ) );
			}
		}

		/**
		 * Handle the initial display.
		 */
		renderPage( data.page );

		/**
		 * Handle clicks on the pagination links.
		 *
		 * Abstracting it out here instead of re-running it after each render.
		 */
		$pagination.on( 'click', 'a', function(event){
			event.preventDefault();
			renderPage( $( event.currentTarget ).data('goto') );
		} );
		$pagination.on( 'change', 'input', function(event) {
			renderPage( $( event.currentTarget ).val() );
		} );

		$table.find('.remove_tax_rates').click(function() {
			if ( $tbody.find('tr.current').length > 0 ) {
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

		/**
		 * Handle the exporting of tax rates, and build it off the global `data.rates` object.
		 *
		 * @todo: Have the `export` button save the current form and generate this from php, so there's no chance the current page is out of date.
		 */
		$table.find('.export').click(function() {
			var csv_data = 'data:application/csv;charset=utf-8,' + data.strings.csv_data_cols.join(',') + '\n';

			$.each( data.rates, function( id, rowData ) {
				var row = '';

				row += rowData.tax_rate_country  + ',';
				row += rowData.tax_rate_state    + ',';
				row += rowData.tax_rate_postcode ? rowData.tax_rate_postcode.join( '; ' ) : '' + ',';
				row += rowData.tax_rate_city     ? rowData.tax_rate_city.join( '; ' )     : '' + ',';
				row += rowData.tax_rate          + ',';
				row += rowData.tax_rate_name     + ',';
				row += rowData.tax_rate_priority + ',';
				row += rowData.tax_rate_compound + ',';
				row += rowData.tax_rate_shipping + ',';
				row += data.current_class;

				csv_data += row + '\n';
			});

			$(this).attr( 'href', encodeURI( csv_data ) );

			return true;
		});

		/**
		 * Add a new blank row to the table for the user to fill out and save.
		 */
		$table.find('.insert').click(function() {
			var size = $tbody.find('tr').length;
			var code = wp.template( 'wc-tax-table-row' )( {
				tax_rate_id       : 'new-' + size,
				tax_rate_priority : 1,
				tax_rate_shipping : 1,
				newRow            : true
			} );

			if ( $tbody.find('tr.current').length > 0 ) {
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

	});
})(jQuery, htmlSettingsTaxLocalizeScript, wp);
