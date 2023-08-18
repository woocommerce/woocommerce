/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { PluginSelectors, PLUGINS_STORE_NAME } from '@woocommerce/data';

export const useIsPluginInstalledNotActivated = () => {
	const { getPluginInstallState } = useSelect( ( select ) => {
		return {
			getPluginInstallState: (
				select( PLUGINS_STORE_NAME ) as PluginSelectors
			 ).getPluginInstallState,
		};
	}, [] );

	const isPluginInstalledNotActivated = useCallback(
		( slug: string ) => {
			return getPluginInstallState( slug ) === 'installed';
		},
		[ getPluginInstallState ]
	);

	return { isPluginInstalledNotActivated };
};
