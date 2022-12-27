/**
 * External dependencies
 */
import { useUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import { IntroductionBanner } from './IntroductionBanner';
import { Campaigns } from './Campaigns';
import { Channels } from './Channels';
import { InstalledExtensions } from './InstalledExtensions';
import { DiscoverTools } from './DiscoverTools';
import { LearnMarketing } from './LearnMarketing';
import '~/marketing/data';
import {
	useIntroductionBanner,
	useRegisteredChannels,
	useRecommendedChannels,
} from '~/marketing/hooks';
import './MarketingOverviewMultichannel.scss';
import { CenteredSpinner } from '../components';

export const MarketingOverviewMultichannel: React.FC = () => {
	const {
		loading: loadingIntroductionBanner,
		isIntroductionBannerDismissed,
		dismissIntroductionBanner,
	} = useIntroductionBanner();
	const { loading: loadingRegistered, data: dataRegistered } =
		useRegisteredChannels();
	const { loading: loadingRecommended, data: dataRecommended } =
		useRecommendedChannels();
	const { currentUserCan } = useUser();

	const shouldShowExtensions =
		getAdminSetting( 'allowMarketplaceSuggestions', false ) &&
		currentUserCan( 'install_plugins' );

	if (
		loadingIntroductionBanner ||
		loadingRegistered ||
		loadingRecommended
	) {
		return <CenteredSpinner />;
	}

	return (
		<div className="woocommerce-marketing-overview-multichannel">
			{ ! isIntroductionBannerDismissed && (
				<IntroductionBanner
					showButtons={ dataRegistered.length >= 1 }
					onDismiss={ dismissIntroductionBanner }
				/>
			) }
			{ dataRegistered.length >= 1 && <Campaigns /> }
			{ ( dataRegistered.length >= 1 || dataRecommended.length >= 1 ) && (
				<Channels
					registeredChannels={ dataRegistered }
					recommendedChannels={ dataRecommended }
				/>
			) }
			<InstalledExtensions />
			{ shouldShowExtensions && <DiscoverTools /> }
			<LearnMarketing />
		</div>
	);
};
