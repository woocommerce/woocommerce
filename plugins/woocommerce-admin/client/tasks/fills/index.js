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
	window.wcAdminFeatures[ 'experimental-products-task' ]
) {
	import( './experimental-products' );
} else {
	import( './products' );
}
