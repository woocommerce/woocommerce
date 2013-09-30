jQuery( function() {
	jQuery('.wc-credit-card-form-card-number').payment('formatCardNumber');
	jQuery('.wc-credit-card-form-card-expiry').payment('formatCardExpiry');
	jQuery('.wc-credit-card-form-card-cvc').payment('formatCardCVC');

	jQuery('body')
		.on('updated_checkout', function() {
			jQuery('.wc-credit-card-form-card-number').payment('formatCardNumber');
			jQuery('.wc-credit-card-form-card-expiry').payment('formatCardExpiry');
			jQuery('.wc-credit-card-form-card-cvc').payment('formatCardCVC');
		});
} );