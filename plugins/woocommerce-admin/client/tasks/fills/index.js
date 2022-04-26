/**
 * Internal dependencies
 */
import './PaymentGatewaySuggestions';
import './shipping';
import './Marketing';
import './appearance';
import './connect';
import './tax';
import './woocommerce-payments';
import './purchase';

if (
	window.wcAdminFeatures &&
	window.wcAdminFeatures[ 'experimental-products-task' ] &&
	window.wcSettings?.admin?.onboarding?.profile?.selling_venues &&
	window.wcSettings.admin.onboarding.profile.selling_venues !== 'no'
) {
	import( './experimental-products' );
} else {
	import( './products' );
}
