/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect, useDispatch } from '@wordpress/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import { WCUser } from './types';

/**
 * Higher-order component used to hydrate current user data.
 *
 * @param {Object} currentUser Current user object in the same format as the WP REST API returns.
 */
export const withCurrentUserHydration = ( currentUser: WCUser ) =>
	createHigherOrderComponent< Record< string, unknown > >(
		( OriginalComponent ) => ( props ) => {
			// Use currentUser to hydrate calls to @wordpress/core-data's getCurrentUser().

			const shouldHydrate = useSelect( ( select ) => {
				if ( ! currentUser ) {
					return;
				}
				// @ts-expect-error both functions are not defined in the wp.data typings
				const { isResolving, hasFinishedResolution } =
					select( STORE_NAME );
				return (
					! isResolving( 'getCurrentUser' ) &&
					! hasFinishedResolution( 'getCurrentUser' )
				);
			} );

			const {
				// @ts-expect-error startResolution is not defined in the wp.data typings
				startResolution,
				// @ts-expect-error finishResolution is not defined in the wp.data typings
				finishResolution,
				receiveCurrentUser,
			} = useDispatch( STORE_NAME );

			if ( shouldHydrate ) {
				startResolution( 'getCurrentUser', [] );
				receiveCurrentUser( currentUser );
				finishResolution( 'getCurrentUser', [] );
			}

			return <OriginalComponent { ...props } />;
		},
		'withCurrentUserHydration'
	);
