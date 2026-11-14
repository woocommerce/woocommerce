/**
 * Internal dependencies
 */
import { whenAnimationsEnd } from './when-animations-end';

// The `motion-collapse` mixin transitions the element while it has this attribute. The state is on the element, not
// in the module, because each script bundle has its own copy of this module.
const ATTRIBUTE = 'data-wc-collapse';

// Marks the copy that `collapseCopy` puts in the place of an element, with the class name of the element.
const COPY = 'data-wc-collapse-copy';

// The properties that make the space, and the fade. Borders count, so a bordered row leaves no line behind.
const PROPERTIES = [
	'height',
	'paddingTop',
	'paddingBottom',
	'marginTop',
	'marginBottom',
	'borderTopWidth',
	'borderBottomWidth',
	'opacity',
] as const;

type Values = Record< ( typeof PROPERTIES )[ number ], string >;

const CLOSED: Values = {
	height: '0px',
	paddingTop: '0px',
	paddingBottom: '0px',
	marginTop: '0px',
	marginBottom: '0px',
	borderTopWidth: '0px',
	borderBottomWidth: '0px',
	opacity: '0',
};

const readValues = ( element: HTMLElement ) => {
	const style = getComputedStyle( element );
	return Object.fromEntries(
		PROPERTIES.map( ( property ) => [ property, style[ property ] ] )
	) as Values;
};

const reset = ( element: HTMLElement ) => {
	element.removeAttribute( ATTRIBUTE );
	PROPERTIES.forEach( ( property ) => {
		element.style[ property ] = '';
	} );
	// Leave nothing behind, not even an empty `style` attribute.
	if ( element.style.length === 0 ) {
		element.removeAttribute( 'style' );
	}
};

/**
 * Grows an element open or collapses it shut in the real layout, so the content below moves with it. The
 * element also fades. Give the element the `motion-collapse` mixin, which sets the timing and the
 * reduced motion rules. The element must not have its own inline styles for the properties that change.
 *
 * Call it before paint: after the element mounts to grow it, or when it starts to go to collapse it. If the element
 * still moves, the motion turns around from its current position. This includes a copy of the element that
 * `collapseCopy` still collapses in its place. A grown element has no inline styles when the motion ends. A collapsed
 * element stays collapsed until it unmounts. Nothing changes when the browser has no Web Animations API, such as in
 * Jest.
 *
 * @param element The element that grows or collapses.
 * @param isOpen  Whether to grow the element (`true`) or collapse it (`false`).
 * @param animate Set to `false` to stop the motion and show the element at its full size immediately.
 * @param from    Grows from these values instead of from nothing, such as the height of the content that the element
 *                replaces. The values that it does not set start open.
 */
export const animateCollapse = (
	element: HTMLElement | null,
	isOpen: boolean,
	animate = true,
	from?: Partial< Values >
): void => {
	if ( typeof element?.getAnimations !== 'function' ) {
		return;
	}
	const copy = isOpen ? element.previousElementSibling : null;
	const hasCopy =
		copy instanceof HTMLElement &&
		copy.getAttribute( COPY ) === element.className;
	// While the motion runs, the computed style is its current position.
	let current: Values | null = null;
	if ( hasCopy ) {
		current = readValues( copy );
		copy.remove();
	} else if ( element.hasAttribute( ATTRIBUTE ) ) {
		current = readValues( element );
	}
	reset( element );
	if ( ! animate ) {
		return;
	}
	const open = readValues( element );
	const closed = from ? { ...open, ...from } : CLOSED;

	Object.assign( element.style, current ?? ( isOpen ? closed : open ) );
	// Apply the start values before the transitions exist, so that the transitions start from them.
	element.getBoundingClientRect();
	element.setAttribute( ATTRIBUTE, isOpen ? 'in' : 'out' );
	Object.assign( element.style, isOpen ? open : CLOSED );

	if ( isOpen ) {
		whenAnimationsEnd( element, () => {
			// A collapse that started later keeps its styles until the element unmounts.
			if ( element.getAttribute( ATTRIBUTE ) === 'in' ) {
				reset( element );
			}
		} );
	}
};

/**
 * Copies an element as inert content that only shows, so that the element itself can unmount at once. The copy has no
 * `id`, `name`, or `for` attributes, and assistive technology skips it. Its selects show the selected options.
 *
 * @param element The element to copy. It can already be out of the page.
 */
export const inertCopy = ( element: HTMLElement ): HTMLElement => {
	const copy = element.cloneNode( true ) as HTMLElement;
	[
		copy,
		...Array.from( copy.querySelectorAll( '[id], [name], [for]' ) ),
	].forEach( ( node ) => {
		node.removeAttribute( 'id' );
		node.removeAttribute( 'name' );
		node.removeAttribute( 'for' );
	} );
	// A clone shows the first option of a select, not the selected one.
	const selects = element.querySelectorAll( 'select' );
	copy.querySelectorAll( 'select' ).forEach( ( select, index ) => {
		select.selectedIndex = selects[ index ].selectedIndex;
	} );
	copy.setAttribute( 'inert', '' );
	copy.setAttribute( 'aria-hidden', 'true' );
	return copy;
};

/**
 * Collapses an inert copy of an element in its place, so that the element itself can unmount at once. Use it for a
 * form that goes, so that its fields stop counting immediately. The copy has no `id`, `name`, or `for` attributes, and
 * it is removed when the collapse ends. Call it just before the element unmounts, while it is still in the page.
 *
 * @param element The element that unmounts.
 */
export const collapseCopy = ( element: HTMLElement | null ): void => {
	if ( typeof element?.getAnimations !== 'function' ) {
		return;
	}
	const copy = inertCopy( element );
	copy.setAttribute( COPY, element.className );
	// A motion that runs continues from its current position.
	if ( element.hasAttribute( ATTRIBUTE ) ) {
		Object.assign( copy.style, readValues( element ) );
	}
	element.after( copy );
	animateCollapse( copy, false );
	whenAnimationsEnd( copy, () => copy.remove() );
};
