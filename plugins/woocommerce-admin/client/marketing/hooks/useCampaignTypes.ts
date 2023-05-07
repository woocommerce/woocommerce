/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

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

type UseCampaignTypes = {
	loading: boolean;
	data?: Array< CampaignType >;
	error?: ApiFetchError;
	refetch: () => void;
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

export const useCampaignTypes = (): UseCampaignTypes => {
	const { invalidateResolution } = useDispatch( STORE_KEY );

	const refetch = useCallback( () => {
		invalidateResolution( 'getCampaignTypes', [] );
	}, [ invalidateResolution ] );

	return useSelect< UseCampaignTypes >(
		( select ) => {
			const { hasFinishedResolution, getCampaignTypes } =
				select( STORE_KEY );
			const campaignTypesState = getCampaignTypes< CampaignTypesState >();

			return {
				loading: ! hasFinishedResolution( 'getCampaignTypes', [] ),
				data: campaignTypesState.data?.map( convert ),
				error: campaignTypesState.error,
				refetch,
			};
		},
		[ refetch ]
	);
};
