/**
 * Used by woocommerce/includes/admin/settings/views/html-settings-tax.php
 */

(function($, data, wp){
	var rowTemplate = wp.template( 'tax-table-row' ),
		$ratesTbody = $('#rates');

	$(function() {
		$ratesTbody.innerHTML = '';
		$.each( data.rates, function ( id, rowData ) {
			$ratesTbody.append( rowTemplate( rowData ) );
		} );
	});
})(jQuery, htmlSettingsTaxLocalizeScript, wp);
