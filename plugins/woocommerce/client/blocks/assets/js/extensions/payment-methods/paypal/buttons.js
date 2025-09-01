/**
 * External dependencies
 */
import { PayPalScriptProvider, PayPalButtons } from '@paypal/react-paypal-js';

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

	return (
		<PayPalScriptProvider options={ options }>
			<PayPalButtons />
		</PayPalScriptProvider>
	);
};

export default PayPalButtonsContainer;
