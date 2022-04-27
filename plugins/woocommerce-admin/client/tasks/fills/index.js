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

if (
	window.wcAdminFeatures &&
	window.wcAdminFeatures[ 'experimental-import-products-task' ] &&
	onboardingData?.profile?.selling_venues &&
	onboardingData?.profile?.selling_venues !== 'no'
) {
	import( './experimental-import-products' );
} else if (
	window.wcAdminFeatures &&
	window.wcAdminFeatures[ 'experimental-products-task' ]
) {
	import( './experimental-products' );
} else {
	import( './products' );
}
