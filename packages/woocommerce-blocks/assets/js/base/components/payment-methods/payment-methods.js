/**
 * External dependencies
 */
import { usePaymentMethods } from '@woocommerce/base-hooks';
import { __ } from '@wordpress/i18n';
import Label from '@woocommerce/base-components/label';
import { usePaymentMethodDataContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import NoPaymentMethods from './no-payment-methods';
import PaymentMethodOptions from './payment-method-options';
import SavedPaymentMethodOptions from './saved-payment-method-options';

/**
 * PaymentMethods component.
 *
 * @return {*} The rendered component.
 */
const PaymentMethods = () => {
	const { isInitialized, paymentMethods } = usePaymentMethods();
	const { customerPaymentMethods } = usePaymentMethodDataContext();

	if ( isInitialized && Object.keys( paymentMethods ).length === 0 ) {
		return <NoPaymentMethods />;
	}

	return (
		<>
			<SavedPaymentMethodOptions />
			{ Object.keys( customerPaymentMethods ).length > 0 && (
				<Label
					label={ __(
						'Use another payment method.',
						'woocommerce'
					) }
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
