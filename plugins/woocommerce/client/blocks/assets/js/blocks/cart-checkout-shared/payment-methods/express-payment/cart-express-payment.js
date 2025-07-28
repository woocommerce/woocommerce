/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
<<<<<<< HEAD
import { noticeContexts, useStoreCart } from '@woocommerce/base-context';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { useSelect } from '@wordpress/data';
import { paymentStore } from '@woocommerce/block-data';
=======
import { noticeContexts } from '@woocommerce/base-context';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { useSelect } from '@wordpress/data';
import { checkoutStore, paymentStore } from '@woocommerce/block-data';
>>>>>>> dfab1f648e ([Cart block] Enable progressive rendering (#59667))
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
<<<<<<< HEAD
=======
		isCalculating,
>>>>>>> dfab1f648e ([Cart block] Enable progressive rendering (#59667))
		availableExpressPaymentMethods = {},
		expressPaymentMethodsInitialized,
		isExpressPaymentMethodActive,
		registeredExpressPaymentMethods = {},
	} = useSelect( ( select ) => {
<<<<<<< HEAD
		const payment = select( paymentStore );
		return {
=======
		const checkout = select( checkoutStore );
		const payment = select( paymentStore );
		return {
			isCalculating: checkout.isCalculating(),
>>>>>>> dfab1f648e ([Cart block] Enable progressive rendering (#59667))
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
<<<<<<< HEAD
	const { hasPendingItemsOperations } = useStoreCart();
=======
>>>>>>> dfab1f648e ([Cart block] Enable progressive rendering (#59667))

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
<<<<<<< HEAD

	// We show the skeleton when
	// the express payment method is not active (because they trigger recalculations) and
	// cart items are being added, updated, or deleted, because it can result in different express payment methods
	// or when the express payment methods are not initialized
	const showSkeleton =
		! isExpressPaymentMethodActive &&
		( hasPendingItemsOperations ||
			hasRegisteredNotInitializedExpressPaymentMethods );
=======
>>>>>>> dfab1f648e ([Cart block] Enable progressive rendering (#59667))

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
							isExpressPaymentMethodActive,
					}
				) }
				aria-disabled={ isExpressPaymentMethodActive }
				aria-live="polite"
<<<<<<< HEAD
				{ ...( isExpressPaymentMethodActive && {
					'aria-busy': true,
					'aria-label': __(
						'Processing express checkout',
						'woocommerce'
					),
				} ) }
=======
				aria-label={ __(
					'Processing express checkout',
					'woocommerce'
				) }
>>>>>>> dfab1f648e ([Cart block] Enable progressive rendering (#59667))
			>
				<div className="wc-block-components-express-payment__content">
					<StoreNoticesContainer
						context={ noticeContexts.EXPRESS_PAYMENTS }
					/>
<<<<<<< HEAD
					{ showSkeleton ? (
=======
					{ isCalculating ||
					hasRegisteredNotInitializedExpressPaymentMethods ? (
>>>>>>> dfab1f648e ([Cart block] Enable progressive rendering (#59667))
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
