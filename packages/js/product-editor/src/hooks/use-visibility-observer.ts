/**
 * External dependencies
 */
import { RefObject } from 'react';
import { useEffect, useState, useRef } from '@wordpress/element';

export function useVisibilityObserver(
	elementRef: RefObject< HTMLInputElement >,
	options = { threshold: 0.1 }
) {
	const [ isVisible, setIsVisible ] = useState( false );
	const observer = useRef< IntersectionObserver | null >( null );

	const handleObserver = ( entries: IntersectionObserverEntry[] ) => {
		entries.forEach( ( entry ) => {
			setIsVisible( entry.isIntersecting );
		} );
	};

	useEffect( () => {
		const currentElement = elementRef.current;
		if ( ! currentElement || observer.current ) return;

		const obs = new IntersectionObserver( handleObserver, options );
		obs.observe( currentElement );
		observer.current = obs;

		return () => {
			if ( observer.current ) {
				observer.current.unobserve( currentElement );
				observer.current.disconnect();
				observer.current = null;
			}
		};
	}, [] );

	return isVisible;
}
