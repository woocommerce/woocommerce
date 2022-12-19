/**
 * Internal dependencies
 */
import { Campaign } from '~/marketing/types';

type UseCampaignsType = {
	loading: boolean;
	data: Array< Campaign >;
};

// // TODO: testing for loading state.
// export const useCampaigns = (): UseCampaignsType => {
// 	return {
// 		loading: true,
// 		data: [],
// 	};
// };

// TODO: testing for empty data.
// export const useCampaigns = (): UseCampaignsType => {
// 	return {
// 		loading: false,
// 		data: [],
// 	};
// };

// TODO: testing with campaigns data.
export const useCampaigns = (): UseCampaignsType => {
	return {
		loading: false,
		data: [
			{
				channelSlug: 'google-listings-and-ads',
				channelName: 'Google Listings and Ads',
				icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/google.svg',
				id: 'gla-campaign-01',
				title: 'Performance Max 01',
				description: 'New Zealand',
				cost: '$50',
				manageUrl: 'https://www.google.com/manage-campaign',
			},
			{
				channelSlug: 'google-listings-and-ads',
				channelName: 'Google Listings and Ads',
				icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/google.svg',
				id: 'gla-campaign-02',
				title: 'Performance Max 02',
				description: '6 countries',
				cost: '$50',
				manageUrl: 'https://www.google.com/manage-campaign',
			},
			{
				channelSlug: 'google-listings-and-ads',
				channelName: 'Google Listings and Ads',
				icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/google.svg',
				id: 'gla-campaign-03',
				title: 'Performance Max 03',
				description: '10 countries • 15 Sep - 31 Oct 2022',
				cost: '$50',
				manageUrl: 'https://www.google.com/manage-campaign',
			},
			{
				channelSlug: 'google-listings-and-ads',
				channelName: 'Google Listings and Ads',
				icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/google.svg',
				id: 'gla-campaign-04',
				title: 'Performance Max 04',
				description: 'New Zealand',
				cost: '$50',
				manageUrl: 'https://www.google.com/manage-campaign',
			},
			{
				channelSlug: 'google-listings-and-ads',
				channelName: 'Google Listings and Ads',
				icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/google.svg',
				id: 'gla-campaign-05',
				title: 'Performance Max 05',
				description: 'New Zealand',
				cost: '$50',
				manageUrl: 'https://www.google.com/manage-campaign',
			},
			{
				channelSlug: 'google-listings-and-ads',
				channelName: 'Google Listings and Ads',
				icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/google.svg',
				id: 'gla-campaign-06',
				title: 'Performance Max 06',
				description: 'New Zealand',
				cost: '$50',
				manageUrl: 'https://www.google.com/manage-campaign',
			},
			{
				channelSlug: 'google-listings-and-ads',
				channelName: 'Google Listings and Ads',
				icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/google.svg',
				id: 'gla-campaign-07',
				title: 'Performance Max 07',
				description: 'New Zealand',
				cost: '$50',
				manageUrl: 'https://www.google.com/manage-campaign',
			},
		],
	};
};
