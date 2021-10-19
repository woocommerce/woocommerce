/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useEmitResponse,
	useExpressPaymentMethods,
} from '@woocommerce/base-context/hooks';
import {
	StoreNoticesProvider,
	useCheckoutContext,
	usePaymentMethodDataContext,
} from '@woocommerce/base-context';
import LoadingMask from '@woocommerce/base-components/loading-mask';

/**
 * Internal dependencies
 */
import ExpressPaymentMethods from '../express-payment-methods';
import './style.scss';

const CartExpressPayment = () => {
	const { paymentMethods, isInitialized } = useExpressPaymentMethods();
	const { noticeContexts } = useEmitResponse();
	const {
		isCalculating,
		isProcessing,
		isAfterProcessing,
		isBeforeProcessing,
		isComplete,
		hasError,
	} = useCheckoutContext();
	const { currentStatus: paymentStatus } = usePaymentMethodDataContext();

	if (
		! isInitialized ||
		( isInitialized && Object.keys( paymentMethods ).length === 0 )
	) {
		return null;
	}

	// Set loading state for express payment methods when payment or checkout is in progress.
	const checkoutProcessing =
		isProcessing ||
		isAfterProcessing ||
		isBeforeProcessing ||
		( isComplete && ! hasError );

	return (
		<>
			<LoadingMask
				isLoading={
					isCalculating ||
					checkoutProcessing ||
					paymentStatus.isDoingExpressPayment
				}
			>
				<div className="wc-block-components-express-payment wc-block-components-express-payment--cart">
					<div className="wc-block-components-express-payment__content">
						<StoreNoticesProvider
							context={ noticeContexts.EXPRESS_PAYMENTS }
						>
							<ExpressPaymentMethods />
						</StoreNoticesProvider>
					</div>
				</div>
			</LoadingMask>
			<div className="wc-block-components-express-payment-continue-rule wc-block-components-express-payment-continue-rule--cart">
				{ /* translators: Shown in the Cart block between the express payment methods and the Proceed to Checkout button */ }
				{ __( 'Or', 'woocommerce' ) }
			</div>
		</>
	);
};

export default CartExpressPayment;
