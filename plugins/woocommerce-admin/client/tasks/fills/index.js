/**
 * Internal dependencies
 */
import { isImportProductExperiment } from './product-task-experiment';
import './PaymentGatewaySuggestions';
import './shipping';
import './Marketing';
import './appearance';
import './connect';
import './tax';
import './woocommerce-payments';
import './purchase';

const possiblyImportProductTaskExperiment = async () => {
	if ( isImportProductExperiment() ) {
		import( './experimental-import-products' );
	} else {
		import( './experimental-products' );
	}
};

possiblyImportProductTaskExperiment();

if (
	window.wcAdminFeatures &&
	window.wcAdminFeatures[ 'shipping-smart-defaults' ]
) {
	import( './experimental-shipping-recommendation' );
}
