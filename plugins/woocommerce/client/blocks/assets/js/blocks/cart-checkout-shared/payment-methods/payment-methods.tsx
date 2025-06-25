/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Label } from '@woocommerce/blocks-components';
import { useSelect } from '@wordpress/data';
import { paymentStore } from '@woocommerce/block-data';
import { CheckoutPaymentSkeleton } from '@woocommerce/base-components/skeleton/patterns/checkout-payment';

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

	if ( ! paymentMethodsInitialized ) {
		return <CheckoutPaymentSkeleton />;
	}

	const hasPaymentMethods =
		paymentMethodsInitialized &&
		Object.keys( availablePaymentMethods ).length > 0;
	const hasExpressPaymentMethods =
		expressPaymentMethodsInitialized &&
		Object.keys( availableExpressPaymentMethods ).length > 0;

	if ( ! hasPaymentMethods && ! hasExpressPaymentMethods ) {
		return noPaymentMethods;
	}

	if ( hasExpressPaymentMethods && ! hasPaymentMethods ) {
		return onlyExpressPayments;
	}

	return (
		<>
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
		</>
	);
};

export default PaymentMethods;
