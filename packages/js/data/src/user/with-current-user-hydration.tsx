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
import { WCUser } from './types';

/**
 * Higher-order component used to hydrate current user data.
 *
 * @param {Object} currentUser Current user object in the same format as the WP REST API returns.
 */
export const withCurrentUserHydration = < ComponentProps, >(
	currentUser: WCUser
) =>
	createHigherOrderComponent< Record< string, unknown >, ComponentProps >(
		( OriginalComponent ) => ( props ) => {
			const userRef = useRef( currentUser );

			// Use currentUser to hydrate calls to @wordpress/core-data's getCurrentUser().
			// @ts-expect-error // @ts-expect-error registry is not defined in the wp.data typings
			useSelect( ( select, registry ) => {
				if ( ! userRef.current ) {
					return;
				}

				const { isResolving, hasFinishedResolution } =
					select( STORE_NAME );
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
		},
		'withCurrentUserHydration'
	);
