/**
 * Internal dependencies
 */
import { LocalPayments } from './LocalPayments';
import { PaymentGatewaySuggestions } from './PaymentGatewaySuggestions';

export const Payments = ( { query } ) => {
	if ( window.wcAdminFeatures[ 'payment-gateway-suggestions' ] ) {
		return <PaymentGatewaySuggestions query={ query } />;
	}

	return <LocalPayments query={ query } />;
};

export default Payments;
