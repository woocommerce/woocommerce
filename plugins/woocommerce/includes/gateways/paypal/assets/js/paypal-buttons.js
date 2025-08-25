/* global ajaxurl */

(function ( $, window ) {
	// Store WooCommerce order ID for use across PayPal button callbacks
	let wcOrderId = null;

	paypal.Buttons({
		async createOrder() {
			console.log( 'createOrder' );
			// Create WooCommerce order via Store API.
			// This needs to be a client-side request.
			const wcOrder = await fetch( window.PayPalStandardButtons.endpoints.storeAPICheckout, {
				method: 'GET', // Use GET for Draft orders.
				headers: {
					'Nonce': window.PayPalStandardButtons.nonce,
				},
			} );
			const wcOrderData = await wcOrder.json();
			console.log( 'wcOrderData', wcOrderData );

			// Store the WooCommerce order ID for later use
			wcOrderId = wcOrderData.order_id;

			// Create PayPal order via PayPal API.
			const paypalOrder = await fetch(window.PayPalStandardButtons.endpoints.createPayPalOrder, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					order_id: wcOrderId,
				}),
			});
			const paypalOrderData = await paypalOrder.json();

			console.log( 'paypalOrderData', paypalOrderData );
			return paypalOrderData.id;
		},
	}).render('#woocommerce-paypal-standard-buttons-container');
})( jQuery, window );