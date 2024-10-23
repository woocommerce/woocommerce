/**
 * External dependencies
 */
import '@wordpress/element';

/**
 * Internal dependencies
 */
import './settings-payments-offline.scss';
import { OfflinePaymentGateways } from './components/offline-payment-gateways';

export const SettingsPaymentsOffline: React.FC = () => {
	return (
		<div className="settings-payments-offline__container">
			<OfflinePaymentGateways />
		</div>
	);
};

export default SettingsPaymentsOffline;
