jQuery(function ($) {
	const containerSelector = 'paypal-standard-container';

	function renderButtons() {
		const container = document.getElementById( containerSelector );
		if ( ! container ) {
			return;
		}

		const buttons = paypal.Buttons( {
			style: {
				layout: 'vertical',
				color: 'gold',
				shape: 'rect',
				label: 'paypal'
			},

			async createOrder() {
				// form data
				const formData = new FormData();
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
				}
			},

			async onApprove( data ) {
				// TODO: Add onApprove logic here
			},

			onError: function ( err ) {
				// TODO: Add onError logic here
				console.error( 'PayPal error:', err );
			},

			onCancel( data ) {
				// TODO: Add onCancel logic here
			},

			onInit( data, actions ) {
				// TODO: Add onInit logic here
			},

			onClick() {
				// TODO: Add onClick logic here
			},

		});


		buttons.render( container ).catch( function ( err ) {
			console.error( 'Failed to render PayPal buttons', err );
		});
	}

	// Re-render when WooCommerce updates the checkout.
	$( document.body ).on( 'updated_checkout payment_method_selected', function () {
		// If the container was replaced, re-render PayPal buttons
		const buttonsContainer = document.getElementById( containerSelector );
		if ( buttonsContainer && ! buttonsContainer.querySelector( 'iframe' ) ) {
			renderButtons();
		}
	} );

	renderButtons();
});