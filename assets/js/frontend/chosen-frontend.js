jQuery(document).ready(function($) {

	// Frontend Chosen selects
	$("select.country_select, select.state_select").chosen();

	$('body').bind('country_to_state_changed', function(){
		$("select.state_select").chosen().trigger("liszt:updated");
	});

});