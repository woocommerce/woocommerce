/**
 * External dependencies
 */
import { usePaymentMethods } from '@woocommerce/base-hooks';
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
	const {
		customerPaymentMethods = {},
		paymentMethodData,
	} = usePaymentMethodDataContext();
	const { isInitialized, paymentMethods } = usePaymentMethods();

	if ( isInitialized && Object.keys( paymentMethods ).length === 0 ) {
		return <NoPaymentMethods />;
	}

	return Object.keys( customerPaymentMethods ).length > 0 &&
		paymentMethodData.isSavedToken ? (
		<SavedPaymentMethodOptions />
	) : (
		<>
			<SavedPaymentMethodOptions />
			<PaymentMethodOptions />
		</>
	);
};

export default PaymentMethods;
