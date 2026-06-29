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

const isFeatureEnabled = ( feature: string ) =>
	Boolean(
		window.wcAdminFeatures?.[ feature ] ||
			document.body.classList.contains(
				`woocommerce-feature-enabled-${ feature }`
			)
	);

if ( isFeatureEnabled( 'shipping-smart-defaults' ) ) {
	import( './experimental-shipping-recommendation' );
}
