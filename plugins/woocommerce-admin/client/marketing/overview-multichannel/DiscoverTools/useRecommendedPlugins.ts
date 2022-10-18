/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/marketing/data/constants';
import { Plugin } from './types';

const category = 'marketing';

type SelectResult = {
	isLoading: boolean;
	plugins: Plugin[];
};

export const useRecommendedPlugins = () => {
	return useSelect< SelectResult >(
		( select ) => {
			const { getRecommendedPlugins, isResolving } = select( STORE_KEY );

			return {
				isLoading: isResolving( 'getRecommendedPlugins', [ category ] ),
				plugins: getRecommendedPlugins( category ),
			};
		},
		[ category ]
	);
};
