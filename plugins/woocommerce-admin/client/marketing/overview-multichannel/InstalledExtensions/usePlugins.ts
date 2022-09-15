/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/marketing/data/constants';
import { Plugin } from './types';

export type UsePluginsType = {
	installedPlugins: Plugin[];
	activatingPlugins: string[];
	activateInstalledPlugin: ( slug: string ) => void;
};

export const usePlugins = (): UsePluginsType => {
	const { installedPlugins, activatingPlugins } = useSelect( ( select ) => {
		const { getInstalledPlugins, getActivatingPlugins } =
			select( STORE_KEY );

		return {
			installedPlugins: getInstalledPlugins(),
			activatingPlugins: getActivatingPlugins(),
		};
	}, [] );
	const { activateInstalledPlugin } = useDispatch( STORE_KEY );

	return {
		installedPlugins,
		activatingPlugins,
		activateInstalledPlugin,
	};
};
