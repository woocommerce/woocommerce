/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

export default function usePreventLeavingPage(
	hasUnsavedChanges: boolean,
	message = ''
) {
	useEffect( () => {
		if ( hasUnsavedChanges ) {
			function onBeforeUnload( event: BeforeUnloadEvent ) {
				event.preventDefault();
				event.returnValue = message;
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
	}, [ hasUnsavedChanges, message ] );
}
