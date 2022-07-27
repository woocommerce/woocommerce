/**
 * Internal dependencies
 */
import InstalledExtensionsCard from './InstalledExtensionsCard';
import '../data';
import './MarketingOverviewMultichannel.scss';

export const MarketingOverviewMultichannel: React.FC = () => {
	return (
		<div className="woocommerce-marketing-overview-multichannel">
			<InstalledExtensionsCard />
		</div>
	);
};
