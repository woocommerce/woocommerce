jQuery(document).ready(function($) {

	// Shipping calculator
	$(document).on( 'click', '.shipping-calculator-button', function() {
		$('.shipping-calculator-form').slideToggle('slow');
		return false;
	}).on( 'change', 'select#shipping_method, input[name=shipping_method]', function() {
		var method = $(this).val();

		$('div.cart_totals').block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.ajax_loader_url + ') no-repeat center', backgroundSize: '16px 16px', opacity: 0.6}});

		var data = {
			action: 			'woocommerce_update_shipping_method',
			security: 			woocommerce_params.update_shipping_method_nonce,
			shipping_method: 	method
		};

		$.post( woocommerce_params.ajax_url, data, function(response) {

			$('div.cart_totals').replaceWith( response );
			$('body').trigger('updated_shipping_method');

		});
	})

	$('.shipping-calculator-form').hide();

});