/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
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

				if ( profileItems && ! hydratedProfileItems ) {
					startResolution( 'getProfileItems', [] );
					setProfileItems( profileItems, true );
					finishResolution( 'getProfileItems', [] );

					hydratedProfileItems = true;
				}
			}, [ finishResolution, setProfileItems, startResolution ] );

			return <OriginalComponent { ...props } />;
		},
		'withOnboardingHydration'
	);
};
