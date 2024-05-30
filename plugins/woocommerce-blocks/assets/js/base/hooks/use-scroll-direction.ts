/**
 * External dependencies
 */
import { useState, useCallback, useRef, useEffect } from '@wordpress/element';

export function useScrollDirection(): [ scrollDirection: 'up' | 'down' | '' ] {
	const [ scrollDirection, setScrollDirection ] = useState<
		'up' | 'down' | ''
	>( '' );

	const lastScrollTop = useRef( 0 );

	const handleScroll = useCallback( () => {
		const scrollTop =
			window.pageYOffset || document.documentElement.scrollTop;

		if ( scrollTop > lastScrollTop.current ) {
			setScrollDirection( 'down' );
		} else {
			setScrollDirection( 'up' );
		}

		lastScrollTop.current = scrollTop;
	}, [] );

	useEffect( () => {
		window.addEventListener( 'scroll', handleScroll );

		return () => {
			window.removeEventListener( 'scroll', handleScroll );
		};
	}, [ handleScroll ] );

	return [ scrollDirection ];
}
