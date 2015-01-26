jQuery(function( $ ){
	$('#woocommerce_paypal_payment_notification_handler').change(function(){
		if ( $(this).val() === 'IPN' ) {
			$('#woocommerce_paypal_receiver_email').closest('tr').show();
			$('#woocommerce_paypal_identity_token').closest('tr').hide();
		} else {
			$('#woocommerce_paypal_receiver_email').closest('tr').hide();
			$('#woocommerce_paypal_identity_token').closest('tr').show();
		}
	}).change();
});