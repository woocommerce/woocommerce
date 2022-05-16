/**
 * External dependencies
 */
import { isProductTaskExperimentTreatment } from '@woocommerce/onboarding';

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
	const isExperiment = await isProductTaskExperimentTreatment();
	if ( isExperiment ) {
		if ( isImportProductExperiment() ) {
			import( './experimental-import-products' );
		} else {
			import( './experimental-products' );
		}
	} else {
		import( './products' );
	}
};

if (
	window.wcAdminFeatures &&
	( window.wcAdminFeatures[ 'experimental-import-products-task' ] ||
		window.wcAdminFeatures[ 'experimental-products-task' ] )
) {
	possiblyImportProductTaskExperiment();
} else {
	import( './products' );
}
