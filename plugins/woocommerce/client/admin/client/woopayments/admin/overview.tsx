/**
 * Internal dependencies
 */
import { WooPaymentsOverviewPage } from './overview/page';
import './style.scss';

export const WooPaymentsOverview = () => {
	return (
		<div className="woocommerce-woopayments-overview">
			<WooPaymentsOverviewPage />
		</div>
	);
};

export default WooPaymentsOverview;
