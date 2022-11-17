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
	const { invalidateResolution } = useDispatch( STORE_KEY );

	const refetch = () => {
		invalidateResolution( selector, [ category ] );
	};

	return useSelect( ( select ) => {
		const { getRecommendedPlugins, hasFinishedResolution } =
			select( STORE_KEY );
		const plugins = getRecommendedPlugins< Plugin[] >( category );

		return {
			isLoading:
				! hasFinishedResolution( selector, [ category ] ) &&
				! plugins.length,
			plugins,
			refetch,
		};
	}, [] );
};
