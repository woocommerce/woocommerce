jQuery(function ($) {
	const containerSelector = 'paypal-standard-container';
	let orderReceivedUrl = '';
	let orderId = '';

	function renderButtons() {
		const container = document.getElementById( containerSelector );
		if ( ! container ) {
			return;
		}

		const buttons = paypal.Buttons( {
			async createOrder() {
				let responseData;
				try {
					// Create a draft order in WooCommerce.
					const response = await fetch( paypal_standard.rest_url + 'wc/store/v1/checkout', {
						headers: {
							'Content-Type': 'application/json',
							'Nonce': paypal_standard.wc_store_api_nonce,
						},
						} );
					responseData = await response.json();
					orderId = responseData.order_id;
				} catch ( error ) {
					console.error( 'Failed to create WooCommerce order', error );
					return null;
				}

				try {
					// Create a PayPal order.
					const paypalResponse = await fetch( paypal_standard.rest_url + 'wc/v3/paypal-buttons/create-order', {
						method: 'POST',
						body: JSON.stringify( {
							order_id: responseData.order_id,
						} ),
						headers: {
							'Content-Type': 'application/json',
							'Nonce': paypal_standard.nonce,
						},
					} );
					paypalResponseData = await paypalResponse.json();

					orderReceivedUrl = paypalResponseData.return_url;

					return paypalResponseData.paypal_order_id;
				} catch ( error ) {
					console.error( 'Failed to create PayPal order', error );
					return null;
				}
			},

			async onApprove( data ) {
				if ( data.paymentID && orderReceivedUrl ) {
					window.location.href = orderReceivedUrl;
				}
			},

			async onCancel() {
				try {
					await fetch(
						paypal_standard.rest_url + 'wc/v3/paypal-buttons/cancel-payment',
						{
							method: 'POST',
							body: JSON.stringify( {
								order_id: orderId,
							} ),
							headers: {
								'Content-Type': 'application/json',
								Nonce: paypal_standard.nonce,
							},
						}
					);
		
					orderReceivedUrl = '';
				} catch ( error ) {
				}
			},
		});


		buttons.render( container ).catch( function ( err ) {
			console.error( 'Failed to render PayPal buttons', err );
		});
	}

	// Re-render when cart is updated and the html is rerendered on the Cart page.
	$( document.body ).on( 'updated_cart_totals', function () {
		// If the container was replaced, re-render PayPal buttons
		const buttonsContainer = document.getElementById( containerSelector );
		if ( buttonsContainer && ! buttonsContainer.querySelector( 'iframe' ) ) {
			renderButtons();
		}
	} );

	renderButtons();
});