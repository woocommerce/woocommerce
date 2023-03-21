/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { differenceWith } from 'lodash';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/marketing/data/constants';
import { useRecommendedChannels } from '~/marketing/hooks';
import { RecommendedPlugin } from '~/marketing/types';

const selector = 'getRecommendedPlugins';
const category = 'marketing';

export const useRecommendedPlugins = () => {
	const {
		loading: loadingRecommendedChannels,
		data: dataRecommendedChannels,
	} = useRecommendedChannels();
	const { invalidateResolution, installAndActivateRecommendedPlugin } =
		useDispatch( STORE_KEY );

	const installAndActivate = ( plugin: string ) => {
		installAndActivateRecommendedPlugin( plugin, category );
		invalidateResolution( selector, [ category ] );
	};

	const { loading: loadingRecommendedPlugins, data: dataRecommendedPlugins } =
		useSelect( ( select ) => {
			const { getRecommendedPlugins, hasFinishedResolution } =
				select( STORE_KEY );

			return {
				loading: ! hasFinishedResolution( selector, [ category ] ),
				data: getRecommendedPlugins< RecommendedPlugin[] >( category ),
			};
		}, [] );

	const loading = loadingRecommendedPlugins || loadingRecommendedChannels;

	const recommendedPluginsWithoutChannels = differenceWith(
		dataRecommendedPlugins,
		dataRecommendedChannels || [],
		( a, b ) => a.product === b.product
	);

	return {
		isInitializing: ! recommendedPluginsWithoutChannels.length && loading,
		isLoading: loading,
		plugins: recommendedPluginsWithoutChannels,
		installAndActivate,
	};
};
