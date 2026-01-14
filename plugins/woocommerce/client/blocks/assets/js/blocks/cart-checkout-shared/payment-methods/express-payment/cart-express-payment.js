/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noticeContexts, useStoreCart } from '@woocommerce/base-context';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { useSelect } from '@wordpress/data';
import { paymentStore } from '@woocommerce/block-data';
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
		availableExpressPaymentMethods = {},
		expressPaymentMethodsInitialized,
		isExpressPaymentMethodActive,
		registeredExpressPaymentMethods = {},
	} = useSelect( ( select ) => {
		const payment = select( paymentStore );
		return {
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
	const { hasPendingItemsOperations } = useStoreCart();

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

	// We show the skeleton when
	// the express payment method is not active (because they trigger recalculations) and
	// cart items are being added, updated, or deleted, because it can result in different express payment methods
	// or when the express payment methods are not initialized
	const showSkeleton =
		! isExpressPaymentMethodActive &&
		( hasPendingItemsOperations ||
			hasRegisteredNotInitializedExpressPaymentMethods );

	if (
		! hasRegisteredExpressPaymentMethods ||
		hasNoValidRegisteredExpressPaymentMethods
	) {
		return null;
	}
	/*
	 * Define the elements used for the Express Payments markup.
	 *
	 * When multiple express payment options are available, this will use an
	 * unordered list to display each option.
	 *
	 * When only one express payment option is available, this will use a
	 * non-semantic DIV for both the wrapper and the individual items. This
	 * is to prevent accessibility issues caused by a list of one (which isn't
	 * a list).
	 */
	const ExpressPayWrapper = availableExpressPaymentsCount > 1 ? 'ul' : 'div';
	const ExpressPayItem = availableExpressPaymentsCount > 1 ? 'li' : 'div';

	return (
		<>
			<div
				className={ clsx(
					'wc-block-components-express-payment',
					'wc-block-components-express-payment--cart',
					{
						'wc-block-components-express-payment--disabled':
							isExpressPaymentMethodActive,
					}
				) }
				aria-disabled={ isExpressPaymentMethodActive }
				aria-live="polite"
				{ ...( isExpressPaymentMethodActive && {
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
						<ExpressPayWrapper className="wc-block-components-express-payment__event-buttons">
							{ Array.from( {
								length: availableExpressPaymentsCount,
							} ).map( ( _, index ) => (
								<ExpressPayItem key={ index }>
									<Skeleton
										height="48px"
										ariaMessage={ __(
											'Loading express payment method…',
											'woocommerce'
										) }
									/>
								</ExpressPayItem>
							) ) }
						</ExpressPayWrapper>
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
