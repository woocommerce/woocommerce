/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/marketing/data-multichannel/constants';
import {
	CampaignTypesState,
	CampaignType as APICampaignType,
	ApiFetchError,
} from '~/marketing/data-multichannel/types';
import { CampaignType } from '~/marketing/types/CampaignType';

type UseNewCampaignTypes = {
	loading: boolean;
	data?: Array< CampaignType >;
	error?: ApiFetchError;
};

const convert = ( campaignType: APICampaignType ): CampaignType => {
	return {
		id: campaignType.id,
		icon: campaignType.icon_url,
		name: campaignType.name,
		description: campaignType.description,
		createUrl: campaignType.create_url,
		channelName: campaignType.channel.name,
		channelSlug: campaignType.channel.slug,
	};
};

export const useNewCampaignTypes = (): UseNewCampaignTypes => {
	return useSelect( ( select ) => {
		const { hasFinishedResolution, getCampaignTypes } = select( STORE_KEY );
		const campaignTypesState = getCampaignTypes< CampaignTypesState >();

		return {
			loading: ! hasFinishedResolution( 'getCampaignTypes' ),
			data: campaignTypesState.data?.map( convert ),
			error: campaignTypesState.error,
		};
	}, [] );
};
