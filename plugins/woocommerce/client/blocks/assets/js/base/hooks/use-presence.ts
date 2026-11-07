/**
 * External dependencies
 */
import { useLayoutEffect, useRef, useState } from '@wordpress/element';
import { useReducedMotion } from '@wordpress/compose';
import { onAnimationsFinished } from '@woocommerce/base-utils/on-animations-finished';

type Transition = 'entering' | 'exiting' | null;

/**
 * Keeps content mounted while it animates out. `transition` (and `className`) stays set until the animations on
 * `ref` finish; under reduced motion it's never set. While exiting, `data` is the last `options.data` seen while
 * open, so the content doesn't empty out as it leaves. `enterOnMount` also enters when mounted open.
 */
export const usePresence = <
	T extends Element = HTMLDivElement,
	D = undefined,
>(
	isOpen: boolean,
	{ data, enterOnMount = false }: { data?: D; enterOnMount?: boolean } = {}
) => {
	const isReducedMotion = useReducedMotion();
	const ref = useRef< T >( null );
	const shownData = useRef( data );
	const [ wasOpen, setWasOpen ] = useState( isOpen );
	const [ transition, setTransition ] = useState< Transition >(
		enterOnMount && isOpen && ! isReducedMotion ? 'entering' : null
	);

	// Set in the same render as the change, so closing content never unmounts for a frame first.
	if ( isOpen !== wasOpen ) {
		setWasOpen( isOpen );
		if ( ! isReducedMotion ) {
			setTransition( isOpen ? 'entering' : 'exiting' );
		}
	}
	if ( isOpen ) {
		shownData.current = data;
	}

	useLayoutEffect( () => {
		if ( ! transition ) {
			return;
		}
		return onAnimationsFinished( ref.current, () => setTransition( null ) );
	}, [ transition ] );

	const isExiting = transition === 'exiting';
	return {
		isPresent: isOpen || isExiting,
		transition,
		className: transition ? ( `is-${ transition }` as const ) : undefined,
		ref,
		data: ( isExiting ? shownData.current : data ) as D,
	};
};
