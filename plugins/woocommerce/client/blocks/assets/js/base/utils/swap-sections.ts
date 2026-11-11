/**
 * Internal dependencies
 */
import {
	inertCopy,
	prefersReducedMotion,
	quietInnerEntrances,
	timing,
} from './collapse';

/**
 * Where a container's sections were before a choice swaps some of them, such as Ship/Pickup. `direction` is the way
 * the selection moved on screen: 1 for rightwards, -1 for leftwards.
 */
export type SwapSnapshot = {
	container: HTMLElement;
	rects: Map< Element, DOMRect >;
	direction: number;
	// Ends the swap's hold on entrances inside the container, such as option rows staggering in.
	end: () => void;
};

const SWAPPING = 'data-wc-swapping';
// Set while a swap holds the sections after the group in their old places (see `playSwap()`).
const HOLDING = 'data-wc-swap-holding';
// Sections a swap has moved back with an inline `translate`, which a new swap clears.
const held = new WeakSet< Element >();
// The latest swap per container, so an earlier one finishing doesn't clear a later one's state.
const latest = new WeakMap< HTMLElement, SwapSnapshot >();
const played = new WeakSet< SwapSnapshot >();

/** Whether `element` is inside a container whose sections are being swapped, so its own motion should wait. */
export const isInSwap = ( element: Element | null | undefined ) =>
	!! element?.closest?.( `[${ SWAPPING }]` );

/**
 * Whether `element` is inside a swap that's still waiting for its content to settle, so a change in its size is
 * part of the swap rather than something to animate on its own.
 */
export const isSwapHolding = ( element: Element | null | undefined ) =>
	!! element?.closest?.( `[${ HOLDING }]` );

/**
 * Records where `container`'s sections are, just before a change swaps some of them. Call it from the event handler
 * that makes the change, then `playSwap()` once React has applied it. Running motion from an earlier swap stops here,
 * from where it got to.
 */
export const captureSwap = (
	container: Element | null,
	direction: number
): SwapSnapshot | null => {
	if (
		! ( container instanceof HTMLElement ) ||
		typeof container.animate !== 'function'
	) {
		return null;
	}
	const children = Array.from( container.children ).filter(
		( child ) => ! child.hasAttribute( SWAPPING )
	);
	const rects = new Map(
		children.map( ( child ) => [ child, child.getBoundingClientRect() ] )
	);
	container
		.querySelectorAll( `:scope > [${ SWAPPING }]` )
		.forEach( ( copy ) => copy.remove() );
	// A swap still under way stops where it got to: the positions above already include its holds.
	container.removeAttribute( HOLDING );
	children.forEach( ( child ) => {
		if ( held.has( child ) ) {
			( child as HTMLElement ).style.removeProperty( 'translate' );
			held.delete( child );
		}
	} );
	// Only the script animations a swap starts; CSS ones belong to the sections.
	[ container, ...children ].forEach( ( element ) =>
		element
			.getAnimations()
			.filter(
				( animation ) =>
					! ( animation instanceof CSSAnimation ) &&
					! ( animation instanceof CSSTransition )
			)
			.forEach( ( animation ) => animation.cancel() )
	);
	const end = quietInnerEntrances( container, SWAPPING );
	const snapshot = { container, rects, direction, end };
	latest.set( container, snapshot );
	// If the change never arrives, so `playSwap()` isn't called, the container goes back to normal.
	window.setTimeout( () => {
		if (
			latest.get( container ) === snapshot &&
			! played.has( snapshot )
		) {
			latest.delete( container );
			end();
		}
	}, 1000 );
	return snapshot;
};

/**
 * Plays a swap as one movement (motion rules: "One change, one movement"). The sections from the first that changed
 * to the last change together, including any in between that stay, such as a billing address shown in both states:
 * each fades out quickly as a copy where it was, moving a little the way the selection moved, then fades in where it
 * is now, arriving from the other side. Until the copies have gone, the sections after the group hold their old
 * places, so content that arrives in the meantime, such as pickup locations loading, is in place before anything
 * moves; then they slide straight to their new places. Under reduced motion, the sections only fade.
 */
export const playSwap = ( snapshot: SwapSnapshot ) => {
	played.add( snapshot );
	const { container, rects, direction } = snapshot;
	const { move, fade, quick, slideEasing } = timing( 'in' );
	const isReduced = prefersReducedMotion();
	const offset = 24 * direction;
	const containerRect = container.getBoundingClientRect();
	const before = Array.from( rects.keys() );
	const now = Array.from( container.children );
	const animations: Animation[] = [];

	// The range that changed, in the old order and in the new one. A section inside either range is in the group.
	const changedRange = (
		list: Element[],
		isChanged: ( e: Element ) => boolean
	) => {
		const indexes = list.flatMap( ( element, index ) =>
			isChanged( element ) ? [ index ] : []
		);
		return indexes.length
			? [ indexes[ 0 ], indexes[ indexes.length - 1 ] ]
			: [ -1, -1 ];
	};
	const [ firstBefore, lastBefore ] = changedRange(
		before,
		( element ) => element.parentElement !== container
	);
	const [ firstNow, lastNow ] = changedRange(
		now,
		( element ) => ! rects.has( element )
	);
	const isInGroup = ( element: Element ) => {
		const was = before.indexOf( element );
		const is = now.indexOf( element );
		return (
			( was > firstBefore && was < lastBefore ) ||
			( is > firstNow && is < lastNow ) ||
			was === -1 ||
			is === -1
		);
	};
	const group = now.filter( ( section ) => isInGroup( section ) );
	const after = now.filter(
		( section ) =>
			! isInGroup( section ) && now.indexOf( section ) > firstNow
	) as HTMLElement[];

	if ( getComputedStyle( container ).position === 'static' ) {
		container.style.position = 'relative';
	}

	rects.forEach( ( rect, section ) => {
		if ( ! isInGroup( section ) ) {
			return;
		}
		// Absolutely placed against the container, which only resizes from its bottom edge.
		const copy = inertCopy( section );
		copy.setAttribute( SWAPPING, '' );
		Object.assign( copy.style, {
			position: 'absolute',
			margin: '0',
			top: `${ rect.top - containerRect.top }px`,
			left: `${ rect.left - containerRect.left }px`,
			width: `${ rect.width }px`,
		} );
		container.append( copy );
		const leave = copy.animate(
			[
				{ opacity: 1, translate: '0 0' },
				{
					opacity: 0,
					translate: isReduced ? '0 0' : `${ offset }px 0`,
				},
			],
			{ duration: quick, easing: fade.easing, fill: 'forwards' }
		);
		animations.push( leave );
		leave.finished.then(
			() => copy.remove(),
			() => copy.remove()
		);
	} );

	// The group stays hidden until the copies have gone, then fades in.
	group.forEach( ( section ) =>
		animations.push(
			section.animate( [ { opacity: 0 }, { opacity: 1 } ], {
				duration: fade.duration,
				delay: quick,
				easing: fade.easing,
				fill: 'backwards',
			} )
		)
	);

	// Each frame until the copies have gone, the sections after the group are moved back to where they were, wherever
	// the layout now puts them.
	const hold = () =>
		after.forEach( ( section ) => {
			const top = rects.get( section )?.top;
			if ( top === undefined ) {
				return;
			}
			section.style.removeProperty( 'translate' );
			const shift = top - section.getBoundingClientRect().top;
			section.style.translate = `0 ${ shift }px`;
			held.add( section );
		} );
	let frame = 0;
	const keepHolding = () => {
		hold();
		frame = requestAnimationFrame( keepHolding );
	};
	if ( ! isReduced ) {
		container.setAttribute( HOLDING, '' );
		hold();
		frame = requestAnimationFrame( keepHolding );
	}

	const finish = () => {
		void Promise.allSettled(
			animations.map( ( animation ) => animation.finished )
		).then( () => {
			if ( latest.get( container ) !== snapshot ) {
				return;
			}
			snapshot.end();
			container.style.removeProperty( 'position' );
		} );
	};

	if ( isReduced ) {
		finish();
		return;
	}

	window.setTimeout( () => {
		cancelAnimationFrame( frame );
		if ( latest.get( container ) !== snapshot ) {
			return;
		}
		container.removeAttribute( HOLDING );
		after.forEach( ( section ) => {
			const shift = parseFloat(
				section.style.translate.split( ' ' )[ 1 ] ?? '0'
			);
			section.style.removeProperty( 'translate' );
			held.delete( section );
			if ( Math.abs( shift ) >= 1 ) {
				animations.push(
					section.animate(
						[
							{ translate: `0 ${ shift }px` },
							{ translate: '0 0' },
						],
						move
					)
				);
			}
		} );
		group.forEach( ( section ) =>
			animations.push(
				section.animate(
					[ { translate: `${ -offset }px 0` }, { translate: '0 0' } ],
					{ duration: move.duration, easing: slideEasing }
				)
			)
		);
		finish();
	}, quick );
};
