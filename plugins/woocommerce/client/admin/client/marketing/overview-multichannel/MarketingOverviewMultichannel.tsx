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
import Promotions from '~/marketplace/components/promotions/promotions';
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

	const showCampaigns = !! (
		dataRegistered?.length &&
		( isIntroductionBannerDismissed || metaCampaigns?.total )
	);

	const showChannels = !! (
		dataRegistered &&
		dataRecommended &&
		( dataRegistered.length || dataRecommended.length )
	);

	const showSuggestions = !! getAdminSetting(
		'allowMarketplaceSuggestions',
		false
	);

	const showExtensions =
		showSuggestions && currentUserCan( 'install_plugins' );

	const onInstalledAndActivated = ( pluginSlug: string ) => {
		refetchCampaignTypes();
		refetchRegisteredChannels();
		loadInstalledPluginsAfterActivation( pluginSlug );
	};

	return (
		<div className="woocommerce-marketing-overview-multichannel">
			<Promotions format="promo-card" />
			{ ! isIntroductionBannerDismissed && (
				<IntroductionBanner
					onDismissClick={ dismissIntroductionBanner }
					onAddChannelsClick={ () => {
						channelsRef.current?.scrollIntoAddChannels();
					} }
				/>
			) }
			{ showCampaigns && <Campaigns /> }
			{ showChannels && (
				<Channels
					ref={ channelsRef }
					registeredChannels={ dataRegistered }
					recommendedChannels={ dataRecommended }
					onInstalledAndActivated={ onInstalledAndActivated }
				/>
			) }
			<InstalledExtensions />
			{ showExtensions && <DiscoverTools /> }
			{ showSuggestions && <LearnMarketing /> }
		</div>
	);
};
