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
	CampaignsState,
	Campaign as APICampaign,
	ApiFetchError,
} from '~/marketing/data-multichannel/types';
import { useRegisteredChannels } from '~/marketing/hooks';

type UseCampaignsType = {
	loading: boolean;
	data?: Array< Campaign >;
	error?: ApiFetchError;
};

export const useCampaigns = (): UseCampaignsType => {
	const { data } = useRegisteredChannels();

	return useSelect( ( select ) => {
		const { hasFinishedResolution, getCampaigns } = select( STORE_KEY );
		const campaignsState = getCampaigns< CampaignsState >();

		const convert = ( campaign: APICampaign ): Campaign => {
			const channel = data?.find(
				( el ) => el.slug === campaign.channel
			);

			return {
				id: `${ campaign.channel }|${ campaign.id }`,
				title: campaign.title,
				description: '',
				cost: `${ campaign.cost.currency } ${ campaign.cost.value }`,
				manageUrl: campaign.manage_url,
				icon: channel?.icon || '',
				channelName: channel?.title || '',
				channelSlug: campaign.channel,
			};
		};

		return {
			loading: ! hasFinishedResolution( 'getCampaigns' ),
			data: campaignsState.data?.map( convert ),
			error: campaignsState.error,
		};
	} );
};
