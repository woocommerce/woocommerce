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
import { getExpressPaymentMethodsState } from './express-payment-methods-helpers';

const CheckoutExpressPayment = () => {
	const {
		isCalculating,
		isProcessing,
		isAfterProcessing,
		isBeforeProcessing,
		isComplete,
		hasError,
		availableExpressPaymentMethods = {},
		expressPaymentMethodsInitialized,
		isExpressPaymentMethodActive,
		registeredExpressPaymentMethods = {},
	} = useSelect( ( select ) => {
		const checkout = select( checkoutStore );
		const payment = select( paymentStore );
		return {
			isCalculating: checkout.isCalculating(),
			isProcessing: checkout.isProcessing(),
			isAfterProcessing: checkout.isAfterProcessing(),
			isBeforeProcessing: checkout.isBeforeProcessing(),
			isComplete: checkout.isComplete(),
			hasError: checkout.hasError(),
			availableExpressPaymentMethods:
				payment.getAvailableExpressPaymentMethods(),
			expressPaymentMethodsInitialized:
				payment.expressPaymentMethodsInitialized(),
			isExpressPaymentMethodActive:
				payment.isExpressPaymentMethodActive(),
			registeredExpressPaymentMethods:
				payment.getRegisteredExpressPaymentMethods(),
		};
	}, [] );
	const { isEditor } = useEditorContext();

	const {
		hasRegisteredExpressPaymentMethods,
		hasRegisteredNotInitializedExpressPaymentMethods,
		hasNoValidRegisteredExpressPaymentMethods,
		availableExpressPaymentsCount,
	} = getExpressPaymentMethodsState( {
		availableExpressPaymentMethods,
		expressPaymentMethodsInitialized,
		registeredExpressPaymentMethods,
	} );

	if (
		! hasRegisteredExpressPaymentMethods ||
		hasNoValidRegisteredExpressPaymentMethods
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
		( isCalculating || hasRegisteredNotInitializedExpressPaymentMethods );

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
						{ hasRegisteredNotInitializedExpressPaymentMethods ? (
							<Skeleton
								width="127px"
								height="18px"
								ariaMessage={ __(
									'Loading express payment area…',
									'woocommerce'
								) }
							/>
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
							{ Array.from( {
								length: availableExpressPaymentsCount,
							} ).map( ( _, index ) => (
								<li key={ index }>
									<Skeleton
										height="48px"
										ariaMessage={ __(
											'Loading express payment method…',
											'woocommerce'
										) }
									/>
								</li>
							) ) }
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
