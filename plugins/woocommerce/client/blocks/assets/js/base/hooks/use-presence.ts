/**
 * External dependencies
 */
import { useLayoutEffect, useRef, useState } from '@wordpress/element';
import { onAnimationsFinished } from '@woocommerce/base-utils/on-animations-finished';
import { collapseIn, collapseOut } from '@woocommerce/base-utils/collapse';

type Transition = 'entering' | 'exiting';

/**
 * Keeps content mounted while it animates out. `transition` (and `className`) stays set until the animations and
 * transitions on `ref` finish, which is straight away when nothing animates. While exiting, `data` is the last
 * `options.data` seen while open, so the content doesn't empty out as it leaves. `enterOnMount` also enters when
 * mounted open.
 *
 * With `collapse`, `ref` (or the element inside it that matches `collapse`, when it's a selector) grows in and
 * collapses out, so the content after it moves with it (see `collapseIn`). A change of direction part way through
 * turns around from where it is.
 */
export const usePresence = <
	T extends Element = HTMLDivElement,
	D = undefined,
>(
	isOpen: boolean,
	{
		data,
		enterOnMount = false,
		collapse = false,
	}: { data?: D; enterOnMount?: boolean; collapse?: boolean | string } = {}
) => {
	const ref = useRef< T >( null );
	const shownData = useRef( data );
	const [ wasOpen, setWasOpen ] = useState( isOpen );
	const [ transition, setTransition ] = useState< Transition | null >(
		enterOnMount && isOpen ? 'entering' : null
	);

	// Set in the same render as the change, so closing content never unmounts for a frame first.
	if ( isOpen !== wasOpen ) {
		setWasOpen( isOpen );
		setTransition( isOpen ? 'entering' : 'exiting' );
	}
	if ( isOpen ) {
		shownData.current = data;
	}

	useLayoutEffect( () => {
		if ( ! transition ) {
			return;
		}
		if ( collapse ) {
			const target =
				typeof collapse === 'string'
					? ( ref.current?.querySelector( collapse ) ?? null )
					: ref.current;
			if ( transition === 'entering' ) {
				collapseIn( target );
			} else {
				collapseOut( target );
			}
		}
		return onAnimationsFinished( ref.current, () => setTransition( null ) );
		// `collapse` is fixed for a component, so it's left out of the dependencies.
		// eslint-disable-next-line react-hooks/exhaustive-deps
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
