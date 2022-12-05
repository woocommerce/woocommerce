/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/marketing/data/constants';
import { Plugin } from './types';

const selector = 'getRecommendedPlugins';
const category = 'marketing';

export const useRecommendedPlugins = () => {
	const { invalidateResolution, installAndActivateRecommendedPlugin } =
		useDispatch( STORE_KEY );

	const installAndActivate = ( plugin: string ) => {
		installAndActivateRecommendedPlugin( plugin, category );
		invalidateResolution( selector, [ category ] );
	};

	return useSelect( ( select ) => {
		const { getRecommendedPlugins, hasFinishedResolution } =
			select( STORE_KEY );
		const plugins = getRecommendedPlugins< Plugin[] >( category );
		const isLoading = ! hasFinishedResolution( selector, [ category ] );

		return {
			isInitializing: ! plugins.length && isLoading,
			isLoading,
			plugins,
			installAndActivate,
		};
	}, [] );
};
