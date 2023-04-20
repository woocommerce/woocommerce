/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';

import { useSelect, useDispatch } from '@wordpress/data';
import { createElement, useEffect } from '@wordpress/element';
import { SelectFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import { WPDataSelectors } from '../types';

type PluginHydrationData = {
	installedPlugins: string[];
	activePlugins: string[];
	jetpackStatus?: { isActive: boolean };
};
export const withPluginsHydration = ( data: PluginHydrationData ) =>
	createHigherOrderComponent< Record< string, unknown > >(
		( OriginalComponent ) => ( props ) => {
			const shouldHydrate = useSelect(
				(
					select: (
						key: typeof STORE_NAME
					) => SelectFromMap< typeof selectors > & WPDataSelectors
				) => {
					if ( ! data ) {
						return;
					}

					const { isResolving, hasFinishedResolution } =
						select( STORE_NAME );
					return (
						! isResolving( 'getActivePlugins', [] ) &&
						! hasFinishedResolution( 'getActivePlugins', [] )
					);
				},
				[]
			);

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
				startResolution( 'getActivePlugins', [] );
				startResolution( 'getInstalledPlugins', [] );
				startResolution( 'isJetpackConnected', [] );
				updateActivePlugins( data.activePlugins, true );
				updateInstalledPlugins( data.installedPlugins, true );
				updateIsJetpackConnected(
					data.jetpackStatus && data.jetpackStatus.isActive
						? true
						: false
				);
				finishResolution( 'getActivePlugins', [] );
				finishResolution( 'getInstalledPlugins', [] );
				finishResolution( 'isJetpackConnected', [] );
			}, [ shouldHydrate ] );

			return <OriginalComponent { ...props } />;
		},
		'withPluginsHydration'
	);
