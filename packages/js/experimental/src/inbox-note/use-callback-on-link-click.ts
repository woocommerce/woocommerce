/**
 * External dependencies
 */
import { useCallback, useEffect, useRef } from '@wordpress/element';

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

	const nodeRef = useRef< HTMLElement | null >( null );

	useEffect( () => {
		const node = nodeRef.current;
		if ( node ) {
			node.addEventListener( 'click', onNodeClick );
		}
		return () => {
			if ( node ) {
				node.removeEventListener( 'click', onNodeClick );
			}
		};
	}, [ onNodeClick ] );

	return useCallback( ( node: HTMLElement ) => {
		nodeRef.current = node;
	}, [] );
}
