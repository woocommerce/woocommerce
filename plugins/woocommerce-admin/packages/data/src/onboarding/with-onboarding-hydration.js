/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';

export const withOnboardingHydration = ( data ) => ( OriginalComponent ) => {
	return ( props ) => {
		const onboardingRef = useRef( data );

		useSelect( ( select, registry ) => {
			if ( ! onboardingRef.current ) {
				return;
			}

			const { isResolving, hasFinishedResolution } = select( STORE_NAME );
			const {
				startResolution,
				finishResolution,
				setProfileItems,
			} = registry.dispatch( STORE_NAME );

			if (
				! isResolving( 'getProfileItems', [] ) &&
				! hasFinishedResolution( 'getProfileItems', [] )
			) {
				startResolution( 'getProfileItems', [] );
				setProfileItems( onboardingRef.current, true );
				finishResolution( 'getProfileItems', [] );
			}
		}, [] );

		return <OriginalComponent { ...props } />;
	};
};
