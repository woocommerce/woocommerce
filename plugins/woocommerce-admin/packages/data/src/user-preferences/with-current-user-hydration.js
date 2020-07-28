/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';

/**
 * Higher-order component used to hydrate current user data.
 *
 * @param {Object} currentUser Current user object in the same format as the WP REST API returns.
 */
export const withCurrentUserHydration = ( currentUser ) => (
	OriginalComponent
) => {
	return ( props ) => {
		const userRef = useRef( currentUser );

		// Use currentUser to hydrate calls to @wordpress/core-data's getCurrentUser().
		useSelect( ( select, registry ) => {
			if ( ! userRef.current ) {
				return;
			}

			const { isResolving, hasFinishedResolution } = select( STORE_NAME );
			const {
				startResolution,
				finishResolution,
				receiveCurrentUser,
			} = registry.dispatch( STORE_NAME );

			if (
				! isResolving( 'getCurrentUser' ) &&
				! hasFinishedResolution( 'getCurrentUser' )
			) {
				startResolution( 'getCurrentUser', [] );
				receiveCurrentUser( userRef.current );
				finishResolution( 'getCurrentUser', [] );
			}
		} );

		return <OriginalComponent { ...props } />;
	};
};
