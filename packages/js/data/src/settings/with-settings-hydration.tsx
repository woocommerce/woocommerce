/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement, useRef, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { store } from './';
import { Settings } from './types';

export const withSettingsHydration = ( group: string, settings: Settings ) =>
	createHigherOrderComponent<
		React.ComponentType< Record< string, unknown > >,
		React.ComponentType< Record< string, unknown > >
	>(
		( OriginalComponent ) => ( props ) => {
			const settingsRef = useRef( settings );

			const {
				startResolution,
				finishResolution,
				updateSettingsForGroup,
				clearIsDirty,
			} = useDispatch( store );
			const { isResolvingGroup, hasFinishedResolutionGroup } = useSelect(
				( select ) => {
					const { isResolving, hasFinishedResolution } =
						select( store );
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
					void startResolution( 'getSettings', [ group ] );
					void updateSettingsForGroup( group, settingsRef.current );
					void clearIsDirty( group );
					void finishResolution( 'getSettings', [ group ] );
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
