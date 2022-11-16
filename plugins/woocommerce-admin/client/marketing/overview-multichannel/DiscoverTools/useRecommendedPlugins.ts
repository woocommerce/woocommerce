/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/marketing/data/constants';
import { Plugin } from './types';

const category = 'marketing';

type SelectResult = {
	isLoading: boolean;
	plugins: Plugin[];
	removeRecommendedPlugin: ( slug: string ) => void;
};

export const useRecommendedPlugins = () => {
	const { removeRecommendedPlugin } = useDispatch( STORE_KEY );

	const callback = useCallback(
		( slug ) => {
			removeRecommendedPlugin( slug, category );
		},
		[ removeRecommendedPlugin ]
	);

	return useSelect< SelectResult >(
		( select ) => {
			const { getRecommendedPlugins, isResolving } = select( STORE_KEY );

			return {
				isLoading: isResolving( 'getRecommendedPlugins', [ category ] ),
				plugins: getRecommendedPlugins( category ),
				removeRecommendedPlugin: callback,
			};
		},
		[ category ]
	);
};
