/**
 * External dependencies
 */
import { useUser } from '@woocommerce/data';
import { ScrollTo } from '@woocommerce/components';

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
	useIsLocationHashAddChannels,
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
		loading: loadingRegistered,
		data: dataRegistered,
		refetch,
	} = useRegisteredChannels();
	const { loading: loadingRecommended, data: dataRecommended } =
		useRecommendedChannels();
	const isLocationHashAddChannels = useIsLocationHashAddChannels();
	const { currentUserCan } = useUser();

	const shouldShowExtensions =
		getAdminSetting( 'allowMarketplaceSuggestions', false ) &&
		currentUserCan( 'install_plugins' );

	if (
		loadingIntroductionBanner ||
		( loadingRegistered && ! dataRegistered ) ||
		( loadingRecommended && ! dataRecommended )
	) {
		return <CenteredSpinner />;
	}

	return (
		<div className="woocommerce-marketing-overview-multichannel">
			{ ! isIntroductionBannerDismissed && (
				<IntroductionBanner
					showButtons={
						!! dataRegistered && dataRegistered.length >= 1
					}
					onDismiss={ dismissIntroductionBanner }
				/>
			) }
			{ dataRegistered?.length && <Campaigns /> }
			{ dataRegistered &&
				dataRecommended &&
				( dataRegistered.length || dataRecommended.length ) &&
				( isLocationHashAddChannels ? (
					<ScrollTo>
						<Channels
							registeredChannels={ dataRegistered }
							recommendedChannels={ dataRecommended }
							onInstalledAndActivated={ refetch }
						/>
					</ScrollTo>
				) : (
					<Channels
						registeredChannels={ dataRegistered }
						recommendedChannels={ dataRecommended }
						onInstalledAndActivated={ refetch }
					/>
				) ) }
			<InstalledExtensions />
			{ shouldShowExtensions && <DiscoverTools /> }
			<LearnMarketing />
		</div>
	);
};
