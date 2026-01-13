/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { PayPalScriptProvider, PayPalButtons } from '@paypal/react-paypal-js';
import { getPaymentMethodData } from '@woocommerce/settings';
import { dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * PayPalButtonsContainer component.
 *
 * @param {Object}  props
 * @param {string}  props.clientId
 * @param {string}  [props.components]
 * @param {string}  [props.disableFunding]
 * @param {string}  [props.enableFunding]
 * @param {string}  [props.currency]
 * @param {string}  [props.intent]
 * @param {string}  [props.merchantId]
 * @param {string}  [props.partnerAttributionId]
 * @param {string}  [props.pageType]
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
	const [ productPageCartData, setProductPageCartData ] = useState( {
		id: '',
		quantity: '',
	} );
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

	/**
	 * Manage the cart contents when placing an order from the product page.
	 *
	 * @return {Promise<boolean>} True for success, false for failure.
	 */
	const manageCartForProductPageOrder = async () => {
		// Get product ID from the value of the "add-to-cart" button.
		let productId = document.querySelector( '[name="add-to-cart"]' )?.value;
		const variationId = document.querySelector(
			'[name="variation_id"]'
		)?.value;

		if ( variationId ) {
			productId = variationId;
		}

		if ( ! productId ) {
			return false;
		}

		// Get quantity from the value of the "quantity" input field.
		const quantityField = document.querySelector( '[name="quantity"]' );
		const quantity = quantityField?.value ?? '1';
		if ( quantity === '' ) {
			return false;
		}

		// Clearing the cart and re-adding the item causes the current WooCommerce draft order to be lost.
		// If the user is re-opening the payment modal and has not changed anything, do nothing;
		// we want to resume the existing draft order if the cart has not changed.
		if (
			orderId &&
			productPageCartData.id === productId &&
			productPageCartData.quantity === quantity
		) {
			return true;
		}

		try {
			// Empty the cart before adding the product.
			const emptyCartResponse = await window.wp.apiFetch( {
				method: 'DELETE',
				path: '/wc/store/v1/cart/items',
			} );

			// Expected response is an empty array.
			if ( ! emptyCartResponse || emptyCartResponse.length !== 0 ) {
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
		setProductPageCartData( {
			id: productId,
			quantity,
		} );

		return true;
	};

	const createOrder = async ( data ) => {
		let responseData;
		try {
			// If we're inside the product page, we need to empty the cart,
			// and add the current product to the cart.
			if ( isProductPage ) {
				const cartSuccess = await manageCartForProductPageOrder();
				if ( ! cartSuccess ) {
					return null;
				}
			}

			// Create a draft order in WooCommerce.
			responseData = await apiFetch( {
				method: 'GET',
				path: '/wc/store/v1/checkout',
				headers: {
					Nonce: payPalData.wc_store_api_nonce,
				},
			} );

			if ( ! responseData.order_id || ! responseData.order_key ) {
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
					order_key: responseData.order_key,
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

	const onApprove = () => {
		if ( orderReceivedUrl ) {
			window.location.href = orderReceivedUrl;
		}
	};

	const onCancel = async ( data ) => {
		let currentOrderId = orderId;
		if ( ! currentOrderId ) {
			// When coming back from App Switch, the order ID may not be available in the
			// client-side data. Check the URL for the order ID.
			const orderIdFromUrl = new URLSearchParams(
				window.location.search
			).get( 'order_id' );
			if ( orderIdFromUrl ) {
				setOrderId( orderIdFromUrl );
				currentOrderId = orderIdFromUrl;
			}
		}

		if ( ! currentOrderId ) {
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
					order_id: currentOrderId,
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
		const errorMessage =
			error.message || __( 'An unknown error occurred', 'woocommerce' );
		dispatch( 'core/notices' ).createErrorNotice( errorMessage, {
			context: pageType === 'checkout' ? 'wc/checkout' : 'wc/cart',
		} );
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
