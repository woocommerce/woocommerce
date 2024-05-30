/**
 * External dependencies
 */
import { useState, useCallback, useRef } from '@wordpress/element';

/**
 * A ref callback that also supports a cleanup function.
 * Remove once this lands somewhere (React 19 maybe?)
 *
 * @link https://github.com/facebook/react/pull/25686
 */
function useCallbackRef< T >( rawCallback: ( node: T | null ) => void ) {
	const cleanupRef = useRef< ( () => void ) | null >( null );
	const callback = useCallback(
		( node ) => {
			if ( cleanupRef.current ) {
				cleanupRef.current();
				cleanupRef.current = null;
			}

			if ( node ) {
				const cleanup = rawCallback( node );
				if ( cleanup !== undefined ) {
					cleanupRef.current = cleanup;
				}
			}
		},
		[ rawCallback ]
	);

	return callback;
}
/**
 * Returns a ref, its dimensions, and its visible viewport dimensions. Useful to know if an element should be sticky or not. This hook only runs when an element changes its intersection or dimensions.
 *
 * @example
 *
 * ```js
 * const App = () => {
 * 	const [ observedRef, observedElement, viewWindow ] = useObservedViewport();
 *
 * 	return (
 * 		<MyElement ref={ observedRef } className={ observedElement.height < viewWindow.height ? 'is-sticky': '' } />
 * 	);
 * };
 * ```
 */
export function useObservedViewport< T extends HTMLElement >(): [
	React.Ref< T >,
	{ height: number; width: number },
	{ height: number; width: number }
] {
	const [ observedElement, setObservedElement ] = useState( {
		height: 0,
		width: 0,
	} );

	const [ viewWindow, setViewWindow ] = useState( {
		height: 0,
		width: 0,
	} );

	const observedRef = useCallbackRef< T >(
		useCallback( ( node: T | null ) => {
			if ( ! node ) return;

			const resizeObserver = new ResizeObserver( ( entries ) => {
				entries.forEach( ( entry ) => {
					if ( entry.target === node ) {
						const { height, width } = entry.contentRect;
						let elementTop = 0;
						if ( node.computedStyleMap() ) {
							elementTop =
								parseInt(
									node
										.computedStyleMap()
										.get( 'top' )
										?.toString() || '0', // This is needed because `top` can be a number, auto, or undefined.
									10
								) || 0;
						}
						setObservedElement( {
							height: height + elementTop,
							width,
						} );
					}
				} );
			} );

			const intersectionObserver = new IntersectionObserver(
				( entries ) => {
					entries.forEach( ( entry ) => {
						const { height, width } = entry.boundingClientRect;
						setObservedElement( { height, width } );
						if ( entry.target.ownerDocument.defaultView ) {
							setViewWindow( {
								height: entry.target.ownerDocument.defaultView
									.innerHeight,
								width: entry.target.ownerDocument.defaultView
									.innerWidth,
							} );
						}
					} );
				},
				{
					root: null,
					rootMargin: '0px',
					threshold: 1,
				}
			);

			resizeObserver.observe( node );
			intersectionObserver.observe( node );

			return () => {
				resizeObserver.unobserve( node );
				intersectionObserver.unobserve( node );
			};
		}, [] )
	);

	return [ observedRef, observedElement, viewWindow ];
}
