jQuery(function ($) {
	const containerSelector = 'paypal-standard-container';

	function renderButtons() {
		const container = document.getElementById( containerSelector );
		if ( ! container ) {
			return;
		}

		const buttons = paypal.Buttons( {
			async createOrder() {
				// TODO: Add createOrder logic here
				return null;
			},

			async onApprove( data ) {
				// TODO: Add onApprove logic here
			},

			onError: function ( error ) {				
				const sanitizedErrorMessage = $( '<div>' ).text( error.message || 'An unknown error occurred' ).html();
				const messageWrapper =
					'<ul class="woocommerce-error" role="alert"><li>' +
						'PayPal error: ' +
						sanitizedErrorMessage +
					'</li></ul>';	
				
				const $noticeContainer = $( '.woocommerce-notices-wrapper' ).first();

				if ( ! $noticeContainer.length ) {
					return;
				}
		
				$(
					'.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message'
				).remove();
				$noticeContainer.prepend( messageWrapper );
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