/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { useUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import '~/marketing/data';
import '~/marketing/data-multichannel';
import { CenteredSpinner } from '~/marketing/components';
import {
	useIntroductionBanner,
	useCampaigns,
	useRegisteredChannels,
	useRecommendedChannels,
	useCampaignTypes,
	useInstalledPluginsWithoutChannels,
} from '~/marketing/hooks';
import { getAdminSetting } from '~/utils/admin-settings';
import { IntroductionBanner } from './IntroductionBanner';
import { Campaigns } from './Campaigns';
import { Channels, ChannelsRef } from './Channels';
import { InstalledExtensions } from './InstalledExtensions';
import { DiscoverTools } from './DiscoverTools';
import { LearnMarketing } from './LearnMarketing';
import './MarketingOverviewMultichannel.scss';

export const MarketingOverviewMultichannel: React.FC = () => {
	const {
		loading: loadingIntroductionBanner,
		isIntroductionBannerDismissed,
		dismissIntroductionBanner,
	} = useIntroductionBanner();
	const { loading: loadingCampaigns, meta: metaCampaigns } = useCampaigns();
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
	const { loadInstalledPluginsAfterActivation } =
		useInstalledPluginsWithoutChannels();
	const { currentUserCan } = useUser();
	const channelsRef = useRef< ChannelsRef >( null );

	if (
		loadingIntroductionBanner ||
		( loadingCampaigns && metaCampaigns?.total === undefined ) ||
		( loadingCampaignTypes && ! dataCampaignTypes ) ||
		( loadingRegistered && ! dataRegistered ) ||
		( loadingRecommended && ! dataRecommended )
	) {
		return <CenteredSpinner />;
	}

	const shouldShowCampaigns = !! (
		dataRegistered?.length &&
		( isIntroductionBannerDismissed || metaCampaigns?.total )
	);

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
			{ ! isIntroductionBannerDismissed && (
				<IntroductionBanner
					onDismissClick={ dismissIntroductionBanner }
					onAddChannelsClick={ () => {
						channelsRef.current?.scrollIntoAddChannels();
					} }
				/>
			) }
			{ shouldShowCampaigns && <Campaigns /> }
			{ !! ( dataRegistered && dataRecommended ) &&
				!! ( dataRegistered.length || dataRecommended.length ) && (
					<Channels
						ref={ channelsRef }
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
