/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect, select as wpSelect } from '@wordpress/data';
import { createElement, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import { Settings } from './types';

export const withSettingsHydration = < ComponentProps, >(
	group: string,
	settings: Settings
) =>
	createHigherOrderComponent< Record< string, unknown >, ComponentProps >(
		( OriginalComponent ) => ( props ) => {
			const settingsRef = useRef( settings );

			// @ts-expect-error registry is not defined in the wp.data typings
			useSelect( ( select: typeof wpSelect, registry ) => {
				if ( ! settingsRef.current ) {
					return;
				}

				const { isResolving, hasFinishedResolution } =
					select( STORE_NAME );
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
