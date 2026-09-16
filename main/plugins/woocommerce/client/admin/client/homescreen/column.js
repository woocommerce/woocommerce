/**
 * External dependencies
 */
import {
	useCallback,
	useState,
	useRef,
	useLayoutEffect,
} from '@wordpress/element';

export const Column = ( { children, shouldStick = false } ) => {
	const [ isContentStuck, setIsContentStuck ] = useState( false );
	const content = useRef( null );
	const initialTop = useRef( null );

	const maybeStickContent = useCallback( () => {
		if ( ! content.current ) {
			return;
		}

		const { bottom, top } = content.current.getBoundingClientRect();

		if ( initialTop.current === null ) {
			initialTop.current = top;
		}

		const shouldBeSticky = bottom < window.innerHeight;

		if ( top === initialTop.current ) {
			setIsContentStuck( shouldBeSticky );
		}
	}, [] );

	useLayoutEffect( () => {
		if ( ! shouldStick ) {
			return;
		}

		maybeStickContent();
		window.addEventListener( 'resize', maybeStickContent );
		window.addEventListener( 'scroll', maybeStickContent );

		return () => {
			window.removeEventListener( 'resize', maybeStickContent );
			window.removeEventListener( 'scroll', maybeStickContent );
		};
	}, [ maybeStickContent, shouldStick ] );

	return (
		<div
			className="woocommerce-homescreen-column"
			ref={ content }
			style={ {
				position: shouldStick && isContentStuck ? 'sticky' : 'static',
			} }
		>
			{ children }
		</div>
	);
};
