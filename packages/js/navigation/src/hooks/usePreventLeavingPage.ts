/**
 * External dependencies
 */
import { useContext, useEffect, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Location,
	UNSAFE_NavigationContext as NavigationContext,
	useLocation,
} from 'react-router-dom';

/**
 * Internal dependencies
 */
import { parseAdminUrl } from '../';

export const usePreventLeavingPage = (
	hasUnsavedChanges: boolean,
	shouldConfirm?: ( path: URL, fromUrl: Location ) => boolean,
	/**
	 * Some browsers ignore this message currently on before unload event.
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/Window/beforeunload_event#compatibility_notes
	 */
	message?: string
) => {
	const confirmMessage = useMemo(
		() =>
			message ??
			__( 'Changes you made may not be saved.', 'woocommerce' ),
		[ message ]
	);
	const { navigator } = useContext( NavigationContext );
	const fromUrl = useLocation();

	// This effect prevent react router from navigate and show
	// a confirmation message. It's a work around to beforeunload
	// because react router does not triggers that event.
	useEffect( () => {
		if ( hasUnsavedChanges ) {
			const push = navigator.push;

			navigator.push = ( ...args: Parameters< typeof push > ) => {
				const toUrl = parseAdminUrl( args[ 0 ] ) as URL;
				if (
					typeof shouldConfirm === 'function' &&
					! shouldConfirm( toUrl, fromUrl )
				) {
					push( ...args );
					return;
				}

				/* eslint-disable-next-line no-alert */
				const result = window.confirm( confirmMessage );
				if ( result !== false ) {
					push( ...args );
				}
			};

			return () => {
				navigator.push = push;
			};
		}
	}, [ navigator, hasUnsavedChanges, confirmMessage ] );

	// This effect listen to the native beforeunload event to show
	// a confirmation message
	useEffect( () => {
		if ( hasUnsavedChanges ) {
			function onBeforeUnload( event: BeforeUnloadEvent ) {
				event.preventDefault();
				return ( event.returnValue = confirmMessage );
			}

			window.addEventListener( 'beforeunload', onBeforeUnload, {
				capture: true,
			} );

			return () => {
				window.removeEventListener( 'beforeunload', onBeforeUnload, {
					capture: true,
				} );
			};
		}
	}, [ hasUnsavedChanges, confirmMessage ] );
};
