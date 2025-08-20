/* global ajaxurl */

(function ( $, window ) {
	paypal.Buttons({
		async createOrder() {
			// Get cart data.
			const cart = await fetch( window.PayPalStandardButtons.endpoints.storeAPICart );
			const cartData = await cart.json();
			console.log( cartData );

			// Create WooCommerce order via Store API.
			const wcOrder = await fetch( window.PayPalStandardButtons.endpoints.storeAPICheckout, {
				method: 'GET', // Use GET for Draft orders.
				headers: {
					'Nonce': window.PayPalStandardButtons.nonce,
				},
			} );
			const wcOrderData = await wcOrder.json();
			console.log( wcOrderData );

			// Create PayPal order via PayPal API.
			const response = await fetch(window.PayPalStandardButtons.endpoints.createPaypalOrder, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					order_id: wcOrderData.order_id,
				}),
			});
			const data = await response.json();
			return data.id;
		}
	}).render('#woocommerce-paypal-standard-buttons-container');
})( jQuery, window );