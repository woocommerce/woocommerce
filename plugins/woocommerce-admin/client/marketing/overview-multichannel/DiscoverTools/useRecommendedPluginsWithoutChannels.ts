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

type UseRecommendedPluginsWithoutChannels = {
	/**
	 * Boolean indicating whether it is initializing.
	 */
	isInitializing: boolean;

	/**
	 * Boolean indicating whether it is loading.
	 *
	 * This will be true when data is being refetched
	 * after `invalidateResolution` is called in the `installAndActivate` method.
	 */
	isLoading: boolean;

	/**
	 * An array of recommended marketing plugins without marketing channels.
	 */
	data: RecommendedPlugin[];

	/**
	 * Install and activate a plugin.
	 */
	installAndActivate: ( slug: string ) => void;
};

const selector = 'getRecommendedPlugins';
const category = 'marketing';

/**
 * A hook to return a list of recommended plugins without marketing channels,
 * and related methods, to be used with the `DiscoverTools` component.
 */
export const useRecommendedPluginsWithoutChannels =
	(): UseRecommendedPluginsWithoutChannels => {
		const {
			loading: loadingRecommendedPlugins,
			data: dataRecommendedPlugins,
		} = useSelect( ( select ) => {
			const { getRecommendedPlugins, hasFinishedResolution } =
				select( STORE_KEY );

			return {
				loading: ! hasFinishedResolution( selector, [ category ] ),
				data: getRecommendedPlugins< RecommendedPlugin[] >( category ),
			};
		}, [] );

		const {
			loading: loadingRecommendedChannels,
			data: dataRecommendedChannels,
		} = useRecommendedChannels();

		const { invalidateResolution, installAndActivateRecommendedPlugin } =
			useDispatch( STORE_KEY );

		const isInitializing =
			( loadingRecommendedPlugins && ! dataRecommendedPlugins.length ) ||
			( loadingRecommendedChannels && ! dataRecommendedChannels );

		const loading = loadingRecommendedPlugins || loadingRecommendedChannels;

		const recommendedPluginsWithoutChannels = differenceWith(
			dataRecommendedPlugins,
			dataRecommendedChannels || [],
			( a, b ) => a.product === b.product
		);

		const installAndActivate = ( slug: string ) => {
			installAndActivateRecommendedPlugin( slug, category );
			invalidateResolution( selector, [ category ] );
		};

		return {
			isInitializing,
			isLoading: loading,
			data: isInitializing ? [] : recommendedPluginsWithoutChannels,
			installAndActivate,
		};
	};
