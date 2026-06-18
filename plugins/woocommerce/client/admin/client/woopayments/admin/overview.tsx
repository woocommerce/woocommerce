/**
 * Internal dependencies
 */
import { WooPaymentsAccountSettings } from '~/woopayments/settings/account-settings';
import './style.scss';

export const WooPaymentsOverview = () => {
	return (
		<div className="woocommerce-woopayments-overview">
			<WooPaymentsAccountSettings />
		</div>
	);
};

export default WooPaymentsOverview;
