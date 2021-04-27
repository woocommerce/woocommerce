/**
 * Internal dependencies
 */
import { LocalPayments } from './LocalPayments';
import { RemotePayments } from './RemotePayments';

export const Payments = ( { query } ) => {
	if ( window.wcAdminFeatures[ 'remote-payment-methods' ] ) {
		return <RemotePayments query={ query } />;
	}

	return <LocalPayments query={ query } />;
};

export default Payments;
