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
	useEditorContext,
} from '@woocommerce/base-context';
import Title from '@woocommerce/base-components/title';
import LoadingMask from '@woocommerce/base-components/loading-mask';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import ExpressPaymentMethods from '../express-payment-methods';
import './style.scss';

const CheckoutExpressPayment = () => {
	const {
		isCalculating,
		isProcessing,
		isAfterProcessing,
		isBeforeProcessing,
		isComplete,
		hasError,
	} = useCheckoutContext();
	const { currentStatus: paymentStatus } = usePaymentMethodDataContext();
	const { paymentMethods, isInitialized } = useExpressPaymentMethods();
	const { isEditor } = useEditorContext();
	const { noticeContexts } = useEmitResponse();

	if (
		! isInitialized ||
		( isInitialized && Object.keys( paymentMethods ).length === 0 )
	) {
		// Make sure errors are shown in the editor and for admins. For example,
		// when a payment method fails to register.
		if ( isEditor || CURRENT_USER_IS_ADMIN ) {
			return (
				<StoreNoticesProvider
					context={ noticeContexts.EXPRESS_PAYMENTS }
				></StoreNoticesProvider>
			);
		}
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
				<div className="wc-block-components-express-payment wc-block-components-express-payment--checkout">
					<div className="wc-block-components-express-payment__title-container">
						<Title
							className="wc-block-components-express-payment__title"
							headingLevel="2"
						>
							{ __(
								'Express checkout',
								'woocommerce'
							) }
						</Title>
					</div>
					<div className="wc-block-components-express-payment__content">
						<StoreNoticesProvider
							context={ noticeContexts.EXPRESS_PAYMENTS }
						>
							<p>
								{ __(
									'In a hurry? Use one of our express checkout options:',
									'woocommerce'
								) }
							</p>
							<ExpressPaymentMethods />
						</StoreNoticesProvider>
					</div>
				</div>
			</LoadingMask>
			<div className="wc-block-components-express-payment-continue-rule wc-block-components-express-payment-continue-rule--checkout">
				{ __( 'Or continue below', 'woocommerce' ) }
			</div>
		</>
	);
};

export default CheckoutExpressPayment;
