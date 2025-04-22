/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement, useEffect, useRef } from '@wordpress/element';
import type { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import { store } from './';
import { ProfileItems } from './types';

export const withOnboardingHydration = ( data: {
	profileItems: ProfileItems;
} ) => {
	let hydratedProfileItems = false;

	return createHigherOrderComponent<
		ComponentType< Record< string, unknown > >,
		ComponentType< Record< string, unknown > >
	>(
		( OriginalComponent ) => ( props ) => {
			const onboardingRef = useRef( data );

			const { isResolvingGroup, hasFinishedResolutionGroup } = useSelect(
				( select ) => {
					const { isResolving, hasFinishedResolution } =
						select( store );
					return {
						isResolvingGroup: isResolving( 'getProfileItems', [] ),
						hasFinishedResolutionGroup: hasFinishedResolution(
							'getProfileItems',
							[]
						),
					};
				},
				[]
			);

			const { startResolution, finishResolution, setProfileItems } =
				useDispatch( store );

			useEffect( () => {
				if ( ! onboardingRef.current ) {
					return;
				}

				const { profileItems } = onboardingRef.current;
				if ( ! profileItems ) {
					return;
				}

				if (
					profileItems &&
					! hydratedProfileItems &&
					// Ensure that profile items have finished resolving to prevent race conditions
					! isResolvingGroup &&
					! hasFinishedResolutionGroup
				) {
					startResolution( 'getProfileItems', [] );
					setProfileItems( profileItems, true );
					finishResolution( 'getProfileItems', [] );

					hydratedProfileItems = true;
				}
			}, [
				finishResolution,
				setProfileItems,
				startResolution,
				isResolvingGroup,
				hasFinishedResolutionGroup,
			] );

			return <OriginalComponent { ...props } />;
		},
		'withOnboardingHydration'
	);
};
