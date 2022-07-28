/**
 * Internal dependencies
 */
import InstalledExtensions from './InstalledExtensions';
import LearnMarketing from './LearnMarketing';
import '../data';
import './MarketingOverviewMultichannel.scss';

export const MarketingOverviewMultichannel: React.FC = () => {
	return (
		<div className="woocommerce-marketing-overview-multichannel">
			<InstalledExtensions />
			<LearnMarketing />
		</div>
	);
};
