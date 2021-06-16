/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { FormStep } from '@woocommerce/base-components/cart-checkout';
import { StoreNoticesProvider } from '@woocommerce/base-context';
import {
	useStoreCart,
	useEmitResponse,
	usePaymentMethods,
	useCheckoutSubmit,
} from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import { PaymentMethods } from '../../payment-methods';

const PaymentMethodStep = () => {
	const { isDisabled } = useCheckoutSubmit();
	const { cartNeedsPayment } = useStoreCart();
	const { paymentMethods } = usePaymentMethods();
	const { noticeContexts } = useEmitResponse();

	if ( ! cartNeedsPayment ) {
		return null;
	}

	return (
		<FormStep
			id="payment-method"
			disabled={ isDisabled }
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
