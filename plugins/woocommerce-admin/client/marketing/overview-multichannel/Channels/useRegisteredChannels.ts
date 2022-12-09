export const useRegisteredChannels = () => {
	// TODO: call API here to get data.
	// The following are just dummy data for testing now.
	return {
		loading: false,
		data: [
			{
				slug: 'google-listings-and-ads',
				name: 'Google Listings and Ads',
				title: 'Google Listings and Ads',
				description:
					'Get in front of shoppers and drive traffic so you can grow your business with Smart Shopping Campaigns and free listings.',
				icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/google.svg',
				isSetupCompleted: true,
				setupUrl: 'www.google.com/setup',
				manageUrl: 'www.google.com/manage',
				syncStatus: 'synced' as const,
				issueType: 'none' as const,
				issueText: '',
			},
		],
	};
};
