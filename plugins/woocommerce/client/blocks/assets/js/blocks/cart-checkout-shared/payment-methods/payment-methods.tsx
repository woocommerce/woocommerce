/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Label } from '@woocommerce/blocks-components';
import { useSelect } from '@wordpress/data';
import { paymentStore } from '@woocommerce/block-data';
import { CheckoutPaymentSkeleton } from '@woocommerce/base-components/skeleton/patterns/checkout-payment';
import { DelayedContentWithSkeleton } from '@woocommerce/base-components/delayed-content-with-skeleton';

/**
 * Internal dependencies
 */
import NoPaymentMethods from './no-payment-methods';
import OnlyExpressPayments from './only-express-payments';
import PaymentMethodOptions from './payment-method-options';
import SavedPaymentMethodOptions from './saved-payment-method-options';
import './style.scss';

/**
 * PaymentMethods component.
 */
const PaymentMethods = ( {
	noPaymentMethods = <NoPaymentMethods />,
	onlyExpressPayments = <OnlyExpressPayments />,
}: {
	noPaymentMethods?: JSX.Element | undefined;
	onlyExpressPayments?: JSX.Element | undefined;
} ) => {
	const {
		paymentMethodsInitialized,
		expressPaymentMethodsInitialized,
		availablePaymentMethods,
		availableExpressPaymentMethods,
		savedPaymentMethods,
	} = useSelect( ( select ) => {
		const store = select( paymentStore );
		return {
			paymentMethodsInitialized: store.paymentMethodsInitialized(),
			expressPaymentMethodsInitialized:
				store.expressPaymentMethodsInitialized(),
			availablePaymentMethods: store.getAvailablePaymentMethods(),
			availableExpressPaymentMethods:
				store.getAvailableExpressPaymentMethods(),
			savedPaymentMethods: store.getSavedPaymentMethods(),
		};
	} );

	const hasAvailablePaymentMethods =
		Object.keys( availablePaymentMethods ).length > 0;
	const hasAvailableExpressPaymentMethods =
		Object.keys( availableExpressPaymentMethods ).length > 0;

	if ( paymentMethodsInitialized && expressPaymentMethodsInitialized ) {
		// No payment methods available at all
		if (
			! hasAvailablePaymentMethods &&
			! hasAvailableExpressPaymentMethods
		) {
			return noPaymentMethods;
		}

		// Only express payment methods available
		if (
			hasAvailableExpressPaymentMethods &&
			! hasAvailablePaymentMethods
		) {
			return onlyExpressPayments;
		}
	}

	return (
		<DelayedContentWithSkeleton
			isLoading={
				! paymentMethodsInitialized ||
				! expressPaymentMethodsInitialized
			}
			skeleton={ <CheckoutPaymentSkeleton /> }
		>
			<SavedPaymentMethodOptions />
			{ Object.keys( savedPaymentMethods ).length > 0 && (
				<Label
					label={ __( 'Use another payment method.', 'woocommerce' ) }
					screenReaderLabel={ __(
						'Other available payment methods',
						'woocommerce'
					) }
					wrapperElement="p"
					wrapperProps={ {
						className: [
							'wc-block-components-checkout-step__description wc-block-components-checkout-step__description-payments-aligned',
						],
					} }
				/>
			) }
			<PaymentMethodOptions />
		</DelayedContentWithSkeleton>
	);
};

export default PaymentMethods;
