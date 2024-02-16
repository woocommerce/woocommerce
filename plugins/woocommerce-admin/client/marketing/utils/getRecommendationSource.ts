/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

export const getRecommendationSource = () => {
	if ( getAdminSetting( 'allowMarketplaceSuggestions', false ) ) {
		return 'woocommerce.com';
	}

	return 'plugin-woocommerce';
};
