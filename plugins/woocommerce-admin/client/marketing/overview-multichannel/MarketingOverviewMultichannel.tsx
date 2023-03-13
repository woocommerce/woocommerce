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
	useCampaignTypes,
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
		loading: loadingCampaignTypes,
		data: dataCampaignTypes,
		refetch: refetchCampaignTypes,
	} = useCampaignTypes();
	const {
		loading: loadingRegistered,
		data: dataRegistered,
		refetch: refetchRegisteredChannels,
	} = useRegisteredChannels();
	const { loading: loadingRecommended, data: dataRecommended } =
		useRecommendedChannels();
	const { currentUserCan } = useUser();

	if (
		( loadingCampaignTypes && ! dataCampaignTypes ) ||
		( loadingRegistered && ! dataRegistered ) ||
		( loadingRecommended && ! dataRecommended )
	) {
		return <CenteredSpinner />;
	}

	const shouldShowExtensions =
		getAdminSetting( 'allowMarketplaceSuggestions', false ) &&
		currentUserCan( 'install_plugins' );

	const refetch = () => {
		refetchCampaignTypes();
		refetchRegisteredChannels();
	};

	return (
		<div className="woocommerce-marketing-overview-multichannel">
			{ !! dataRegistered?.length && <Campaigns /> }
			{ dataRegistered &&
				dataRecommended &&
				!! ( dataRegistered.length || dataRecommended.length ) && (
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
