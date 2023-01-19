/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/marketing/data-multichannel/constants';
import { RecommendedChannel } from '~/marketing/types';
import { RecommendedChannels } from '~/marketing/data-multichannel/types';

type UseRecommendedChannels = {
	loading: boolean;
	data: Array< RecommendedChannel >;
};

export const useRecommendedChannels = (): UseRecommendedChannels => {
	return useSelect( ( select ) => {
		const { hasFinishedResolution, getRecommendedChannels } =
			select( STORE_KEY );
		const channels = getRecommendedChannels< RecommendedChannels >();

		// TODO: filter recommended channels against installed plugins,
		// using @woocommerce/data/plugins.

		return {
			loading: ! hasFinishedResolution( 'getChannels' ),
			data: channels.data || [],
			error: channels.error,
		};
	} );
};
