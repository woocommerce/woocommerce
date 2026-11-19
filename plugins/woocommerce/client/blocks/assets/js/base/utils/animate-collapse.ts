/**
 * Internal dependencies
 */
import { whenAnimationsEnd } from './when-animations-end';

// The `motion-collapse` mixin transitions the element while it has this attribute. The state is on the element, not
// in the module, because each script bundle has its own copy of this module.
const ATTRIBUTE = 'data-wc-collapse';

// The properties that make the space, and the fade.
const PROPERTIES = [
	'height',
	'paddingTop',
	'paddingBottom',
	'marginTop',
	'marginBottom',
	'opacity',
] as const;

type Values = Record< ( typeof PROPERTIES )[ number ], string >;

const CLOSED: Values = {
	height: '0px',
	paddingTop: '0px',
	paddingBottom: '0px',
	marginTop: '0px',
	marginBottom: '0px',
	opacity: '0',
};

const readValues = ( element: HTMLElement ) => {
	const style = getComputedStyle( element );
	return Object.fromEntries(
		PROPERTIES.map( ( property ) => [ property, style[ property ] ] )
	) as Values;
};

/**
 * Removes inline styles from an element, and the `style` attribute too when no styles are left, so that a motion
 * leaves nothing behind.
 *
 * @param element    The element.
 * @param properties The CSS property names to remove, such as `margin-bottom`.
 */
export const clearInlineStyle = (
	element: HTMLElement,
	...properties: string[]
): void => {
	properties.forEach( ( property ) =>
		element.style.removeProperty( property )
	);
	if ( element.style.length === 0 ) {
		// Chrome writes the cleared declarations back as `style=""` when nothing has read the attribute since.
		element.getAttribute( 'style' );
		element.removeAttribute( 'style' );
	}
};

const reset = ( element: HTMLElement ) => {
	element.removeAttribute( ATTRIBUTE );
	PROPERTIES.forEach( ( property ) => {
		element.style[ property ] = '';
	} );
	clearInlineStyle( element );
};

// Moves the element from `from`, or from its rest position, to open or closed.
const move = ( element: HTMLElement, isOpen: boolean, from: Values | null ) => {
	reset( element );
	const open = readValues( element );
	const start = from ?? ( isOpen ? CLOSED : open );
	// Nothing to grow, such as when the content kept its size during the grow.
	if (
		isOpen &&
		PROPERTIES.every(
			( property ) => start[ property ] === open[ property ]
		)
	) {
		return;
	}

	Object.assign( element.style, start );
	// Apply the start values before the transitions exist, so that the transitions start from them.
	element.getBoundingClientRect();
	element.setAttribute( ATTRIBUTE, isOpen ? 'in' : 'out' );
	Object.assign( element.style, isOpen ? open : CLOSED );

	if ( isOpen ) {
		whenAnimationsEnd( element, () => {
			// A collapse that started later keeps its styles until the element unmounts. If the content changed size
			// during the grow, the grow goes on from there to the new size, instead of jumping.
			if ( element.getAttribute( ATTRIBUTE ) === 'in' ) {
				move( element, true, readValues( element ) );
			}
		} );
	}
};

/**
 * Grows an element open or collapses it shut in the real layout, so the content below moves with it. The
 * element also fades. Give the element the `motion-collapse` mixin, which sets the timing and the
 * reduced motion rules. The element must not have its own inline styles for the properties that change.
 *
 * Call it before paint: after the element mounts to grow it, or when it starts to go to collapse it. If the element
 * still moves, the motion turns around from its current position. If the content changes size while the element
 * grows, the element grows on to the new size. A grown element has no inline styles when the motion ends. A collapsed
 * element stays collapsed until it unmounts. Nothing changes when the browser has no Web Animations API, such as in
 * Jest.
 *
 * @param element The element that grows or collapses.
 * @param isOpen  Whether to grow the element (`true`) or collapse it (`false`).
 * @param animate Set to `false` to stop the motion and show the element at its full size immediately.
 */
export const animateCollapse = (
	element: HTMLElement | null,
	isOpen: boolean,
	animate = true
): void => {
	if ( typeof element?.getAnimations !== 'function' ) {
		return;
	}
	// While the motion runs, the computed style is its current position.
	const current = element.hasAttribute( ATTRIBUTE )
		? readValues( element )
		: null;
	if ( ! animate ) {
		reset( element );
		return;
	}
	move( element, isOpen, current );
};
