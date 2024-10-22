/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';

export const useIsPluginInstalledNotActivated = () => {
	const { getPluginInstallState } = useSelect( ( select ) => {
		return {
			getPluginInstallState:
				select( PLUGINS_STORE_NAME ).getPluginInstallState,
		};
	} );

	const isPluginInstalledNotActivated = useCallback(
		( slug: string ) => {
			return getPluginInstallState( slug ) === 'installed';
		},
		[ getPluginInstallState ]
	);

	return { isPluginInstalledNotActivated };
};
