/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import './settings-payments-main.scss';
import { PaymentGateways } from '~/settings-payments/components/payment-gateways';
import { OtherPaymentGateways } from '~/settings-payments/components/other-payment-gateways';

export const SettingsPaymentsMain: React.FC = () => {
	return (
		<div className="settings-payments-main__container">
			<PaymentGateways />
			<OtherPaymentGateways />
		</div>
	);
};

export default SettingsPaymentsMain;
