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
		async onShippingAddressChange(data, actions) {
			console.log( 'onShippingAddressChange' );
			// Now we have access to wcOrderId from the closure
			console.log( 'WooCommerce Order ID:', wcOrderId );
			console.log( 'PayPal shipping address data:', data );

			// Update the WooCommerce order via Store API.
			const wcOrder = await fetch( window.PayPalStandardButtons.endpoints.storeAPICheckout, {
				method: 'PUT', // Use PUT for updating Draft orders.
				headers: {
					'Nonce': window.PayPalStandardButtons.nonce,
				},
				body: JSON.stringify({
					order_id: wcOrderId,
					shipping_address: {
						city: data.shippingAddress.city,
						country_code: data.shippingAddress.countryCode,
						postal_code: data.shippingAddress.postalCode,
						state: data.shippingAddress.state,
					}
				}),
			} );

			const wcOrderData = await wcOrder.json();
			console.log( wcOrderData );

			// Update the PayPal order via PayPal API.
			const paypalOrder = await fetch(window.PayPalStandardButtons.endpoints.updateShippingAddress, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					wc_order_id: wcOrderId,
					paypal_order_id: data.orderID,
					shipping_address: {
						city: data.shippingAddress.city,
						country_code: data.shippingAddress.countryCode,
						postal_code: data.shippingAddress.postalCode,
						state: data.shippingAddress.state,
					}
				}),
			});
			const paypalOrderData = await paypalOrder.json();
			console.log( 'paypalOrderData', paypalOrderData );
		}
	}).render('#woocommerce-paypal-standard-buttons-container');
})( jQuery, window );