/**
 * Internal dependencies
 */
import { whenAnimationsEnd } from './when-animations-end';

// The `motion-collapse` mixin transitions the element while it has this attribute. The state is on the element, not
// in the module, because each script bundle has its own copy of this module.
const ATTRIBUTE = 'data-wc-collapse';

// Marks the copy that `collapseCopy` puts in the place of an element, with the class name of the element.
const COPY = 'data-wc-collapse-copy';

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

// Moves the element from `current`, or from its rest position, to open or closed. A grow from rest starts from `from`.
const move = (
	element: HTMLElement,
	isOpen: boolean,
	current: Values | null,
	from?: Partial< Values >
) => {
	reset( element );
	const open = readValues( element );
	const closed = from ? { ...open, ...from } : CLOSED;
	const start = current ?? ( isOpen ? closed : open );
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
 * still moves, the motion turns around from its current position. This includes a copy of the element that
 * `collapseCopy` still collapses in its place. If the content changes size while the element grows, the element grows
 * on to the new size. A grown element has no inline styles when the motion ends. A collapsed
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
	if ( ! animate ) {
		reset( element );
		return;
	}
	move( element, isOpen, current, from );
};

/**
 * Collapses an inert copy of an element in its place, so that the element itself can unmount at once. Use it for a
 * form that goes, so that its fields stop counting immediately. The copy has no `id`, `name`, or `for` attributes, its
 * fields are not validated, and it is removed when the collapse ends. Call it just before the element unmounts, while
 * it is still in the page.
 *
 * @param element The element that unmounts.
 */
export const collapseCopy = ( element: HTMLElement | null ): void => {
	if ( typeof element?.getAnimations !== 'function' ) {
		return;
	}
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
	// Keeps the fields out of form validation and `:invalid`. Disabled would do it too, but browsers grey it out.
	// Read-only fields are not validated, and checkboxes, radios, and selects only check `required`.
	copy.querySelectorAll( 'input, select, textarea' ).forEach( ( control ) => {
		control.setAttribute( 'readonly', '' );
		control.removeAttribute( 'required' );
	} );
	copy.setAttribute( 'inert', '' );
	copy.setAttribute( 'aria-hidden', 'true' );
	copy.setAttribute( COPY, element.className );
	// A motion that runs continues from its current position.
	if ( element.hasAttribute( ATTRIBUTE ) ) {
		Object.assign( copy.style, readValues( element ) );
	}
	element.after( copy );
	animateCollapse( copy, false );
	whenAnimationsEnd( copy, () => copy.remove() );
};
