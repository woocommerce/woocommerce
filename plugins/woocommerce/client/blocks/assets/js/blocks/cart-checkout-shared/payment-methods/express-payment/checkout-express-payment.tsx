/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEditorContext, noticeContexts } from '@woocommerce/base-context';
import { Title, StoreNoticesContainer } from '@woocommerce/blocks-components';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/settings';
import { checkoutStore, paymentStore } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';
import { Skeleton } from '@woocommerce/base-components/skeleton';
import clsx from 'clsx';

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
	} = useSelect( ( select ) => {
		const store = select( checkoutStore );
		return {
			isCalculating: store.isCalculating(),
			isProcessing: store.isProcessing(),
			isAfterProcessing: store.isAfterProcessing(),
			isBeforeProcessing: store.isBeforeProcessing(),
			isComplete: store.isComplete(),
			hasError: store.hasError(),
		};
	}, [] );
	const {
		availableExpressPaymentMethods,
		expressPaymentMethodsInitialized,
		isExpressPaymentMethodActive,
		registeredExpressPaymentMethods,
	} = useSelect( ( select ) => {
		const store = select( paymentStore );
		return {
			availableExpressPaymentMethods:
				store.getAvailableExpressPaymentMethods(),
			expressPaymentMethodsInitialized:
				store.expressPaymentMethodsInitialized(),
			isExpressPaymentMethodActive: store.isExpressPaymentMethodActive(),
			registeredExpressPaymentMethods:
				store.getRegisteredExpressPaymentMethods(),
		};
	}, [] );
	const { isEditor } = useEditorContext();

	const hasRegisteredExpressPaymentMethods =
		Object.keys( registeredExpressPaymentMethods ).length > 0;

	// The store has registered express payment methods but they are not initialized.
	// We don't know if the methods pass the canMakePayment check.
	const hasRegisteredNotInitializedExpressPayments =
		! expressPaymentMethodsInitialized &&
		hasRegisteredExpressPaymentMethods;

	// The store has available express payment methods but they are not initialized.
	const hasNoValidRegisteredExpressPayments =
		expressPaymentMethodsInitialized &&
		Object.keys( availableExpressPaymentMethods ).length === 0;

	if (
		! hasRegisteredExpressPaymentMethods ||
		hasNoValidRegisteredExpressPayments
	) {
		// Make sure errors are shown in the editor and for admins. For example,
		// when a payment method fails to register.
		if ( isEditor || CURRENT_USER_IS_ADMIN ) {
			return (
				<StoreNoticesContainer
					context={ noticeContexts.EXPRESS_PAYMENTS }
				/>
			);
		}
		return null;
	}

	// Set disabled state for express payment methods when
	// checkout is processing or an express payment method is active
	const isAreaDisabled =
		isProcessing ||
		isAfterProcessing ||
		isBeforeProcessing ||
		( isComplete && ! hasError ) ||
		isExpressPaymentMethodActive;

	// We show the skeleton when
	// the express payment method is not active (because they trigger recalculations) and
	// the checkout is calculating, because it can result in different express payment methods
	// or when the express payment methods are not initialized
	const showSkeleton =
		! isExpressPaymentMethodActive &&
		( isCalculating || hasRegisteredNotInitializedExpressPayments );

	return (
		<>
			<div
				className={ clsx(
					'wc-block-components-express-payment',
					'wc-block-components-express-payment--checkout',
					{
						'wc-block-components-express-payment--disabled':
							isAreaDisabled,
					}
				) }
				aria-disabled={ isAreaDisabled }
				aria-live="polite"
				{ ...( isAreaDisabled && {
					'aria-busy': true,
					'aria-label': __(
						'Processing express checkout',
						'woocommerce'
					),
				} ) }
			>
				<div className="wc-block-components-express-payment__title-container">
					<Title
						className="wc-block-components-express-payment__title"
						headingLevel="2"
					>
						{ hasRegisteredNotInitializedExpressPayments ? (
							<Skeleton width="127px" height="18px" />
						) : (
							__( ' Express Checkout', 'woocommerce' )
						) }
					</Title>
				</div>
				<div className="wc-block-components-express-payment__content">
					<StoreNoticesContainer
						context={ noticeContexts.EXPRESS_PAYMENTS }
					/>
					{ showSkeleton ? (
						<ul className="wc-block-components-express-payment__event-buttons">
							<li>
								<Skeleton height="48px" />
							</li>
							<li>
								<Skeleton height="48px" />
							</li>
						</ul>
					) : (
						<ExpressPaymentMethods />
					) }
				</div>
			</div>
			<div className="wc-block-components-express-payment-continue-rule wc-block-components-express-payment-continue-rule--checkout">
				{ __( 'Or continue below', 'woocommerce' ) }
			</div>
		</>
	);
};

export default CheckoutExpressPayment;
