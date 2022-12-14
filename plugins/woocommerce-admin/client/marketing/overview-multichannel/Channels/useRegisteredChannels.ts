// // TODO: To be removed. This is for testing loading state.
// export const useRegisteredChannels = () => {
// 	// TODO: call API here to get data.
// 	// The following are just dummy data for testing now.
// 	return {
// 		loading: true,
// 		data: [],
// 	};
// };

// // TODO: To be removed. This is for testing isSetupCompleted = false.
// export const useRegisteredChannels = () => {
// 	// TODO: call API here to get data.
// 	// The following are just dummy data for testing now.
// 	return {
// 		loading: false,
// 		data: [
// 			{
// 				slug: 'google-listings-and-ads',
// 				name: 'Google Listings and Ads',
// 				title: 'Google Listings and Ads',
// 				description:
// 					'Get in front of shoppers and drive traffic so you can grow your business with Smart Shopping Campaigns and free listings.',
// 				icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/google.svg',
// 				isSetupCompleted: false,
// 				setupUrl: 'https://www.example.com/setup',
// 				manageUrl: 'https://www.example.com/manage',
// 				syncStatus: 'synced' as const,
// 				issueType: 'none' as const,
// 				issueText: 'No issues to resolve',
// 			},
// 		],
// 	};
// };

// // TODO: To be removed. This is for testing error state.
// export const useRegisteredChannels = () => {
// 	// TODO: call API here to get data.
// 	// The following are just dummy data for testing now.
// 	return {
// 		loading: false,
// 		data: [
// 			{
// 				slug: 'google-listings-and-ads',
// 				name: 'Google Listings and Ads',
// 				title: 'Google Listings and Ads',
// 				description:
// 					'Get in front of shoppers and drive traffic so you can grow your business with Smart Shopping Campaigns and free listings.',
// 				icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/google.svg',
// 				isSetupCompleted: true,
// 				setupUrl: 'https://www.example.com/setup',
// 				manageUrl: 'https://www.example.com/manage',
// 				syncStatus: 'failed' as const,
// 				issueType: 'error' as const,
// 				issueText: '3 issues to resolve',
// 			},
// 		],
// 	};
// };

// TODO: To be removed. This is for testing everything works okay.
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
				setupUrl: 'https://www.example.com/setup',
				manageUrl: 'https://www.example.com/manage',
				syncStatus: 'synced' as const,
				issueType: 'none' as const,
				issueText: 'No issues to resolve',
			},
		],
	};
};
