/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { InstalledChannel, SyncStatusType } from '~/marketing/types';
import { STORE_KEY } from '~/marketing/data-multichannel/constants';
import {
	ApiFetchError,
	Channel,
	Channels,
} from '~/marketing/data-multichannel/types';

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

const statusMap: Record< string, SyncStatusType > = {
	synced: 'synced',
	'sync-in-progress': 'syncing',
};

const convert = ( data: Channel ): InstalledChannel => {
	const issueType = data.errors_count >= 1 ? 'error' : 'none';
	const issueText =
		data.errors_count >= 1
			? sprintf(
					// translators: %d: The number of issues to resolve.
					__( '%d issues to resolve', 'woocommerce' ),
					data.errors_count
			  )
			: __( 'No issues to resolve', 'woocommerce' );

	return {
		slug: data.slug,
		title: data.name,
		description: data.description,
		icon: data.icon,
		isSetupCompleted: data.is_setup_completed,
		setupUrl: data.settings_url,
		manageUrl: data.settings_url,
		syncStatus: statusMap[ data.product_listings_status ],
		issueType,
		issueText,
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
