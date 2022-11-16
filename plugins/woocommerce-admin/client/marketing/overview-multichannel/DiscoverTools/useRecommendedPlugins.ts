/**
 * External dependencies
 */
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
	removeRecommendedPlugin: ( slug: string, category: string ) => void;
};

export const useRecommendedPlugins = () => {
	const { removeRecommendedPlugin } = useDispatch( STORE_KEY );

	return useSelect< SelectResult >( ( select ) => {
		const { getRecommendedPlugins, hasFinishedResolution } =
			select( STORE_KEY );

		return {
			isLoading: ! hasFinishedResolution( 'getRecommendedPlugins', [
				category,
			] ),
			plugins: getRecommendedPlugins( category ),
			removeRecommendedPlugin,
		};
	}, [] );
};
