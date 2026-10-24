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
		void import(
			/* webpackChunkName: "import-products" */ './import-products'
		);
	} else {
		void import( /* webpackChunkName: "products" */ './products' );
	}
};

void possiblyImportProductTask();

void import(
	/* webpackChunkName: "shipping-recommendation" */ './shipping-recommendation'
);
