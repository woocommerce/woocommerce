jQuery(function ($) {
	const containerSelector = 'paypal-standard-container';
	let orderReceivedUrl = '';

	function renderButtons() {
		const container = document.getElementById( containerSelector );
		if ( ! container ) {
			return;
		}

		const buttons = paypal.Buttons( {
			async createOrder() {
				// Create a draft order in WooCommerce.
				const response = await fetch( paypal_standard.rest_url + 'wc/store/v1/checkout', {
					headers: {
						'Content-Type': 'application/json',
						'Nonce': paypal_standard.wc_store_api_nonce,
					},
					} );
				responseData = await response.json();

				// Create a PayPal order.
				const paypalResponse = await fetch( paypal_standard.rest_url + 'wc/v3/paypal-buttons/create-order', {
					method: 'POST',
					body: JSON.stringify( {
						order_id: responseData.order_id,
					} ),
					// credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						'Nonce': paypal_standard.nonce,
					},
				} );
				paypalResponseData = await paypalResponse.json();

				orderReceivedUrl = paypalResponseData.return_url;

				return paypalResponseData.paypal_order_id;
			},

			async onApprove( data ) {
				if ( data.paymentID && orderReceivedUrl ) {
					window.location.href = orderReceivedUrl;
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