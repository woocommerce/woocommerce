/**
 * External dependencies
 */
import { useEffect } from 'react';

export function BlockInspector() {
	useEffect( () => {
		function handleFocus( event: FocusEvent ) {
			const target = event.target;

			if ( ! ( target instanceof Element ) ) {
				return;
			}

			const blockElement = target.closest( '[data-block]' );

			if ( blockElement ) {
				console.log( blockElement );
				//showInspectorPanel( blockElement );
			} else {
				console.log( 'no block element' );
				//hideInspectorPanel();
			}
		}

		document.addEventListener( 'focus', handleFocus, {
			capture: true,
		} );

		return () => {
			document.removeEventListener( 'focus', handleFocus, {
				capture: true,
			} );
		};
	}, [] );

	return null;
}
