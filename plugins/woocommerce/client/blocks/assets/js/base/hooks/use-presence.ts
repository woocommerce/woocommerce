/**
 * External dependencies
 */
import { useLayoutEffect, useRef, useState } from '@wordpress/element';
import { onAnimationsFinished } from '@woocommerce/base-utils/on-animations-finished';
import {
	canStartLayoutTransition,
	isLayoutTransitionRunning,
	startLayoutChange,
} from '@woocommerce/base-utils/layout-transition';

type Transition = 'entering' | 'exiting';

/**
 * Keeps content mounted while it animates out. `transition` (and `className`) stays set until the animations and
 * transitions on `ref` finish, which is straight away when nothing animates. Under reduced motion the styles only
 * fade, so it still runs. While exiting, `data` is the last `options.data` seen while open, so the content doesn't
 * empty out as it leaves. `enterOnMount` also enters when mounted open.
 *
 * Style the classes with transitions (`motion-fade-presence`), so a change of direction part way through turns
 * around from where it is.
 *
 * With `layout`, a change runs as a layout transition instead (see `startLayoutChange`): the content keeps its old
 * state for a frame, then changes inside the transition. When a layout transition can't run, it falls back to the
 * classes. A change made inside a running layout transition applies at once, as the transition animates it.
 */
export const usePresence = <
	T extends Element = HTMLDivElement,
	D = undefined,
>(
	isOpen: boolean,
	{
		data,
		enterOnMount = false,
		layout = false,
	}: { data?: D; enterOnMount?: boolean; layout?: boolean } = {}
) => {
	const ref = useRef< T >( null );
	const shownData = useRef( data );
	const latestOpen = useRef( isOpen );
	latestOpen.current = isOpen;
	const [ wasOpen, setWasOpen ] = useState( isOpen );
	// What's on screen: it only differs from `isOpen` while a layout transition waits to apply the change.
	const [ shownOpen, setShownOpen ] = useState( isOpen );
	const [ isLayoutPending, setLayoutPending ] = useState( false );
	const [ transition, setTransition ] = useState< Transition | null >(
		enterOnMount && isOpen ? 'entering' : null
	);

	// Set in the same render as the change, so closing content never unmounts for a frame first.
	if ( isOpen !== wasOpen ) {
		setWasOpen( isOpen );
		if ( isLayoutTransitionRunning() ) {
			setShownOpen( isOpen );
		} else if (
			layout &&
			! transition &&
			canStartLayoutTransition( ref.current )
		) {
			setLayoutPending( true );
		} else {
			setShownOpen( isOpen );
			setTransition( isOpen ? 'entering' : 'exiting' );
		}
	}
	if ( isOpen ) {
		shownData.current = data;
	}

	useLayoutEffect( () => {
		if ( ! isLayoutPending ) {
			return;
		}
		const apply = () => {
			setLayoutPending( false );
			setShownOpen( latestOpen.current );
		};
		startLayoutChange( apply, {
			element: () => ref.current,
			exit: ! latestOpen.current,
		} );
	}, [ isLayoutPending ] );

	useLayoutEffect( () => {
		if ( ! transition ) {
			return;
		}
		return onAnimationsFinished( ref.current, () => setTransition( null ) );
	}, [ transition ] );

	const isExiting = transition === 'exiting';
	return {
		isPresent: shownOpen || isExiting,
		transition,
		className: transition ? ( `is-${ transition }` as const ) : undefined,
		ref,
		data: ( isExiting || ( shownOpen && ! isOpen )
			? shownData.current
			: data ) as D,
	};
};
