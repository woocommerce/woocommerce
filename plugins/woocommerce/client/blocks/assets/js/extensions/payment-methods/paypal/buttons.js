/**
 * External dependencies
 */
import { PayPalScriptProvider, PayPalButtons } from '@paypal/react-paypal-js';
import { useCallback, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';
import { useCheckoutAddress } from '@woocommerce/base-context/hooks';
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
	const settings = getPaymentMethodData( 'paypal', {} );
	const [ returnUrl, setReturnUrl ] = useState( null );
	const { billingAddress, shippingAddress } = useCheckoutAddress();
	const { additionalFields, orderNotes } = useSelect( ( select ) => {
		const store = select( checkoutStore );
		return {
			additionalFields: store.getAdditionalFields(),
			orderNotes: store.getOrderNotes(),
		};
	}, [] );

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

	const createOrder = useCallback( async () => {
		console.log( 'createOrder' );
		try {
			// Prepare checkout data.
			const checkoutData = {
				billing_address: JSON.stringify( billingAddress ),
				shipping_address: JSON.stringify( shippingAddress ),
				additional_fields: JSON.stringify( additionalFields ),
				customer_note: orderNotes,
				payment_method: 'paypal',
				security: settings.create_order_nonce,
			};

			const formData = new FormData();
			Object.entries( checkoutData ).forEach( ( [ key, value ] ) => {
				formData.append( key, value );
			} );

			const url = settings.wc_ajax_url
				.toString()
				.replace( '%%endpoint%%', 'create_order' );

			const response = await fetch( url, {
				method: 'POST',
				body: formData,
			} );

			const data = await response.json();

			if ( data.return_url ) {
				setReturnUrl( data.return_url );
			}

			return data.paypal_order_id;
		} catch ( error ) {
			console.error( 'Error creating PayPal order:', error );
			return null;
		}
	}, [
		billingAddress,
		shippingAddress,
		additionalFields,
		orderNotes,
		settings,
	] );

	const onApprove = useCallback(
		( data ) => {
			console.log( 'data', data );
			if ( data.paymentID && returnUrl ) {
				window.location.href = returnUrl;
			}
		},
		[ returnUrl ]
	);

	return (
		<PayPalScriptProvider options={ options }>
			<PayPalButtons
				createOrder={ createOrder }
				onApprove={ onApprove }
			/>
		</PayPalScriptProvider>
	);
};

export default PayPalButtonsContainer;
