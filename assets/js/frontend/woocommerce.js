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
	
	// Class added to thank you message.
	if (obj_order_received_page.thankyou_message == 1) {
		$(".woocommerce p").each(function() {
		    var message = $(this).text();
			if (message == 'Thank you. Your order has been received.') {
			 $(this).addClass('woocommerce-message woocommerce-order-thank-you');
			}
		});
	}
	
});
