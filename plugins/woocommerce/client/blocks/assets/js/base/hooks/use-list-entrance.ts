/**
 * External dependencies
 */
import {
	useCallback,
	useEffect,
	useLayoutEffect,
	useRef,
} from '@wordpress/element';
import type { RefObject } from 'react';
import { whenAnimationsEnd } from '@woocommerce/base-utils';

/**
 * Lets the rows of a list stagger in once, the first time the list shows. Call the returned function when the rows
 * show, such as from the `onShow` of `DelayedContentWithSkeleton`. It sets `data-wc-list-entering` on the list until
 * the rows' entrances end, so later rows, updates, and moves do not enter again.
 *
 * @param ref The list. Style its rows with `motion-stagger` under `[data-wc-list-entering]`.
 * @return A function that starts the entrance, one time only.
 */
export const useListEntrance = (
	ref: RefObject< HTMLElement | null >
): ( () => void ) => {
	const hasEntered = useRef( false );
	const isPending = useRef( false );
	const cancel = useRef< () => void >();

	const start = useCallback( () => {
		if ( hasEntered.current ) {
			return;
		}
		const list = ref.current;
		if ( ! list ) {
			isPending.current = true;
			return;
		}
		hasEntered.current = true;
		list.setAttribute( 'data-wc-list-entering', '' );
		cancel.current = whenAnimationsEnd(
			list,
			() => list.removeAttribute( 'data-wc-list-entering' ),
			{ subtree: true }
		);
	}, [ ref ] );

	// On mount, a child can call `start` before React attaches the ref of a list that wraps it.
	useLayoutEffect( () => {
		if ( isPending.current ) {
			isPending.current = false;
			start();
		}
	} );

	useEffect( () => () => cancel.current?.(), [] );

	return start;
};
