/**
 * External dependencies
 */
import { isProductTaskExperimentTreatment } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

import './PaymentGatewaySuggestions';
import './shipping';
import './Marketing';
import './appearance';
import './connect';
import './tax';
import './woocommerce-payments';
import './purchase';

const onboardingData = getAdminSetting( 'onboarding' );

const possiblyImportProductTaskExperiment = async () => {
	const isExperiment = await isProductTaskExperimentTreatment();
	if ( isExperiment ) {
		if (
			window.wcAdminFeatures[ 'experimental-import-products-task' ] &&
			onboardingData?.profile?.selling_venues &&
			onboardingData?.profile?.selling_venues !== 'no'
		) {
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
