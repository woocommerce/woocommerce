/**
 * Internal dependencies
 */
import InstalledExtensions from './InstalledExtensions';
import '../data';
import './MarketingOverviewMultichannel.scss';

export const MarketingOverviewMultichannel: React.FC = () => {
	return (
		<div className="woocommerce-marketing-overview-multichannel">
			<InstalledExtensions />
		</div>
	);
};
