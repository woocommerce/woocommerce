/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { createElement, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';

export const withSettingsHydration = ( group, settings ) =>
	createHigherOrderComponent(
		( OriginalComponent ) => ( props ) => {
			const settingsRef = useRef( settings );

			useSelect( ( select, registry ) => {
				if ( ! settingsRef.current ) {
					return;
				}

				const { isResolving, hasFinishedResolution } = select(
					STORE_NAME
				);
				const {
					startResolution,
					finishResolution,
					updateSettingsForGroup,
					clearIsDirty,
				} = registry.dispatch( STORE_NAME );

				if (
					! isResolving( 'getSettings', [ group ] ) &&
					! hasFinishedResolution( 'getSettings', [ group ] )
				) {
					startResolution( 'getSettings', [ group ] );
					updateSettingsForGroup( group, settingsRef.current );
					clearIsDirty( group );
					finishResolution( 'getSettings', [ group ] );
				}
			}, [] );

			return <OriginalComponent { ...props } />;
		},
		'withSettingsHydration'
	);
