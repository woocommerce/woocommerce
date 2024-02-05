/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { Campaign } from '~/marketing/types';
import { STORE_KEY } from '~/marketing/data-multichannel/constants';
import {
	CampaignsPagination,
	Campaign as APICampaign,
	ApiFetchError,
} from '~/marketing/data-multichannel/types';
import { useRegisteredChannels } from '~/marketing/hooks';

type UseCampaignsType = {
	loading: boolean;
	data?: Array< Campaign >;
	error?: ApiFetchError;
	meta?: {
		total?: number;
	};
};

/**
 * Custom hook to get campaigns.
 *
 * @param page    Page number. Default is `1`.
 * @param perPage Page size, i.e. number of records in one page. Default is `5`.
 */
export const useCampaigns = ( page = 1, perPage = 5 ): UseCampaignsType => {
	const { data: channels } = useRegisteredChannels();

	return useSelect(
		( select ) => {
			const { hasFinishedResolution, getCampaigns } = select( STORE_KEY );
			const { campaignsPage, meta } = getCampaigns< CampaignsPagination >(
				page,
				perPage
			);

			const convert = ( campaign: APICampaign ): Campaign => {
				const channel = channels?.find(
					( el ) => el.slug === campaign.channel
				);

				const cost = campaign.cost
					? `${ campaign.cost.currency } ${ campaign.cost.value }`
					: '-';

				const sales = campaign.sales
					? `${ campaign.sales.currency } ${ campaign.sales.value }`
					: '-';

				return {
					id: `${ campaign.channel }|${ campaign.id }`,
					title: campaign.title,
					description: '',
					cost,
					sales,
					manageUrl: campaign.manage_url,
					icon: channel?.icon || '',
					channelName: channel?.title || '',
					channelSlug: campaign.channel,
				};
			};

			return {
				loading: ! hasFinishedResolution( 'getCampaigns', [
					page,
					perPage,
				] ),
				data: campaignsPage?.data?.map( convert ),
				error: campaignsPage?.error,
				meta,
			};
		},
		[ page, perPage, channels ]
	);
};
