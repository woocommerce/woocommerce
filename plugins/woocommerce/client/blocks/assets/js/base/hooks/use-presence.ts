/**
 * External dependencies
 */
import { useReducedMotion } from '@wordpress/compose';
import { useLayoutEffect, useRef, useState } from '@wordpress/element';
import { onAnimationsFinished } from '@woocommerce/base-utils';

export type PresenceTransition = 'entering' | 'exiting' | null;

/**
 * Keeps content mounted while it animates out. When `isOpen` changes, `transition` is `entering` or
 * `exiting` until the animations on the element in `ref` finish. With reduced motion it stays `null`,
 * so content mounts and unmounts straight away.
 *
 * @param isOpen Whether the content should be shown.
 */
export const usePresence = < T extends Element >( isOpen: boolean ) => {
	const isReducedMotion = useReducedMotion();
	const ref = useRef< T >( null );
	const [ wasOpen, setWasOpen ] = useState( isOpen );
	const [ transition, setTransition ] =
		useState< PresenceTransition >( null );

	// Set in the same render as the change, so closing content never unmounts for a frame first.
	if ( isOpen !== wasOpen ) {
		setWasOpen( isOpen );
		let nextTransition: PresenceTransition = null;
		if ( ! isReducedMotion ) {
			nextTransition = isOpen ? 'entering' : 'exiting';
		}
		setTransition( nextTransition );
	}

	useLayoutEffect( () => {
		if ( ! transition ) {
			return;
		}
		return onAnimationsFinished( ref.current, () => setTransition( null ) );
	}, [ transition ] );

	return { isPresent: isOpen || transition === 'exiting', transition, ref };
};
