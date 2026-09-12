/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

/**
 * Get the source of the marketing recommendations.
 *
 * When the marketplace suggestions feature is turned on, the source is 'woocommerce.com'. Otherwise, it is 'plugin-woocommerce'.
 */
export const getRecommendationSource = () => {
	if ( getAdminSetting( 'allowMarketplaceSuggestions', false ) ) {
		return 'woocommerce.com';
	}

	return 'plugin-woocommerce';
};
