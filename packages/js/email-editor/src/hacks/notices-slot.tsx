/**
 * External dependencies
 */
import { useEffect, useState, createPortal } from '@wordpress/element';

/**
 * Renders a portal for rendering notices before the visual editor.
 *
 * Currently there is no API to add notices with custom context to the content area.
 */
export function NoticesSlot( { children } ) {
	const [ portalEl ] = useState( document.createElement( 'div' ) );

	// Place element for portal as first child of visual editor
	useEffect( () => {
		const visualEditor = document.getElementsByClassName(
			'editor-visual-editor '
		)[ 0 ];
		if ( visualEditor ) {
			visualEditor.parentNode?.insertBefore( portalEl, visualEditor );
		}
	}, [ portalEl ] );

	return createPortal( <>{ children }</>, portalEl );
}
