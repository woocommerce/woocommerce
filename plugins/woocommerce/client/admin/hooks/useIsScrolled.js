/**
 * External dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';

export default function useIsScrolled() {
	const [ isScrolled, setIsScrolled ] = useState( false );
	const rafHandle = useRef( null );
	useEffect( () => {
		const updateIsScrolled = () => {
			setIsScrolled( window.pageYOffset > 20 );
		};

		const scrollListener = () => {
			rafHandle.current =
				window.requestAnimationFrame( updateIsScrolled );
		};

		window.addEventListener( 'scroll', scrollListener );

		return () => {
			window.removeEventListener( 'scroll', scrollListener );
			window.cancelAnimationFrame( rafHandle.current );
		};
	}, [] );

	return isScrolled;
}
