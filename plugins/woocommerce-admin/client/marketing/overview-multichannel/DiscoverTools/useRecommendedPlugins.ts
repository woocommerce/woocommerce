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
	const { data: dataRecommendedChannels } = useRecommendedChannels();
	const { invalidateResolution, installAndActivateRecommendedPlugin } =
		useDispatch( STORE_KEY );

	const installAndActivate = ( plugin: string ) => {
		installAndActivateRecommendedPlugin( plugin, category );
		invalidateResolution( selector, [ category ] );
	};

	const { isLoading, plugins } = useSelect( ( select ) => {
		const { getRecommendedPlugins, hasFinishedResolution } =
			select( STORE_KEY );

		return {
			isLoading: ! hasFinishedResolution( selector, [ category ] ),
			plugins: getRecommendedPlugins< RecommendedPlugin[] >( category ),
		};
	}, [] );

	const recommendedPluginsWithoutChannels = differenceWith(
		plugins,
		dataRecommendedChannels || [],
		( a, b ) => a.product === b.product
	);

	return {
		isInitializing: ! recommendedPluginsWithoutChannels.length && isLoading,
		isLoading,
		plugins: recommendedPluginsWithoutChannels,
		installAndActivate,
	};
};
