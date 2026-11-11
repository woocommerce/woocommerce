/**
 * External dependencies
 */
import { useLayoutEffect, useRef, useState } from '@wordpress/element';
import { animateCollapse, whenAnimationsEnd } from '@woocommerce/base-utils';

/**
 * Keeps content mounted while it animates out, such as a dropdown that closes.
 *
 * Pass the content to show, or `null` to remove it. When it becomes `null`, the hook keeps returning the last content
 * with `isExiting` set, until the animations of `ref` end. Style the exit on `isExiting`. If new content arrives first,
 * `isExiting` is unset again, so a transition turns around from its current position. Nothing is kept when nothing
 * animates, such as in Jest.
 *
 * @param value            The content to show, or `null` when there is none.
 * @param options
 * @param options.animate  Set to `false` to apply the change immediately, for example after a key press.
 * @param options.collapse Set to `true` to grow `ref` when content arrives and collapse it when it goes.
 *                         Content that is there on the first render does not grow.
 * @return The content to render, whether it is exiting, and the ref for the element that animates.
 */
export function usePresence< T, E extends HTMLElement = HTMLDivElement >(
	value: T | null,
	{
		animate = true,
		collapse = false,
	}: { animate?: boolean; collapse?: boolean } = {}
) {
	const ref = useRef< E | null >( null );
	const [ previousValue, setPreviousValue ] = useState( value );
	const [ exitingValue, setExitingValue ] = useState< T | null >( null );
	const isPresent = value !== null;
	const wasPresentRef = useRef( isPresent );

	// Adjust state while rendering, so the exiting content never unmounts for a frame.
	if ( value !== previousValue ) {
		setPreviousValue( value );
		setExitingValue( value === null && animate ? previousValue : null );
	}

	// Grow before paint, so the content never shows at its full size first. This also turns around a collapse.
	useLayoutEffect( () => {
		if ( collapse && isPresent && ! wasPresentRef.current ) {
			animateCollapse( ref.current, true, animate );
		}
		wasPresentRef.current = isPresent;
	}, [ isPresent, collapse, animate ] );

	// A layout effect runs after the exit style is in the DOM, so its transitions have started, and before paint, so
	// content that does not animate is removed without a visible frame.
	useLayoutEffect( () => {
		if ( exitingValue === null ) {
			return;
		}
		if ( collapse ) {
			animateCollapse( ref.current, false );
		}
		return whenAnimationsEnd( ref.current, () => setExitingValue( null ) );
	}, [ exitingValue, collapse ] );

	return {
		value: value ?? exitingValue,
		isExiting: value === null && exitingValue !== null,
		ref,
	};
}
