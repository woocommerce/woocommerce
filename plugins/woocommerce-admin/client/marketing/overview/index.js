/**
 * External dependencies
 */
import { useUser, withOptionsHydration } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './style.scss';
import InstalledExtensions from './installed-extensions';
import RecommendedExtensions from '../components/recommended-extensions';
import KnowledgeBase from '../components/knowledge-base';
import WelcomeCard from './welcome-card';
import { getAdminSetting } from '~/utils/admin-settings';
import { MarketingOverviewSectionSlot } from './section-slot';
import '../data';

const MarketingOverview = () => {
	const { currentUserCan } = useUser();

	const shouldShowExtensions =
		getAdminSetting( 'allowMarketplaceSuggestions', false ) &&
		currentUserCan( 'install_plugins' );

	return (
		<div className="woocommerce-marketing-overview">
			<WelcomeCard />
			<InstalledExtensions />
			<MarketingOverviewSectionSlot />
			{ shouldShowExtensions && (
				<RecommendedExtensions category="marketing" />
			) }
			<KnowledgeBase category="marketing" />
		</div>
	);
};

export default withOptionsHydration( {
	...getAdminSetting( 'preloadOptions', {} ),
} )( MarketingOverview );
