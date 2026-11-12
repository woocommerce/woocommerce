/**
 * Internal dependencies
 */
import { animateCollapse, inertCopy } from './animate-collapse';
import { whenAnimationsEnd, whenAllAnimationsEnd } from './when-animations-end';

// The `motion-swap` mixin styles the container, the copies, and the sections by these attributes.
const SWAPPING = 'data-wc-swapping';
const COPY = 'data-wc-swap-copy';
const ENTERING = 'data-wc-swap-in';
const AFTER = 'data-wc-swap-after';

// The motion takes 400ms. After this time, a swap ends even if the browser never reports the end of an animation.
const SAFETY_TIMEOUT = 1000;

type Place = { top: number; left: number; width: number; opacity: string };

/**
 * Where the sections of a container were before a choice swaps some of them. Create it with `captureSwap`.
 */
export type SwapSnapshot = {
	container: HTMLElement;
	places: Map< HTMLElement, Place >;
	// The computed height, which is the current height while the last swap still changes it.
	height: string;
	direction: 'left' | 'right';
};

// Stops the swap that runs in a container. Only one block swaps sections, so the state can be in the module.
const running = new WeakMap< HTMLElement, ( removeCopies: boolean ) => void >();

const sectionsOf = ( container: HTMLElement ) =>
	Array.from( container.children ).filter(
		( child ): child is HTMLElement =>
			child instanceof HTMLElement && ! child.hasAttribute( COPY )
	);

const removeStyle = ( element: HTMLElement, property: string ) => {
	element.style.removeProperty( property );
	if ( element.style.length === 0 ) {
		element.removeAttribute( 'style' );
	}
};

/**
 * Splits the sections of a swap. The group runs from the first section that changes to the last, in the old order and
 * in the new one, and includes the sections between them that stay. The sections after the group stay where they are
 * in the layout, but move with its height.
 *
 * @param before The sections before the change, in order.
 * @param now    The sections after the change, in order.
 * @return The group as it was (`leaving`), the group as it is (`entering`), and the sections after it (`after`).
 */
export const getSwapGroups = < T >( before: T[], now: T[] ) => {
	const stays = ( item: T ) =>
		before.includes( item ) && now.includes( item );
	const hasChange = ( list: T[] ) => list.some( ( item ) => ! stays( item ) );
	const changesBefore = ( item: T ) =>
		hasChange( before.slice( 0, before.indexOf( item ) ) ) ||
		hasChange( now.slice( 0, now.indexOf( item ) ) );
	const changesAfter = ( item: T ) =>
		hasChange( before.slice( before.indexOf( item ) + 1 ) ) ||
		hasChange( now.slice( now.indexOf( item ) + 1 ) );
	const isInGroup = ( item: T ) =>
		! stays( item ) || ( changesBefore( item ) && changesAfter( item ) );
	return {
		leaving: before.filter( isInGroup ),
		entering: now.filter( isInGroup ),
		after: now.filter(
			( item ) =>
				stays( item ) && changesBefore( item ) && ! changesAfter( item )
		),
	};
};

/**
 * Records where the sections of `container` are, just before a choice swaps some of them, such as Ship or Pickup. Call
 * it from the event handler that makes the change, then call `playSwap` before paint, after the sections change.
 *
 * @param container The element whose children are the sections.
 * @param direction The way that the selection moved on the screen: 1 to the right, -1 to the left.
 * @return The snapshot for `playSwap`, or `null` when the browser has no Web Animations API, such as in Jest.
 */
export const captureSwap = (
	container: Element | null,
	direction: number
): SwapSnapshot | null => {
	if (
		! ( container instanceof HTMLElement ) ||
		typeof container.getAnimations !== 'function'
	) {
		return null;
	}
	// Where the sections are on the screen, so a swap that still runs continues from its current position.
	const box = container.getBoundingClientRect();
	const places = new Map(
		sectionsOf( container ).map( ( section ): [ HTMLElement, Place ] => {
			const rect = section.getBoundingClientRect();
			return [
				section,
				{
					top: rect.top - box.top - container.clientTop,
					left: rect.left - box.left - container.clientLeft,
					width: rect.width,
					opacity: getComputedStyle( section ).opacity,
				},
			];
		} )
	);
	return {
		container,
		places,
		height: getComputedStyle( container ).height,
		direction: direction < 0 ? 'left' : 'right',
	};
};

/**
 * Ends a swap that runs in `container` at once, for a change that applies immediately, such as after a key press.
 * Call it from the event handler that makes the change.
 *
 * @param container The element whose children are the sections.
 */
export const stopSwap = ( container: Element | null ) => {
	if ( container instanceof HTMLElement ) {
		running.get( container )?.( true );
	}
};

/**
 * Swaps the group of sections that changed as one movement. Give the container the `motion-swap` mixin.
 *
 * Inert copies of the old sections fade out and move the way the selection moved. The real sections already changed,
 * so a form that goes stops counting at once. The sections after the group hold their old position until the copies
 * have gone, so content that loads meanwhile is in place before anything moves. Then they slide to their new position,
 * the height of the container changes once, and the new sections fade in and slide in from the other side. A new swap
 * continues from the current position. With reduced motion, the sections only fade.
 *
 * @param snapshot The snapshot from `captureSwap`.
 */
export const playSwap = ( snapshot: SwapSnapshot ) => {
	const { container, places, height, direction } = snapshot;
	const { leaving, entering, after } = getSwapGroups(
		Array.from( places.keys() ),
		sectionsOf( container )
	);
	if ( ! leaving.length && ! entering.length ) {
		return;
	}
	running.get( container )?.( false );

	let frame = 0;
	let isStopped = false;
	const cancels: Array< () => void > = [];

	// Each copy shows where its section was, above the content that now takes its place.
	const copies = leaving.map( ( section ) => {
		const { top, left, width, opacity } = places.get( section ) as Place;
		const copy = inertCopy( section );
		copy.setAttribute( COPY, direction );
		Object.assign( copy.style, {
			position: 'absolute',
			boxSizing: 'border-box',
			margin: '0',
			top: `${ top }px`,
			left: `${ left }px`,
			width: `${ width }px`,
			opacity,
		} );
		return copy;
	} );

	// Moves the sections after the group back to where they were, wherever the layout puts them now. Reading the
	// layout also applies the styles that changed before, so entrances that the last swap started can start again.
	const hold = () => {
		after.forEach( ( section ) =>
			section.style.removeProperty( 'translate' )
		);
		const box = container.getBoundingClientRect();
		const shifts = after.map(
			( section ) =>
				( places.get( section )?.top ?? 0 ) -
				( section.getBoundingClientRect().top -
					box.top -
					container.clientTop )
		);
		after.forEach( ( section, index ) => {
			section.style.translate = `0 ${ shifts[ index ] }px`;
		} );
	};
	const keepHolding = () => {
		hold();
		frame = requestAnimationFrame( keepHolding );
	};

	const stop = ( removeCopies: boolean ) => {
		if ( isStopped ) {
			return;
		}
		isStopped = true;
		if ( running.get( container ) === stop ) {
			running.delete( container );
		}
		cancelAnimationFrame( frame );
		cancels.forEach( ( cancel ) => cancel() );
		animateCollapse( container, true, false );
		container.removeAttribute( SWAPPING );
		entering.forEach( ( section ) => section.removeAttribute( ENTERING ) );
		after.forEach( ( section ) => {
			section.removeAttribute( AFTER );
			removeStyle( section, 'translate' );
		} );
		if ( removeCopies ) {
			copies.forEach( ( copy ) => copy.remove() );
			// A slide that still runs would keep moving the sections from where they were, so end it at once.
			after.forEach( ( section ) =>
				section
					.getAnimations()
					.forEach( ( animation ) => animation.cancel() )
			);
		}
	};

	const release = () => {
		cancelAnimationFrame( frame );
		hold();
		// The slide starts from the held position.
		container.getBoundingClientRect();
		after.forEach( ( section ) => {
			section.setAttribute( AFTER, '' );
			removeStyle( section, 'translate' );
		} );
		removeStyle( container, 'height' );
		animateCollapse( container, true, true, { height } );
		cancels.push(
			whenAllAnimationsEnd( [ container, ...entering, ...after ], () =>
				stop( true )
			)
		);
	};

	container.setAttribute( SWAPPING, '' );
	container.style.height = height;
	container.append( ...copies );
	hold();
	entering.forEach( ( section ) =>
		section.setAttribute( ENTERING, direction )
	);
	copies.forEach( ( copy ) =>
		whenAnimationsEnd( copy, () => copy.remove() )
	);
	const safety = window.setTimeout( () => stop( true ), SAFETY_TIMEOUT );
	cancels.push( () => window.clearTimeout( safety ) );
	running.set( container, stop );
	frame = requestAnimationFrame( keepHolding );
	cancels.push( whenAllAnimationsEnd( copies, release ) );
};
