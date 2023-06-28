/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement, useRef, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import { Settings } from './types';

export const withSettingsHydration = ( group: string, settings: Settings ) =>
	// @ts-expect-error TODO: fix these types.
	createHigherOrderComponent< Record< string, unknown > >(
		( OriginalComponent ) => ( props ) => {
			const settingsRef = useRef( settings );

			const {
				startResolution,
				finishResolution,
				updateSettingsForGroup,
				clearIsDirty,
			} = useDispatch( STORE_NAME );
			const { isResolvingGroup, hasFinishedResolutionGroup } = useSelect(
				( select ) => {
					const { isResolving, hasFinishedResolution } =
						select( STORE_NAME );
					return {
						// @ts-expect-error TODO: fix these types.
						isResolvingGroup: isResolving( 'getSettings', [
							group,
						] ),
						// @ts-expect-error TODO: fix these types.
						hasFinishedResolutionGroup: hasFinishedResolution(
							'getSettings',
							[ group ]
						),
					};
				},
				[]
			);
			useEffect( () => {
				if ( ! settingsRef.current ) {
					return;
				}
				if ( ! isResolvingGroup && ! hasFinishedResolutionGroup ) {
					startResolution( 'getSettings', [ group ] );
					updateSettingsForGroup( group, settingsRef.current );
					clearIsDirty( group );
					finishResolution( 'getSettings', [ group ] );
				}
			}, [
				isResolvingGroup,
				hasFinishedResolutionGroup,
				finishResolution,
				updateSettingsForGroup,
				startResolution,
				clearIsDirty,
			] );

			// @ts-expect-error TODO: fix these types.
			return <OriginalComponent { ...props } />;
		},
		'withSettingsHydration'
	);
