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
})(jQuery, htmlSettingsTaxLocalizeScript, wp);
