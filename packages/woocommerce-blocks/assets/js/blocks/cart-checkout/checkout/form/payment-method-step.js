/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { FormStep } from '@woocommerce/base-components/cart-checkout';
import {
	useCheckoutContext,
	StoreNoticesProvider,
} from '@woocommerce/base-context';
import { usePaymentMethods, useStoreCart } from '@woocommerce/base-hooks';
import { PaymentMethods } from '@woocommerce/base-components/payment-methods';

const PaymentMethodStep = () => {
	const { isProcessing: checkoutIsProcessing } = useCheckoutContext();
	const { cartNeedsPayment } = useStoreCart();
	const { paymentMethods } = usePaymentMethods();

	if ( ! cartNeedsPayment ) {
		return null;
	}

	return (
		<FormStep
			id="payment-method"
			disabled={ checkoutIsProcessing }
			className="wc-block-checkout__payment-method"
			title={ __( 'Payment method', 'woocommerce' ) }
			description={
				Object.keys( paymentMethods ).length > 1
					? __(
							'Select a payment method below.',
							'woocommerce'
					  )
					: ''
			}
		>
			<StoreNoticesProvider context="wc/payment-area">
				<PaymentMethods />
			</StoreNoticesProvider>
		</FormStep>
	);
};

export default PaymentMethodStep;
