/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';

export const withPluginsHydration = ( data ) =>
	createHigherOrderComponent(
		( OriginalComponent ) => ( props ) => {
			const dataRef = useRef( data );

			useSelect( ( select, registry ) => {
				if ( ! dataRef.current ) {
					return;
				}

				const { isResolving, hasFinishedResolution } = select(
					STORE_NAME
				);
				const {
					startResolution,
					finishResolution,
					updateActivePlugins,
					updateInstalledPlugins,
					updateIsJetpackConnected,
				} = registry.dispatch( STORE_NAME );

				if (
					! isResolving( 'getActivePlugins', [] ) &&
					! hasFinishedResolution( 'getActivePlugins', [] )
				) {
					startResolution( 'getActivePlugins', [] );
					startResolution( 'getInstalledPlugins', [] );
					startResolution( 'isJetpackConnected', [] );
					updateActivePlugins( dataRef.current.activePlugins, true );
					updateInstalledPlugins(
						dataRef.current.installedPlugins,
						true
					);
					updateIsJetpackConnected(
						dataRef.current.jetpackStatus &&
							dataRef.current.jetpackStatus.isActive
					);
					finishResolution( 'getActivePlugins', [] );
					finishResolution( 'getInstalledPlugins', [] );
					finishResolution( 'isJetpackConnected', [] );
				}
			}, [] );

			return <OriginalComponent { ...props } />;
		},
		'withPluginsHydration'
	);
