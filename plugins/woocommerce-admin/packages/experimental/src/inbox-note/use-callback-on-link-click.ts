/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';

export function useCallbackOnLinkClick( onClick: ( link: string ) => void ) {
	const onNodeClick = useCallback(
		( event: MouseEvent ): void => {
			const target = event.target as
				| EventTarget
				| HTMLAnchorElement
				| null;
			if ( target && 'href' in target ) {
				const innerLink = target.href;
				if ( innerLink && onClick ) {
					onClick( innerLink );
				}
			}
		},
		[ onClick ]
	);

	return useCallback(
		( node: HTMLElement ) => {
			if ( node ) {
				node.addEventListener( 'click', onNodeClick );
			}
			return () => {
				if ( node ) {
					node.removeEventListener( 'click', onNodeClick );
				}
			};
		},
		[ onNodeClick ]
	);
}
