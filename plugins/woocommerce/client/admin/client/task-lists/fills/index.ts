/**
 * Internal dependencies
 */
import { isImportProduct } from './utils';
import './PaymentGatewaySuggestions';
import './shipping';
import './Marketing';
import './appearance';
import './tax';
import './woocommerce-payments';
import './deprecated-tasks';
import './launch-your-store';

const possiblyImportProductTask = async () => {
	if ( isImportProduct() ) {
		void import( './import-products' );
	} else {
		void import( './products' );
	}
};

void possiblyImportProductTask();

void import( './shipping-recommendation' );
