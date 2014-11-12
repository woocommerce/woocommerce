jQuery( function( $ ) {
	$( '.lost_reset_password' ).on( 'submit', function () {
		$( 'input[type="submit"]', this ).attr( 'disabled', 'disabled' );
	});
});
