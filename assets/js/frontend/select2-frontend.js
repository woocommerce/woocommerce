jQuery( function( $ ) {
	// run select2
	function initSelect2() {
		$( 'select.country_select, #calc_shipping_country, #calc_shipping_state' ).select2( 'destroy' ).select2({
			width: 'resolve',
			dropdownAutoWidth: true
		});
	}

	// init select2
	initSelect2();
	
	// run state/province select2
	function initStateSelect2() {
		$( 'select.state_select, #calc_shipping_state' ).select2( 'destroy' ).select2({
			width: 'element',
			dropdownAutoWidth: true,
			placeholderOption: 'first'
		});
	}

	// reinit select2 on country change
	$( 'body' ).on( 'country_to_state_changed', function() {
		initStateSelect2();
	});

});
