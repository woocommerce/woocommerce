/**
 * External dependencies
 */
import { useUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import { Channels } from './Channels';
import { InstalledExtensions } from './InstalledExtensions';
import { DiscoverTools } from './DiscoverTools';
import { LearnMarketing } from './LearnMarketing';
import '~/marketing/data';
import '~/marketing/data-multichannel';
import {
	useRegisteredChannels,
	useRecommendedChannels,
} from '~/marketing/hooks';
import './MarketingOverviewMultichannel.scss';
import { CenteredSpinner } from '../components';

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
			{ dataRegistered &&
				dataRecommended &&
				( dataRegistered.length >= 1 ||
					dataRecommended.length >= 1 ) && (
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
