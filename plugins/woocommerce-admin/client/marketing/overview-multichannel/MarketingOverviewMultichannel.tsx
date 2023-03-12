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
	useRegisteredChannels,
	useRecommendedChannels,
	useCampaignTypes,
} from '~/marketing/hooks';
import { getAdminSetting } from '~/utils/admin-settings';
import { IntroductionBanner } from './IntroductionBanner';
import { Campaigns } from './Campaigns';
import { Channels } from './Channels';
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
	const addChannelsButtonRef = useRef< HTMLButtonElement >( null );

	if (
		loadingIntroductionBanner ||
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
			{ ! isIntroductionBannerDismissed && (
				<IntroductionBanner
					onDismiss={ dismissIntroductionBanner }
					onAddChannels={ () => {
						addChannelsButtonRef.current?.focus();
						addChannelsButtonRef.current?.click();
						addChannelsButtonRef.current?.scrollIntoView();
					} }
				/>
			) }
			{ dataRegistered?.length && <Campaigns /> }
			{ dataRegistered &&
				dataRecommended &&
				( dataRegistered.length || dataRecommended.length ) && (
					<Channels
						addChannelsButtonRef={ addChannelsButtonRef }
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
