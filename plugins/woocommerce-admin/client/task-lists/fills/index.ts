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
		import( /* webpackChunkName: "import-products" */ './import-products' );
	} else {
		import( /* webpackChunkName: "products" */ './products' );
	}
};

possiblyImportProductTask();

if (
	window.wcAdminFeatures &&
	window.wcAdminFeatures[ 'shipping-smart-defaults' ]
) {
	import(
		/* webpackChunkName: "experimental-shipping-recommendation" */ './experimental-shipping-recommendation'
	);
}
