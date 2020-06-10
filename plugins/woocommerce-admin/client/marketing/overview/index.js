/**
 * WooCommerce dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * WooCommerce dependencies
 */
import { withOptionsHydration } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './style.scss';
import InstalledExtensions from './installed-extensions';
import RecommendedExtensions from './recommended-extensions';
import KnowledgeBase from './knowledge-base';
import WelcomeCard from './welcome-card';
import '../data';

const MarketingOverview = () => {
	const allowMarketplaceSuggestions = getSetting( 'allowMarketplaceSuggestions', false );

	return (
		<div className="woocommerce-marketing-overview">
			<WelcomeCard />
			<InstalledExtensions />
			{ allowMarketplaceSuggestions && <RecommendedExtensions /> }
			<KnowledgeBase />
		</div>
	);
};

export default withOptionsHydration( {
	...( window.wcSettings.preloadOptions || {} ),
} )( MarketingOverview );
