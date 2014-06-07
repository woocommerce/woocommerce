jQuery( function( $ ) {
	// run select2
	function initSelect2() {
		$( 'select.country_select, select.state_select' ).select2( 'destroy' ).select2({
			width: 'element',
			dropdownAutoWidth: true
		});
	}

	// init select2
	initSelect2();

	// reinit select2 on country change
	$( 'body' ).bind( 'country_to_state_changed', function() {
		initSelect2();
	});

});
