/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';

export const useIsPluginInstalled = () => {
	const { installedPlugins } = useSelect( ( select ) => {
		const { getInstalledPlugins } = select( PLUGINS_STORE_NAME );

		return {
			installedPlugins: getInstalledPlugins(),
		};
	} );

	const isPluginInstalled = useCallback(
		( slug: string ) => {
			return installedPlugins.includes( slug );
		},
		[ installedPlugins ]
	);

	return { isPluginInstalled };
};
