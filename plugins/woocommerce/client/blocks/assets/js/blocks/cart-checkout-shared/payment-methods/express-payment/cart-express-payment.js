/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noticeContexts } from '@woocommerce/base-context';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { useSelect } from '@wordpress/data';
import { checkoutStore, paymentStore } from '@woocommerce/block-data';
import { Skeleton } from '@woocommerce/base-components/skeleton';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import ExpressPaymentMethods from '../express-payment-methods';
import './style.scss';
import { getExpressPaymentMethodsState } from './express-payment-methods-helpers';

const CartExpressPayment = () => {
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
	// Set loading state for express payment methods when payment or checkout is in progress.
	const checkoutProcessing =
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

	if (
		! hasRegisteredExpressPaymentMethods ||
		hasNoValidRegisteredExpressPaymentMethods
	) {
		return null;
	}

	return (
		<>
			<div
				className={ clsx(
					'wc-block-components-express-payment',
					'wc-block-components-express-payment--cart',
					{
						'wc-block-components-express-payment--disabled':
							checkoutProcessing,
					}
				) }
				aria-disabled={ checkoutProcessing }
				aria-live="polite"
				{ ...( checkoutProcessing && {
					'aria-busy': true,
					'aria-label': __(
						'Processing express checkout',
						'woocommerce'
					),
				} ) }
			>
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
			<div className="wc-block-components-express-payment-continue-rule wc-block-components-express-payment-continue-rule--cart">
				{ /* translators: Shown in the Cart block between the express payment methods and the Proceed to Checkout button */ }
				{ __( 'Or', 'woocommerce' ) }
			</div>
		</>
	);
};

export default CartExpressPayment;
