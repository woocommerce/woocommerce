jQuery(function ($) {
	const containerSelector = 'paypal-standard-container';
	let orderReceivedUrl = '';
	let orderId = '';
	let orderKey = '';
	let productPageCartData = {};

	function renderButtons() {
		const container = document.getElementById( containerSelector );
		if ( ! container ) {
			return;
		}

		// If PayPal is not loaded, don't try to render the buttons.
		if ( typeof paypal === 'undefined' ) {
			return;
		}

		applyStyles();

		/**
		 * Manage the cart contents when placing an order from the product page.
		 *
		 * @returns {Promise<boolean>}
		 */
		const manageCartForProductPageOrder = async () => {
			// Get product ID from the value of the "add-to-cart" button.
			const addToCartBtn = document.querySelector('[name="add-to-cart"]');
			let productId = addToCartBtn ? addToCartBtn.value : null;
			const variationIdField = document.querySelector( '[name="variation_id"]' );
			const variationId = variationIdField ? variationIdField.value : null;

			if ( variationId ) {
				productId = variationId;
			}

			if ( ! productId ) {
				return false;
			}

			// Get quantity from the value of the "quantity" input field.
			const quantityField = document.querySelector( '[name="quantity"]' );
			const quantity = quantityField ? quantityField.value : '1';
			if ( quantity === '' ) {
				return false;
			}

			// Clearing the cart and re-adding the item causes the current WooCommerce draft order to be lost.
			// If the user is re-opening the payment modal and has not changed anything, do nothing;
			// we want to resume the existing draft order if the cart has not changed.
			if ( orderId && productPageCartData.id === productId && productPageCartData.quantity === quantity ) {
				return true;
			}

			try {
				// Empty the cart before adding the product.
				const emptyCartResponse = await window.wp.apiFetch( {
					method: 'DELETE',
					path: '/wc/store/v1/cart/items',
				} );

				// Expected response is an empty array.
				if ( ! emptyCartResponse || emptyCartResponse.length != 0 ) {
					throw new Error( 'Failed to empty cart' );
				}

				// Add the product to the cart.
				const addToCartResponse = await window.wp.apiFetch( {
					method: 'POST',
					path: '/wc/store/v1/cart/items',
					data: {
						id: productId,
						quantity,
					},
				} );

				if ( ! addToCartResponse || ! addToCartResponse.key ) {
					throw new Error( 'Failed to add product to cart' );
				}
			} catch ( error ) {
				return false;
			}

			// Remember what we added to the cart, so we don't have to repeat the action
			// when the user re-opens the payment modal.
			productPageCartData = {
				id: productId,
				quantity,
			};

			return true;
		}

		const buttons = paypal.Buttons( {
			appSwitchWhenAvailable: true,
			async createOrder( data ) {
				// If we're inside the product page, we need to manage the cart contents
				// ourselves.
				if ( paypal_standard.is_product_page ) {
					const cartSuccess = await manageCartForProductPageOrder();
					if ( ! cartSuccess ) {
						return null;
					}
				}

				let responseData;
				try {
					// Create a draft order in WooCommerce.
					responseData = await window.wp.apiFetch( {
						method: 'GET',
						path: '/wc/store/v1/checkout',
						headers: {
							Nonce: paypal_standard.wc_store_api_nonce,
						},
					} );

					if ( ! responseData.order_id || ! responseData.order_key ) {
						// eslint-disable-next-line no-console
						console.error( 'Failed to create WooCommerce order', responseData );
						return null;
					}

					// Create a PayPal order.
					const paypalResponseData = await window.wp.apiFetch( {
						method: 'POST',
						path: '/wc/v3/paypal-buttons/create-order',
						headers: {
							Nonce: paypal_standard.create_order_nonce,
						},
						data: {
							order_id: responseData.order_id,
							order_key: responseData.order_key,
							payment_source: data.paymentSource || '',
							app_switch_request_origin: paypal_standard.app_switch_request_origin,
						},
					} );

					orderId = paypalResponseData.order_id;
					orderKey = responseData.order_key;
					orderReceivedUrl = paypalResponseData.return_url;

					return paypalResponseData.paypal_order_id;
				} catch ( error ) {
					// eslint-disable-next-line no-console
					console.error( 'Failed to create order', error );
					return null;
				}
			},

			onApprove() {
				if ( orderReceivedUrl ) {
					window.location.href = orderReceivedUrl;
				}
			},

			async onCancel( data ) {
				if ( ! orderId ) {
					// When coming back from App Switch, the order ID may not be available in the
					// client-side data. Check the URL for the order ID.
					orderId = new URLSearchParams( window.location.search ).get( 'order_id' );
				}

				if ( ! orderId ) {
					return;
				}

				try {
					await window.wp.apiFetch( {
						method: 'POST',
						path: '/wc/v3/paypal-buttons/cancel-payment',
						headers: {
							Nonce: paypal_standard.cancel_payment_nonce,
						},
						data: {
							order_id: orderId,
							paypal_order_id: data.orderID,
						},
					} );

					orderReceivedUrl = '';
				} catch ( error ) {
					// eslint-disable-next-line no-console
					console.error( 'Failed to cancel PayPal payment', error );
				}
			},

			onError: function ( error ) {
				const sanitizedErrorMessage = $( '<div>' ).text( error.message || paypal_standard.generic_error_message ).html();
				const messageWrapper =
					'<ul class="woocommerce-error" role="alert"><li>' +
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

		});

		if ( buttons.hasReturned() ) {
			// App Switch resume flow.
			buttons.resume();
		}

		buttons.render( container ).catch( function ( err ) {
			// eslint-disable-next-line no-console
			console.error( 'Failed to render PayPal buttons', err );
		});

	}

	// Align the PayPal buttons to the center of the container on classic checkout page.
	function applyStyles() {
		const paypalContainer = document.getElementById( containerSelector );
		const containerWidth = paypalContainer.offsetWidth;

		// PayPal buttons have max-width: 750px inside the iframe.
		// Calculate the left margin to center a 750px button container.
		const leftMargin = Math.max( 0, ( containerWidth - 750 ) / 2 );
		paypalContainer.style.marginLeft = leftMargin + 'px';
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