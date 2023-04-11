/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement, useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import { ProfileItems } from './types';

export const withOnboardingHydration = ( data: {
	profileItems: ProfileItems;
} ) => {
	let hydratedProfileItems = false;

	return createHigherOrderComponent< Record< string, unknown > >(
		( OriginalComponent ) => ( props ) => {
			const onboardingRef = useRef( data );

			const { isResolvingGroup, hasFinishedResolutionGroup } = useSelect(
				( select ) => {
					const { isResolving, hasFinishedResolution } =
						select( STORE_NAME );
					return {
						isResolvingGroup: isResolving( 'getProfileItems', [] ),
						hasFinishedResolutionGroup: hasFinishedResolution(
							'getProfileItems',
							[]
						),
					};
				}
			);

			const { startResolution, finishResolution, setProfileItems } =
				useDispatch( STORE_NAME );

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
