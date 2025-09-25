/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { PayPalScriptProvider, PayPalButtons } from '@paypal/react-paypal-js';
import { getPaymentMethodData } from '@woocommerce/settings';
import { dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * PayPalButtonsContainer component.
 *
 * @param {Object} props
 * @param {string} props.clientId
 * @param {string} [props.components]
 * @param {string} [props.disableFunding]
 * @param {string} [props.enableFunding]
 * @param {string} [props.currency]
 * @param {string} [props.intent]
 * @param {string} [props.merchantId]
 * @param {string} [props.partnerAttributionId]
 * @param {string} [props.pageType]
 * @param {boolean} [props.isProductPage]
 * @param {string} [props.appSwitchRequestOrigin]
 * @return {JSX.Element} The PayPal Buttons container component.
 */
const PayPalButtonsContainer = ( {
	clientId,
	components,
	disableFunding,
	enableFunding,
	currency,
	intent,
	merchantId,
	partnerAttributionId,
	pageType,
	isProductPage,
	appSwitchRequestOrigin,
} ) => {
	const [ orderReceivedUrl, setOrderReceivedURL ] = useState( '' );
	const [ orderId, setOrderId ] = useState( '' );
	const payPalData = getPaymentMethodData( 'paypal', {} );
	const options = {
		clientId: clientId || '',
		components: components || '',
		disableFunding: disableFunding || '',
		enableFunding: enableFunding || '',
		currency: currency || '',
		intent: intent || '',
		merchantId: merchantId || '',
		'data-partner-attribution-id': partnerAttributionId || '',
		'data-page-type': pageType || '',
	};

	const createOrder = async ( data ) => {
		let responseData;
		try {
			// If we're inside the product page, we need to empty the cart,
			// and add the current product to the cart.
			if ( isProductPage ) {
				// Empty the cart.
				await apiFetch( {
					method: 'DELETE',
					path: '/wc/store/v1/cart/items',
				} );

				// Get product ID from the value of the "add-to-cart" button.
				let productId = document.querySelector(
					'[name="add-to-cart"]'
				)?.value;
				const variationId = document.querySelector(
					'[name="variation_id"]'
				)?.value;

				if ( variationId ) {
					productId = variationId;
				}

				if ( ! productId ) {
					return null;
				}

				// Get quantity from the value of the "quantity" input field.
				const quantity =
					document.querySelector( '[name="quantity"]' )?.value;
				if ( ! quantity ) {
					return null;
				}

				// Add the product to the cart.
				await apiFetch( {
					method: 'POST',
					path: '/wc/store/v1/cart/items',
					data: {
						id: productId,
						quantity,
					},
				} );
			}

			// Create a draft order in WooCommerce.
			responseData = await apiFetch( {
				method: 'GET',
				path: '/wc/store/v1/checkout',
				headers: {
					Nonce: payPalData.wc_store_api_nonce,
				},
			} );

			if ( ! responseData.order_id ) {
				// eslint-disable-next-line no-console
				console.error(
					'Failed to create WooCommerce order',
					responseData
				);
				return null;
			}

			// Create a PayPal order.
			const paypalResponseData = await apiFetch( {
				method: 'POST',
				path: '/wc/v3/paypal-buttons/create-order',
				headers: {
					Nonce: payPalData.create_order_nonce,
				},
				data: {
					order_id: responseData.order_id,
					payment_source: data.paymentSource || '',
					app_switch_request_origin: appSwitchRequestOrigin,
				},
			} );

			setOrderId( paypalResponseData.order_id );
			setOrderReceivedURL( paypalResponseData.return_url );

			return paypalResponseData.paypal_order_id;
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Failed to create order', error );
			return null;
		}
	};

	const onApprove = ( data ) => {
		if ( data.paymentID && orderReceivedUrl ) {
			window.location.href = orderReceivedUrl;
		}
	};

	const onCancel = async ( data ) => {
		if ( ! orderId ) {
			// When coming from App Switch, the order ID may not be available in the client-side state.
			// Check the URL for the order ID.
			const orderIdFromUrl = new URLSearchParams(
				window.location.search
			).get( 'order_id' );
			if ( orderIdFromUrl ) {
				setOrderId( orderIdFromUrl );
			}
		}

		if ( ! orderId ) {
			return;
		}

		try {
			await apiFetch( {
				method: 'POST',
				path: '/wc/v3/paypal-buttons/cancel-payment',
				headers: {
					Nonce: payPalData.cancel_payment_nonce,
				},
				data: {
					order_id: orderId,
					paypal_order_id: data.orderID,
				},
			} );

			setOrderReceivedURL( '' );
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Failed to cancel PayPal payment', error );
		}
	};

	const onError = ( error ) => {
		dispatch( 'core/notices' ).createErrorNotice(
			'PayPal error: ' + error.message,
			{
				context: pageType === 'checkout' ? 'wc/checkout' : 'wc/cart',
			}
		);
	};

	return (
		<PayPalScriptProvider options={ options }>
			<PayPalButtons
				appSwitchWhenAvailable={ true }
				createOrder={ createOrder }
				onApprove={ onApprove }
				onCancel={ onCancel }
				onError={ onError }
			/>
		</PayPalScriptProvider>
	);
};

export default PayPalButtonsContainer;
