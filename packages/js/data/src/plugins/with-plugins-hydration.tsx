/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';

import { useSelect, useDispatch } from '@wordpress/data';
import { createElement, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';

type PluginHydrationData = {
	installedPlugins: string[];
	activePlugins: string[];
	jetpackStatus?: { isActive: boolean };
};
export const withPluginsHydration = ( data: PluginHydrationData ) =>
	createHigherOrderComponent<
		React.ComponentType< Record< string, unknown > >,
		React.ComponentType< Record< string, unknown > >
	>(
		( OriginalComponent ) => ( props ) => {
			const shouldHydrate = useSelect( ( select ) => {
				if ( ! data ) {
					return;
				}

				const { isResolving, hasFinishedResolution } =
					select( STORE_NAME );
				return (
					! isResolving( 'getActivePlugins', [] ) &&
					! hasFinishedResolution( 'getActivePlugins', [] )
				);
			}, [] );

			const {
				startResolution,
				finishResolution,
				updateActivePlugins,
				updateInstalledPlugins,
				updateIsJetpackConnected,
			} = useDispatch( STORE_NAME );

			useEffect( () => {
				if ( ! shouldHydrate ) {
					return;
				}
				void startResolution( 'getActivePlugins', [] );
				void startResolution( 'getInstalledPlugins', [] );
				void startResolution( 'isJetpackConnected', [] );
				void updateActivePlugins( data.activePlugins, true );
				void updateInstalledPlugins( data.installedPlugins, true );
				void updateIsJetpackConnected(
					data.jetpackStatus && data.jetpackStatus.isActive
						? true
						: false
				);
				void finishResolution( 'getActivePlugins', [] );
				void finishResolution( 'getInstalledPlugins', [] );
				void finishResolution( 'isJetpackConnected', [] );
			}, [ shouldHydrate ] );

			return <OriginalComponent { ...props } />;
		},
		'withPluginsHydration'
	);
