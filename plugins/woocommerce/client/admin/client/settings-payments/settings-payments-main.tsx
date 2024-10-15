/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import './settings-payments-main.scss';
import { MainPaymentMethods } from '~/settings-payments/components/main-payment-methods';
import { OtherPaymentMethods } from '~/settings-payments/components/other-payment-methods';

export const SettingsPaymentsMain: React.FC = () => {
	return (
		<div className="settings-payments-main__container">
			<MainPaymentMethods />
			<OtherPaymentMethods />
		</div>
	);
};

export default SettingsPaymentsMain;
