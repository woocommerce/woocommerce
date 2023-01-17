/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { InstalledChannel } from '~/marketing/types';
import { STORE_KEY } from '~/marketing/data-multichannel/constants';
import { ApiFetchError, Channel, Channels } from '../data-multichannel/types';

type UseRegisteredChannels = {
	loading: boolean;
	data: Array< InstalledChannel >;
	error?: ApiFetchError;
};

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

// // TODO: To be removed. This is for testing everything works okay.
// export const useRegisteredChannels = (): UseRegisteredChannels => {
// 	// TODO: call API here to get data.
// 	// The following are just dummy data for testing now.
// 	return {
// 		loading: false,
// 		data: [
// 			{
// 				slug: 'google-listings-and-ads',
// 				title: 'Google Listings and Ads',
// 				description:
// 					'Get in front of shoppers and drive traffic so you can grow your business with Smart Shopping Campaigns and free listings.',
// 				icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/google.svg',
// 				isSetupCompleted: true,
// 				setupUrl: 'https://www.example.com/setup',
// 				manageUrl: 'https://www.example.com/manage',
// 				syncStatus: 'synced' as const,
// 				issueType: 'none' as const,
// 				issueText: 'No issues to resolve',
// 			},
// 		],
// 	};
// };

const convert = ( data: Channel ): InstalledChannel => {
	// TODO: map all the fields correctly from API to UI.
	return {
		slug: data.slug,
		title: data.name,
		description: data.description,
		icon: data.icon,
		isSetupCompleted: data.is_setup_completed,
		setupUrl: data.settings_url,
		manageUrl: data.settings_url,
		syncStatus: 'synced',
		issueType: 'none',
		issueText: '',
	};
};

export const useRegisteredChannels = (): UseRegisteredChannels => {
	return useSelect( ( select ) => {
		const { hasFinishedResolution, getChannels } = select( STORE_KEY );
		const channels = getChannels< Channels >();

		return {
			loading: ! hasFinishedResolution( 'getChannels' ),
			data: channels.data?.map( convert ) || [],
			error: channels.error,
		};
	} );
};
