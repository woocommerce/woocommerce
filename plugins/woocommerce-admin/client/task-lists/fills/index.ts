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
		import( './import-products' );
	} else {
		import( './products' );
	}
};

possiblyImportProductTask();

if (
	window.wcAdminFeatures &&
	window.wcAdminFeatures[ 'shipping-smart-defaults' ]
) {
	import( './experimental-shipping-recommendation' );
}
