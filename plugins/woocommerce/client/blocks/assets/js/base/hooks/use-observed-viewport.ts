/**
 * External dependencies
 */
import { useState, useRef, useEffect } from '@wordpress/element';

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

	const observedRef = useRef< T >( null );

	useEffect( () => {
		if ( ! observedRef.current ) {
			return;
		}
		const element = observedRef.current;
		const resizeObserver = new ResizeObserver( ( entries ) => {
			entries.forEach( ( entry ) => {
				if ( entry.target === element ) {
					let elementTop = '0';

					if ( element.computedStyleMap ) {
						elementTop =
							element
								.computedStyleMap()
								.get( 'top' )
								?.toString() || elementTop;
					} else {
						// Firefox support
						elementTop =
							getComputedStyle( element ).top || elementTop;
					}

					const { height, width } = entry.contentRect;

					setObservedElement( {
						height: height + parseInt( elementTop, 10 ),
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
								?.innerHeight,
							width: entry.target.ownerDocument.defaultView
								?.innerWidth,
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

		resizeObserver.observe( element );
		intersectionObserver.observe( element );

		return () => {
			if ( ! element ) {
				return;
			}

			resizeObserver.unobserve( element );
			intersectionObserver.unobserve( element );
		};
	}, [] );
	return [ observedRef, observedElement, viewWindow ];
}
