jQuery(function ($) {
	const containerSelector = 'paypal-standard-container';

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
					return data.paypal_order_id;
				} catch (error) {
					console.error('Error creating order:', error);
					return null;
				}
			},

			async onApprove( data ) {
				console.log( 'PayPal onApprove:', data );
				const formData = new FormData();
				
				formData.append( 'security', paypal_standard.capture_order_nonce );
				try {
					const url = paypal_standard.wc_ajax_url
					.toString()
					.replace( '%%endpoint%%', 'capture_order' );
					
					const response = await fetch( url, {
						method: 'POST',
						body: formData,
					});
					const responseData = await response.json();
					console.log( 'PayPal capture order:', responseData );
				} catch (error) {
					console.error('Error capturing order:', error);
					return null;
				}
			},

			onError: function ( err ) {
				// TODO: Add onError logic here
				console.error( 'PayPal error:', err );
			},

			onCancel( data ) {
				console.log( 'PayPal onCancel:', data );
			},

			onInit( data, actions ) {
				// TODO: Add onInit logic here
			},

			onClick() {
				console.log( 'PayPal onClick:' );
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