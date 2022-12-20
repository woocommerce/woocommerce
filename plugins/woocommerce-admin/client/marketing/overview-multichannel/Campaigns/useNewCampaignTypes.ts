/**
 * Internal dependencies
 */
import { CampaignType } from '~/marketing/types/CampaignType';

type UseNewCampaignTypes = {
	loading: boolean;
	data: Array< CampaignType >;
};

export const useNewCampaignTypes = (): UseNewCampaignTypes => {
	return {
		loading: false,
		data: [
			{
				id: 'google-ads',
				icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/google.svg',
				name: 'Google',
				description:
					'Boost your product listings with a campaign that is automatically optimized to meet your goals',
				createUrl:
					'https://wc1.test/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fsetup-ads',
				channelSlug: 'google-listings-and-ads',
				channelName: 'Google Listings and Ads',
			},
			{
				id: 'pinterest-ads',
				icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/pinterest.svg',
				name: 'Pinterest',
				description:
					'Create single or multi image ads that promote a product relevant to people’s interests (Opens in Pinterest Ads Manager)',
				createUrl: 'https://pinterest.com/create-campaign',
				channelSlug: 'pinterest-for-woocommerce',
				channelName: 'Pinterest for WooCommerce',
			},
		],
	};
};
