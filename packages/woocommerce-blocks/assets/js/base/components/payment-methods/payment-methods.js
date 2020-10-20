/**
 * External dependencies
 */
import { usePaymentMethods } from '@woocommerce/base-hooks';
import { useCallback, useState } from '@wordpress/element';

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
	const [ showNewPaymentMethods, setShowNewPaymentMethods ] = useState(
		true
	);
	const onChange = useCallback(
		( token ) => {
			setShowNewPaymentMethods( token === '0' );
		},
		[ setShowNewPaymentMethods ]
	);

	if ( isInitialized && Object.keys( paymentMethods ).length === 0 ) {
		return <NoPaymentMethods />;
	}

	return (
		<>
			<SavedPaymentMethodOptions onChange={ onChange } />
			{ showNewPaymentMethods && <PaymentMethodOptions /> }
		</>
	);
};

export default PaymentMethods;
