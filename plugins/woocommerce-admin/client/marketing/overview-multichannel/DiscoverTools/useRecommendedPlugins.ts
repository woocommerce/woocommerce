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

const selector = 'getRecommendedPlugins';
const category = 'marketing';

type SelectResult = {
	isLoading: boolean;
	plugins: Plugin[];
	invalidateResolution: () => void;
};

export const useRecommendedPlugins = () => {
	const { invalidateResolution } = useDispatch( STORE_KEY );

	const callback = useCallback( () => {
		invalidateResolution( selector, [ category ] );
	}, [ invalidateResolution ] );

	return useSelect< SelectResult >(
		( select ) => {
			const { getRecommendedPlugins, isResolving } = select( STORE_KEY );

			return {
				isLoading: isResolving( selector, [ category ] ),
				plugins: getRecommendedPlugins( category ),
				invalidateResolution: callback,
			};
		},
		[ category ]
	);
};
