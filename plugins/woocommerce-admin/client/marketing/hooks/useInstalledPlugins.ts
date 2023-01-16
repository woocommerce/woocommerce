/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/marketing/data/constants';
import { Plugin } from '~/marketing/types';

export type UseInstalledPlugins = {
	installedPlugins: Plugin[];
	activatingPlugins: string[];
	activateInstalledPlugin: ( slug: string ) => void;
	loadInstalledPluginsAfterActivation: ( slug: string ) => void;
};

/**
 * Hook to return plugins and methods for "Installed extensions" card.
 */
export const useInstalledPlugins = (): UseInstalledPlugins => {
	const { installedPlugins, activatingPlugins } = useSelect( ( select ) => {
		const { getInstalledPlugins, getActivatingPlugins } =
			select( STORE_KEY );

		return {
			installedPlugins: getInstalledPlugins(),
			activatingPlugins: getActivatingPlugins(),
		};
	}, [] );
	const { activateInstalledPlugin, loadInstalledPluginsAfterActivation } =
		useDispatch( STORE_KEY );

	return {
		installedPlugins,
		activatingPlugins,
		activateInstalledPlugin,
		loadInstalledPluginsAfterActivation,
	};
};
