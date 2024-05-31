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

export function useStickyState< T extends HTMLElement >(): [
	React.Ref< T >,
	isSticky: boolean
] {
	const [ isSticky, setIsSticky ] = useState( false );

	const observedRef = useCallbackRef< T >(
		useCallback( ( node: T | null ) => {
			if ( ! node ) return;

			const intersectionObserver = new IntersectionObserver(
				( entries ) => {
					entries.forEach( ( entry ) => {
						setIsSticky(
							entry.isIntersecting === false &&
								entry.boundingClientRect.top < 0
						);
					} );
				},
				{
					threshold: 0,
				}
			);

			intersectionObserver.observe( node );

			return () => {
				intersectionObserver.unobserve( node );
			};
		}, [] )
	);

	return [ observedRef, isSticky ];
}
