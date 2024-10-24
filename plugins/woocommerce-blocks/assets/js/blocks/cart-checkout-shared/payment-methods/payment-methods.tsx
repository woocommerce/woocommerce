/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Label } from '@woocommerce/blocks-components';
import { useSelect } from '@wordpress/data';
import { PAYMENT_STORE_KEY } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import NoPaymentMethods from './no-payment-methods';
import PaymentMethodOptions from './payment-method-options';
import SavedPaymentMethodOptions from './saved-payment-method-options';
import './style.scss';

/**
 * PaymentMethods component.
 */
const PaymentMethods = ( {
	noPaymentMethods = <NoPaymentMethods />,
}: {
	noPaymentMethods?: JSX.Element | undefined;
} ) => {
	const {
		paymentMethodsInitialized,
		availablePaymentMethods,
		savedPaymentMethods,
	} = useSelect( ( select ) => {
		const store = select( PAYMENT_STORE_KEY );
		return {
			paymentMethodsInitialized: store.paymentMethodsInitialized(),
			availablePaymentMethods: store.getAvailablePaymentMethods(),
			savedPaymentMethods: store.getSavedPaymentMethods(),
		};
	} );

	if (
		paymentMethodsInitialized &&
		Object.keys( availablePaymentMethods ).length === 0
	) {
		return noPaymentMethods;
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
