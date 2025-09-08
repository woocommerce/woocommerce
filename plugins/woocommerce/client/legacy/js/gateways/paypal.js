jQuery(function ($) {
	const containerSelector = 'paypal-standard-container';
	let returnUrl = null;

	function renderButtons() {
		const container = document.getElementById( containerSelector );
		if ( ! container ) {
			return;
		}

		const buttons = paypal.Buttons( {
			async createOrder() {
				const $form    = $('form.woocommerce-checkout');
				const formData = new FormData( $form[0] );
				
				formData.append( 'security', paypal_standard.create_order_nonce );
				
				try {
					const url = paypal_standard.wc_ajax_url
					.toString()
					.replace( '%%endpoint%%', 'create_order' );
					
					const response = await fetch( url, {
						method: 'POST',
						body: formData,
					});
					const data = await response.json();
					returnUrl = data.return_url;
					return data.paypal_order_id;
				} catch (error) {
					console.error('Error creating order:', error);
					return null;
				}
			},

			async onApprove( data ) {
				// Redirect to the order received page.
				if ( data.paymentID && returnUrl ) {
					window.location.href = returnUrl;
				}
			},

			onError: function ( err ) {
				// TODO: Add onError logic here
				console.error( 'PayPal error:', err );
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