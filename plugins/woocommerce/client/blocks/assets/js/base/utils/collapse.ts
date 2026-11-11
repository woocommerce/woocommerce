// Motion that changes the layout, so it runs in script: it measures sizes, which CSS can't animate to or from. Each
// helper does nothing where the browser can't animate, and under reduced motion only fades or applies at once.

// The space a box takes up in the flow, animated together so what follows moves with it, borders included.
const BOX = [
	'height',
	'paddingTop',
	'paddingBottom',
	'borderTopWidth',
	'borderBottomWidth',
	'marginTop',
	'marginBottom',
] as const;
// `--wc-collapse-scale` scales spacing set with `!important`, which animations can't override (see `style.scss`).
type Box = Record< ( typeof BOX )[ number ] | '--wc-collapse-scale', string >;
const EMPTY_BOX = {
	...Object.fromEntries( BOX.map( ( property ) => [ property, '0px' ] ) ),
	'--wc-collapse-scale': '0',
} as Box;

// Shared by every script bundle that includes this, through the global object, so one bundle's animation on an element (such
// as a totals row growing in) is seen and interrupted by another's (such as the row's height easing).
const running: WeakMap< HTMLElement, Animation[] > = ( (
	globalThis as typeof globalThis & {
		wcCollapseAnimations?: WeakMap< HTMLElement, Animation[] >;
	}
 ).wcCollapseAnimations ??= new WeakMap() );

/**
 * Timings come from CSS custom properties set on the root in `style.scss`, which hold the WPDS motion tokens and
 * their fallbacks, so the tokens stay the one source for durations and easing.
 */
export const timing = ( direction: 'in' | 'out' ) => {
	const style = getComputedStyle( document.documentElement );
	const read = ( name: string ) => style.getPropertyValue( name ).trim();
	const toMs = ( value: string, fallback: number ) => {
		const number = parseFloat( value );
		if ( Number.isNaN( number ) ) {
			return fallback;
		}
		return value.endsWith( 'ms' ) ? number : number * 1000;
	};
	return {
		move: {
			duration:
				direction === 'in'
					? toMs( read( '--wc-motion-duration-move-in' ), 300 )
					: toMs( read( '--wc-motion-duration-move-out' ), 200 ),
			easing: read( '--wc-motion-easing-move' ) || 'ease-in-out',
		},
		fade: {
			duration: toMs( read( '--wc-motion-duration-fade' ), 200 ),
			easing: read( '--wc-motion-easing-fade' ) || 'ease-out',
		},
		// A quick fade, for old content making way for new, and the easing for a slide into place.
		quick: toMs( read( '--wc-motion-duration-quick' ), 100 ),
		slideEasing: read( '--wc-motion-easing-slide' ) || 'ease-out',
	};
};

export const prefersReducedMotion = () =>
	window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

const canAnimate = ( element: Element | null ): element is HTMLElement =>
	element instanceof HTMLElement && typeof element.animate === 'function';

const readBox = ( element: HTMLElement ): Box => {
	const style = getComputedStyle( element );
	return {
		...( Object.fromEntries(
			BOX.map( ( property ) => [ property, style[ property ] ] )
		) as Box ),
		height: `${ element.getBoundingClientRect().height }px`,
		'--wc-collapse-scale':
			style.getPropertyValue( '--wc-collapse-scale' ).trim() || '1',
	};
};

// Stops the animations on `element`, after reading where any still in progress had got to. Finished ones only
// leave their end state, such as a collapsed box, which a new animation starts from anyway; cancelled ones none.
const interrupt = ( element: HTMLElement ) => {
	const animations = running.get( element ) ?? [];
	const inProgress = animations.some(
		( animation ) =>
			animation.playState === 'running' ||
			animation.playState === 'paused'
	);
	const box = inProgress ? readBox( element ) : null;
	const opacity = inProgress ? getComputedStyle( element ).opacity : null;
	animations.forEach( ( animation ) => animation.cancel() );
	running.delete( element );
	return { box, opacity };
};

const clip = ( element: HTMLElement ) => {
	element.style.boxSizing = 'border-box';
	element.style.overflow = 'clip';
	element.style.overflowClipMargin = '4px';
};
const unclip = ( element: HTMLElement ) => {
	element.style.removeProperty( 'box-sizing' );
	element.style.removeProperty( 'overflow' );
	element.style.removeProperty( 'overflow-clip-margin' );
};

/** Whether one of these helpers is animating `element` now. */
export const isAnimating = ( element: Element ) =>
	( running.get( element as HTMLElement ) ?? [] ).some(
		( animation ) => animation.playState === 'running'
	);

const track = ( element: HTMLElement, animations: Animation[] ) => {
	running.set( element, animations );
	return animations;
};

/**
 * While `element` enters as a whole, content inside it doesn't play its own entrance, such as option rows staggering
 * in: `motion-stagger` turns those off under `attribute`. Returns a function that ends this without the entrances
 * then starting late.
 */
export const quietInnerEntrances = (
	element: HTMLElement,
	attribute = 'data-wc-entering'
) => {
	element.setAttribute( attribute, '' );
	return () => {
		element.removeAttribute( attribute );
		// Removing the attribute starts the entrances it held back, so they skip to their end.
		element
			.getAnimations( { subtree: true } )
			.filter(
				( animation ) =>
					animation instanceof CSSAnimation && animation.pending
			)
			.forEach( ( animation ) => animation.finish() );
	};
};

/**
 * Grows `element` from nothing to its natural size while it fades in, so the content after it moves down with it.
 * Interrupted part way (by `collapseOut`), it turns around from where it is. Under reduced motion it only fades.
 * Content inside doesn't play its own entrance meanwhile (see `quietInnerEntrances()`).
 */
export const collapseIn = ( element: Element | null ) => {
	if ( ! canAnimate( element ) ) {
		return [];
	}
	const from = interrupt( element );
	const { move, fade } = timing( 'in' );
	const endQuiet = quietInnerEntrances( element );
	const fadeIn = element.animate(
		[ { opacity: from.opacity ?? 0 }, { opacity: 1 } ],
		fade
	);
	if ( prefersReducedMotion() ) {
		fadeIn.finished.then( endQuiet, endQuiet );
		return track( element, [ fadeIn ] );
	}
	clip( element );
	const grow = element.animate(
		[ from.box ?? EMPTY_BOX, readBox( element ) ],
		move
	);
	grow.finished.then( () => {
		unclip( element );
		endQuiet();
	}, endQuiet );
	return track( element, [ fadeIn, grow ] );
};

/**
 * Shrinks `element` to nothing while it fades out, so the content after it moves up into its space. It stays
 * collapsed until it's removed. Under reduced motion it only fades.
 */
export const collapseOut = ( element: Element | null ) => {
	if ( ! canAnimate( element ) ) {
		return [];
	}
	const from = interrupt( element );
	const { move, fade } = timing( 'out' );
	const fadeOut = element.animate(
		[ { opacity: from.opacity ?? 1 }, { opacity: 0 } ],
		{ ...fade, fill: 'forwards' }
	);
	if ( prefersReducedMotion() ) {
		return track( element, [ fadeOut ] );
	}
	const box = from.box ?? readBox( element );
	clip( element );
	const shrink = element.animate( [ box, EMPTY_BOX ], {
		...move,
		fill: 'forwards',
	} );
	return track( element, [ fadeOut, shrink ] );
};

/**
 * Clears what `collapseOut` left on `element`, which otherwise stays collapsed, for an element that's kept once
 * its content has gone, such as the mobile order summary's container.
 */
export const resetCollapse = ( element: Element | null ) => {
	if ( element instanceof HTMLElement ) {
		interrupt( element );
		unclip( element );
	}
};

/** Fades `element` out and keeps it hidden, for a copy of removed content that stays where it was as it goes. */
export const fadeAway = ( element: Element | null ) =>
	canAnimate( element )
		? element.animate( [ { opacity: 1 }, { opacity: 0 } ], {
				...timing( 'out' ).fade,
				fill: 'forwards',
			} )
		: null;

/**
 * A copy of `element` to show in its place as it goes: hidden from assistive tech, not interactive, without ids or
 * field names, and with blank iframes, since a copied iframe would load its page again, such as a payment form.
 */
export const inertCopy = ( element: Element ) => {
	const copy = element.cloneNode( true ) as HTMLElement;
	[
		copy,
		...Array.from( copy.querySelectorAll( '[id], [name], [for]' ) ),
	].forEach( ( node ) => {
		node.removeAttribute( 'id' );
		node.removeAttribute( 'name' );
		node.removeAttribute( 'for' );
	} );
	copy.inert = true;
	copy.setAttribute( 'aria-hidden', 'true' );
	copy.querySelectorAll( 'iframe' ).forEach( ( frame ) => {
		frame.removeAttribute( 'src' );
		frame.removeAttribute( 'srcdoc' );
	} );
	copy.style.pointerEvents = 'none';
	return copy;
};

/** Where an element was in the page, recorded while it was there. */
export type Place = {
	element: Element;
	parent: Node;
	next: Node | null;
	previous: Node | null;
};

export const getPlace = ( element: Element | null ): Place | null =>
	element?.parentNode
		? {
				element,
				parent: element.parentNode,
				next: element.nextSibling,
				previous: element.previousSibling,
			}
		: null;

/**
 * For content React has removed: an inert copy goes back where it was and collapses, then goes. The real content
 * unmounts at once, so a form that leaves doesn't keep its validation or payment handlers while it animates.
 */
export const collapseCopyOut = ( {
	element,
	parent,
	next,
	previous,
}: Place ) => {
	if ( ! canAnimate( element ) || prefersReducedMotion() ) {
		return;
	}
	let before: Node | null | undefined;
	if ( next?.parentNode === parent ) {
		before = next;
	} else if ( previous?.parentNode === parent ) {
		before = previous.nextSibling;
	} else if ( ! next ) {
		before = null;
	}
	if ( before === undefined || ! ( parent as Element ).isConnected ) {
		return;
	}
	const copy = inertCopy( element );
	parent.insertBefore( copy, before );
	const animations = collapseOut( copy );
	void Promise.allSettled(
		animations.map( ( animation ) => animation.finished )
	).then( () => copy.remove() );
};

/**
 * Animates `element`'s height from `fromHeight` (its border-box height before a change) to its height now, so
 * content after it moves smoothly when what's inside it changes. Interrupted, it continues from where it is.
 */
export const animateHeightChange = (
	element: Element | null,
	fromHeight: number
) => {
	if ( ! canAnimate( element ) || prefersReducedMotion() ) {
		return;
	}
	const from = interrupt( element ).box?.height ?? `${ fromHeight }px`;
	const to = element.getBoundingClientRect().height;
	if ( Math.abs( parseFloat( from ) - to ) < 1 ) {
		return;
	}
	const { move } = timing( parseFloat( from ) < to ? 'in' : 'out' );
	clip( element );
	const resize = element.animate(
		[ { height: from }, { height: `${ to }px` } ],
		move
	);
	resize.finished.then(
		() => unclip( element ),
		() => undefined
	);
	track( element, [ resize ] );
};

/**
 * Slides `element` from `offset` pixels away (block axis) to where it is now, for content that moved because
 * something before it went and can't change size itself, such as a table row.
 */
export const slideFrom = ( element: Element | null, offset: number ) => {
	if (
		! canAnimate( element ) ||
		prefersReducedMotion() ||
		Math.abs( offset ) < 1
	) {
		return;
	}
	const { move } = timing( 'in' );
	element.animate(
		[ { transform: `translateY(${ offset }px)` }, { transform: 'none' } ],
		move
	);
};
