/**
 * External dependencies
 */
import { useUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import { InstalledExtensions } from './InstalledExtensions';
import { DiscoverTools } from './DiscoverTools';
import { LearnMarketing } from './LearnMarketing';
import '~/marketing/data';
import './MarketingOverviewMultichannel.scss';

export const MarketingOverviewMultichannel: React.FC = () => {
	const { currentUserCan } = useUser();

	const shouldShowExtensions =
		getAdminSetting( 'allowMarketplaceSuggestions', false ) &&
		currentUserCan( 'install_plugins' );

	return (
		<div className="woocommerce-marketing-overview-multichannel">
			<InstalledExtensions />
			{ shouldShowExtensions && <DiscoverTools /> }
			<LearnMarketing />
		</div>
	);
};
