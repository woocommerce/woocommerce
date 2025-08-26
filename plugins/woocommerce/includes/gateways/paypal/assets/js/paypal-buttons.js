(function ( $, window ) {
	if ( ! document.getElementById( 'woocommerce-paypal-standard-buttons-container' ) ) {
		return;
	}

	let wcOrderId = null;
	paypal.Buttons({
		async createOrder() {
			console.log( 'createOrder' );
			if ( window.PayPalStandardButtons.isProductPage ) {
				// Empty cart.
				await fetch( window.PayPalStandardButtons.endpoints.storeAPICartItems, {
					method: 'DELETE',
					headers: {
						'Content-Type': 'application/json',
						'Nonce': window.PayPalStandardButtons.nonce,
					},
				} );

				// Add current product to cart.
				await fetch( window.PayPalStandardButtons.endpoints.storeAPICartAddItem, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'Nonce': window.PayPalStandardButtons.nonce,
					},
					body: JSON.stringify({
						id: $( '[name="add-to-cart"]').val(),
						quantity: $( '[name="quantity"]').val(),
					}),
				} );
			}

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
		onShippingAddressChange(data) {
			// TODO: Invoked only when there is no server-side shipping callback.
			console.log( 'onShippingAddressChange', data, wcOrderId );
		},
		onShippingOptionsChange(data) {
			// TODO: Invoked only when there is no server-side shipping callback.
			console.log( 'onShippingOptionChange', data, wcOrderId );
		},
		async onApprove(data) {
			console.log( 'onApprove', data, wcOrderId );
			const response = await fetch(window.PayPalStandardButtons.endpoints.capturePayment, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					order_id: wcOrderId,
					paypal_order_id: data.orderID,
					action: 'capture', // TODO: Add support for authorize.
				}),
			});

			const responseData = await response.json();
			console.log( 'approve response', responseData );
			window.location.href = responseData.return_url;
		},
	}).render('#woocommerce-paypal-standard-buttons-container');
})( jQuery, window );