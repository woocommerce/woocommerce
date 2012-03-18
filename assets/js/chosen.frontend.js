jQuery(function(){

	// Frontend Chosen selects
	jQuery("select.country_select, select.state_select").chosen();
	
	jQuery('body').bind('country_to_state_changed', function(){
		jQuery("select.state_select").chosen().trigger("liszt:updated");
	});
	
});