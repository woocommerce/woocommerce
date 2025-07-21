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

const CartExpressPayment = () => {
	const { isCalculating } = useSelect( ( select ) => {
		const store = select( checkoutStore );
		return {
			isCalculating: store.isCalculating(),
		};
	} );
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
		return null;
	}

	const availableMethodsCount =
		availableExpressPaymentMethods &&
		Object.keys( availableExpressPaymentMethods ).length > 0
			? Object.keys( availableExpressPaymentMethods ).length
			: 2;

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
				aria-label={ __(
					'Processing express checkout',
					'woocommerce'
				) }
			>
				<div className="wc-block-components-express-payment__content">
					<StoreNoticesContainer
						context={ noticeContexts.EXPRESS_PAYMENTS }
					/>
					{ isCalculating ||
					hasRegisteredNotInitializedExpressPayments ? (
						<ul className="wc-block-components-express-payment__event-buttons">
							{ Array.from( {
								length: availableMethodsCount,
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
