/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * the admin menu can have different widths in certain scenarios, like when using calypso
 * so we need to observe it and adjust the header width and position accordingly
 */
export function useAdminSidebarWidth() {
	const [ width, setWidth ] = useState( 0 );
	useEffect( () => {
		const resizeObserver = new ResizeObserver( ( entries ) => {
			setWidth( entries[ 0 ].contentRect.width );
		} );
		const adminMenu = document.getElementById( 'adminmenu' );
		if ( adminMenu ) {
			resizeObserver.observe( adminMenu );
		}
		return () => {
			if ( adminMenu ) {
				resizeObserver.unobserve( adminMenu );
			}
		};
	}, [] );
	return width;
}
