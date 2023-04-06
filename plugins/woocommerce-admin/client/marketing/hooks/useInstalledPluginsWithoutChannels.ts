/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { chain } from 'lodash';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/marketing/data/constants';
import { InstalledPlugin } from '~/marketing/types';
import { useRecommendedChannels } from './useRecommendedChannels';
import { useRegisteredChannels } from './useRegisteredChannels';

export type UseInstalledPluginsWithoutChannels = {
	data: InstalledPlugin[];
	activatingPlugins: string[];
	activateInstalledPlugin: ( slug: string ) => void;
	loadInstalledPluginsAfterActivation: ( slug: string ) => void;
};

/**
 * Hook to return plugins and methods for "Installed extensions" card.
 * The list of installed plugins does not include registered and recommended marketing channels.
 */
export const useInstalledPluginsWithoutChannels =
	(): UseInstalledPluginsWithoutChannels => {
		const { installedPlugins, activatingPlugins } = useSelect(
			( select ) => {
				const { getInstalledPlugins, getActivatingPlugins } =
					select( STORE_KEY );

				return {
					installedPlugins:
						getInstalledPlugins< InstalledPlugin[] >(),
					activatingPlugins: getActivatingPlugins(),
				};
			},
			[]
		);

		const {
			loading: loadingRegisteredChannels,
			data: dataRegisteredChannels,
		} = useRegisteredChannels();
		const {
			loading: loadingRecommendedChannels,
			data: dataRecommendedChannels,
		} = useRecommendedChannels();

		const { activateInstalledPlugin, loadInstalledPluginsAfterActivation } =
			useDispatch( STORE_KEY );

		const loading = loadingRegisteredChannels || loadingRecommendedChannels;
		const installedPluginsWithoutChannels = chain( installedPlugins )
			.differenceWith(
				dataRegisteredChannels || [],
				( a, b ) => a.slug === b.slug
			)
			.differenceWith(
				dataRecommendedChannels || [],
				( a, b ) => a.slug === b.product
			)
			.value();

		return {
			data: loading ? [] : installedPluginsWithoutChannels,
			activatingPlugins,
			activateInstalledPlugin,
			loadInstalledPluginsAfterActivation,
		};
	};
