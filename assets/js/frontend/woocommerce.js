jQuery( function( $ ) {
	// Orderby
	$( '.woocommerce-ordering' ).on( 'change', 'select.orderby', function() {
		$( this ).closest( 'form' ).submit();
	});

	// Target quantity inputs on product pages
	$( 'input.qty:not(.product-quantity input.qty)' ).each( function() {
		var min = parseFloat( $( this ).attr( 'min' ) );

		if ( min >= 0 && parseFloat( $( this ).val() ) < min ) {
			$( this ).val( min );
		}
	});
	
	// Password strength message container.
	$( '#password_1' ).after( '<div id="pass-strength-result" aria-live="polite"></div>' );
	
	// Function for check password strength for Edit My Account
	function checkPasswordStrength( s, a, r ) {
		var t = jQuery( '#password_1' ).val();
		a.removeClass( 'short bad good strong' ),
		r = r.concat( wp.passwordStrength.userInputBlacklist() );
		var e = wp.passwordStrength.meter( t, r );
		
		switch ( e ) {
			case 2:
				a.addClass( 'bad' ).html( pwsL10n.bad );
				break;
			case 3:
				a.addClass( 'good' ).html( pwsL10n.good );
				break;
			case 4:
				a.addClass( 'strong' ).html( pwsL10n.strong );
				break;
			case 5:
				a.addClass( 'short' ).html( pwsL10n.mismatch );
				break;
			default:
				a.addClass( 'short' ).html( pwsL10n.short );
		}
		return e
	}

	$( '#password_1' ).keyup(function() {
			checkPasswordStrength(
               	$( 'input[name=password_1]' ),         // First password field
               	$( '#pass-strength-result' ),         // Strength meter
                [ 'black', 'listed', 'word' ]        // Blacklisted words
            );
           
           var passLength = jQuery( '#password_1' ).val().length;
				
           if ( passLength<= 0 ) {
    			$( '#pass-strength-result' ).css( 'display','none' );
    		}else {
    			$( '#pass-strength-result' ).css( 'display','block' );
  		  }
        }
    );
});
