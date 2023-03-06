/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';

import { useSelect } from '@wordpress/data';
import { createElement, useRef } from '@wordpress/element';
import { SelectFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import { WPDataActions } from '../';
import * as actions from './actions';
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
			const dataRef = useRef( data );

			useSelect(
				// @ts-expect-error registry is not defined in the wp.data typings
				(
					select: (
						key: typeof STORE_NAME
					) => SelectFromMap< typeof selectors > & WPDataSelectors,
					registry: {
						dispatch: (
							store: string
						) => typeof actions & WPDataActions;
					}
				) => {
					if ( ! dataRef.current ) {
						return;
					}

					const { isResolving, hasFinishedResolution } =
						select( STORE_NAME );
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
						updateActivePlugins(
							dataRef.current.activePlugins,
							true
						);
						updateInstalledPlugins(
							dataRef.current.installedPlugins,
							true
						);
						updateIsJetpackConnected(
							dataRef.current.jetpackStatus &&
								dataRef.current.jetpackStatus.isActive
								? true
								: false
						);
						finishResolution( 'getActivePlugins', [] );
						finishResolution( 'getInstalledPlugins', [] );
						finishResolution( 'isJetpackConnected', [] );
					}
				},
				[]
			);

			return <OriginalComponent { ...props } />;
		},
		'withPluginsHydration'
	);
