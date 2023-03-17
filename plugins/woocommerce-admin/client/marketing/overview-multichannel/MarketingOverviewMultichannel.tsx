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
	useInstalledPlugins,
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
	const { loadInstalledPluginsAfterActivation } = useInstalledPlugins();
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

	const onInstalledAndActivated = ( pluginSlug: string ) => {
		refetchCampaignTypes();
		refetchRegisteredChannels();
		loadInstalledPluginsAfterActivation( pluginSlug );
	};

	return (
		<div className="woocommerce-marketing-overview-multichannel">
			{ !! dataRegistered?.length && <Campaigns /> }
			{ !! ( dataRegistered && dataRecommended ) &&
				!! ( dataRegistered.length || dataRecommended.length ) && (
					<Channels
						registeredChannels={ dataRegistered }
						recommendedChannels={ dataRecommended }
						onInstalledAndActivated={ onInstalledAndActivated }
					/>
				) }
			<InstalledExtensions />
			{ !! shouldShowExtensions && <DiscoverTools /> }
			<LearnMarketing />
		</div>
	);
};
