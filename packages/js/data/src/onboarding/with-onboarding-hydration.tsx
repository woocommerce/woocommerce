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
import { ProfileItems } from './types';
import { OnboardingSelector } from './';

export const withOnboardingHydration = < ComponentProps, >( data: {
	profileItems: ProfileItems;
} ) => {
	let hydratedProfileItems = false;

	return createHigherOrderComponent<
		Record< string, unknown >,
		ComponentProps
	>(
		( OriginalComponent ) => ( props ) => {
			const onboardingRef = useRef( data );

			useSelect(
				// @ts-expect-error // @ts-expect-error registry is not defined in the wp.data typings
				( select: ( s: string ) => OnboardingSelector, registry ) => {
					if ( ! onboardingRef.current ) {
						return;
					}

					const { isResolving, hasFinishedResolution } =
						select( STORE_NAME );
					const {
						startResolution,
						finishResolution,
						setProfileItems,
					} = registry.dispatch( STORE_NAME );

					const { profileItems } = onboardingRef.current;

					if (
						profileItems &&
						! hydratedProfileItems &&
						! isResolving( 'getProfileItems', [] ) &&
						! hasFinishedResolution( 'getProfileItems', [] )
					) {
						startResolution( 'getProfileItems', [] );
						setProfileItems( profileItems, true );
						finishResolution( 'getProfileItems', [] );

						hydratedProfileItems = true;
					}
				},
				[]
			);

			return <OriginalComponent { ...props } />;
		},
		'withOnboardingHydration'
	);
};
