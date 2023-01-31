/**
 * External dependencies
 */
import { useUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import '~/marketing/data';
import '~/marketing/data-multichannel';
import { CenteredSpinner } from '~/marketing/components';
import {
	useRegisteredChannels,
	useRecommendedChannels,
} from '~/marketing/hooks';
import { getAdminSetting } from '~/utils/admin-settings';
import { Campaigns } from './Campaigns';
import { Channels } from './Channels';
import { InstalledExtensions } from './InstalledExtensions';
import { DiscoverTools } from './DiscoverTools';
import { LearnMarketing } from './LearnMarketing';
import './MarketingOverviewMultichannel.scss';

export const MarketingOverviewMultichannel: React.FC = () => {
	const {
		loading: loadingRegistered,
		data: dataRegistered,
		refetch,
	} = useRegisteredChannels();
	const { loading: loadingRecommended, data: dataRecommended } =
		useRecommendedChannels();
	const { currentUserCan } = useUser();

	const shouldShowExtensions =
		getAdminSetting( 'allowMarketplaceSuggestions', false ) &&
		currentUserCan( 'install_plugins' );

	if (
		( loadingRegistered && ! dataRegistered ) ||
		( loadingRecommended && ! dataRecommended )
	) {
		return <CenteredSpinner />;
	}

	return (
		<div className="woocommerce-marketing-overview-multichannel">
			{ dataRegistered?.length && <Campaigns /> }
			{ dataRegistered &&
				dataRecommended &&
				( dataRegistered.length || dataRecommended.length ) && (
					<Channels
						registeredChannels={ dataRegistered }
						recommendedChannels={ dataRecommended }
						onInstalledAndActivated={ refetch }
					/>
				) }
			<InstalledExtensions />
			{ shouldShowExtensions && <DiscoverTools /> }
			<LearnMarketing />
		</div>
	);
};
