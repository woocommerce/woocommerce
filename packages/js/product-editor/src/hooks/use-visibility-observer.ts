/**
 * External dependencies
 */
import { RefObject } from 'react';
import { useEffect, useState, useRef } from '@wordpress/element';

export function useVisibilityObserver(
	elementOrSelector: RefObject< HTMLInputElement > | string,
	options = { threshold: 0.1 } // 10% of the element is visible.
) {
	const [ isVisible, setIsVisible ] = useState( false );
	const observer = useRef< IntersectionObserver | null >( null );

	useEffect( () => {
		const currentElement =
			typeof elementOrSelector === 'string'
				? document.querySelector< HTMLInputElement >(
						elementOrSelector
				  )
				: elementOrSelector.current;

		if ( ! currentElement || observer.current ) return;

		const handleObserver = ( entries: IntersectionObserverEntry[] ) => {
			entries.forEach( ( entry ) => {
				setIsVisible( entry.isIntersecting );
			} );
		};

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
