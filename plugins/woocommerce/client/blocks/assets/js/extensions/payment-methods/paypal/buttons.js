/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { PayPalScriptProvider, PayPalButtons } from '@paypal/react-paypal-js';
import { getPaymentMethodData } from '@woocommerce/settings';

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

	const createOrder = async () => {
		let responseData;
		try {
			// Create a draft order in WooCommerce.
			const response = await fetch(
				payPalData.rest_url + 'wc/store/v1/checkout',
				{
					headers: {
						'Content-Type': 'application/json',
						Nonce: payPalData.wc_store_api_nonce,
					},
				}
			);
			responseData = await response.json();
			setOrderId( responseData.order_id );
		} catch ( error ) {
			console.error( 'Failed to create WooCommerce order', error );
			return null;
		}

		try {
			// Create a PayPal order.
			const paypalResponse = await fetch(
				payPalData.rest_url + 'wc/v3/paypal-buttons/create-order',
				{
					method: 'POST',
					body: JSON.stringify( {
						order_id: responseData.order_id,
					} ),
					headers: {
						'Content-Type': 'application/json',
						Nonce: payPalData.nonce,
					},
				}
			);
			const paypalResponseData = await paypalResponse.json();

			setOrderReceivedURL( paypalResponseData.return_url );

			return paypalResponseData.paypal_order_id;
		} catch ( error ) {
			console.error( 'Failed to create PayPal order', error );
			return null;
		}
	};

	const onApprove = async ( data ) => {
		if ( data.paymentID && orderReceivedUrl ) {
			window.location.href = orderReceivedUrl;
		}
	};

	const onCancel = async () => {
		try {
			await fetch(
				payPalData.rest_url + 'wc/v3/paypal-buttons/cancel-payment',
				{
					method: 'POST',
					body: JSON.stringify( {
						order_id: orderId,
					} ),
					headers: {
						'Content-Type': 'application/json',
						Nonce: payPalData.nonce,
					},
				}
			);

			setOrderReceivedURL( '' );
		} catch ( error ) {
			console.error( 'Failed to create PayPal order', error );
		}
	};

	return (
		<PayPalScriptProvider options={ options }>
			<PayPalButtons
				createOrder={ createOrder }
				onApprove={ onApprove }
				onCancel={ onCancel }
			/>
		</PayPalScriptProvider>
	);
};

export default PayPalButtonsContainer;
