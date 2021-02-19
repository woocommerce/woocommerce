/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { FormStep } from '@woocommerce/base-components/cart-checkout';
import {
	useCheckoutContext,
	StoreNoticesProvider,
} from '@woocommerce/base-context';
import {
	useEmitResponse,
	usePaymentMethods,
	useStoreCart,
} from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import { PaymentMethods } from '../../payment-methods';

const PaymentMethodStep = () => {
	const { isProcessing: checkoutIsProcessing } = useCheckoutContext();
	const { cartNeedsPayment } = useStoreCart();
	const { paymentMethods } = usePaymentMethods();
	const { noticeContexts } = useEmitResponse();

	if ( ! cartNeedsPayment ) {
		return null;
	}

	return (
		<FormStep
			id="payment-method"
			disabled={ checkoutIsProcessing }
			className="wc-block-checkout__payment-method"
			title={ __( 'Payment method', 'woo-gutenberg-products-block' ) }
			description={
				Object.keys( paymentMethods ).length > 1
					? __(
							'Select a payment method below.',
							'woo-gutenberg-products-block'
					  )
					: ''
			}
		>
			<StoreNoticesProvider context={ noticeContexts.PAYMENTS }>
				<PaymentMethods />
			</StoreNoticesProvider>
		</FormStep>
	);
};

export default PaymentMethodStep;
