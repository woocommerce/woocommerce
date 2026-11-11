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
 * still moves, the motion turns around from its current position. A grown element has no inline styles when the motion
 * ends. A collapsed element stays collapsed until it unmounts. Nothing changes when the browser has no Web Animations
 * API, such as in Jest.
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
	reset( element );
	if ( ! animate ) {
		return;
	}
	const open = readValues( element );

	Object.assign( element.style, current ?? ( isOpen ? CLOSED : open ) );
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
