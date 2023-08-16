/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement, useRef, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { WPDataSelectors } from '../types';
import { STORE_NAME } from './constants';
import { Settings } from './types';

export const withSettingsHydration = ( group: string, settings: Settings ) =>
	createHigherOrderComponent(
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
					const {
						isResolving,
						hasFinishedResolution,
					}: WPDataSelectors = select( STORE_NAME );
					return {
						isResolvingGroup: isResolving( 'getSettings', [
							group,
						] ),
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

			return <OriginalComponent { ...props } />;
		},
		'withSettingsHydration'
	);
