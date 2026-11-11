/**
 * External dependencies
 */
import { useLayoutEffect, useRef, useState } from '@wordpress/element';
import { whenAnimationsEnd } from '@woocommerce/base-utils';

/**
 * Keeps content mounted while it animates out, such as a dropdown that closes.
 *
 * Pass the content to show, or `null` to remove it. When it becomes `null`, the hook keeps returning the last content
 * with `isExiting` set, until the animations of `ref` end. Style the exit on `isExiting`. If new content arrives first,
 * `isExiting` is unset again, so a transition turns around from its current position. Nothing is kept when nothing
 * animates, such as in Jest.
 *
 * @param value        The content to show, or `null` when there is none.
 * @param options
 * @param options.exit Set to `false` to remove the content immediately, for example after a key press.
 * @return The content to render, whether it is exiting, and the ref for the element that animates.
 */
export function usePresence< T, E extends Element = HTMLDivElement >(
	value: T | null,
	{ exit = true }: { exit?: boolean } = {}
) {
	const ref = useRef< E | null >( null );
	const [ previousValue, setPreviousValue ] = useState( value );
	const [ exitingValue, setExitingValue ] = useState< T | null >( null );

	// Adjust state while rendering, so the exiting content never unmounts for a frame.
	if ( value !== previousValue ) {
		setPreviousValue( value );
		setExitingValue( value === null && exit ? previousValue : null );
	}

	// A layout effect runs after the exit style is in the DOM, so its transitions have started, and before paint, so
	// content that does not animate is removed without a visible frame.
	useLayoutEffect( () => {
		if ( exitingValue === null ) {
			return;
		}
		return whenAnimationsEnd( ref.current, () => setExitingValue( null ) );
	}, [ exitingValue ] );

	return {
		value: value ?? exitingValue,
		isExiting: value === null && exitingValue !== null,
		ref,
	};
}
