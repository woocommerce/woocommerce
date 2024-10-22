/**
 * External dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';

function isAtBottom() {
	return window.innerHeight + window.scrollY >= document.body.scrollHeight;
}

export default function useIsScrolled() {
	const [ isScrolled, setIsScrolled ] = useState( false );
	const [ atBottom, setAtBottom ] = useState( isAtBottom() );
	const rafHandle = useRef( null );
	useEffect( () => {
		const updateIsScrolled = () => {
			setIsScrolled( window.pageYOffset > 20 );
			setAtBottom( isAtBottom() );
		};

		const scrollListener = () => {
			rafHandle.current =
				window.requestAnimationFrame( updateIsScrolled );
		};

		window.addEventListener( 'scroll', scrollListener );

		window.addEventListener( 'resize', scrollListener );

		return () => {
			window.removeEventListener( 'scroll', scrollListener );
			window.removeEventListener( 'resize', scrollListener );
			window.cancelAnimationFrame( rafHandle.current );
		};
	}, [] );

	return {
		isScrolled,
		atBottom,
		atTop: ! isScrolled,
	};
}
