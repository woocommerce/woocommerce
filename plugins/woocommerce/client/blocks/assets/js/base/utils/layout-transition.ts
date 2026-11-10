/**
 * External dependencies
 */
import { flushSync, useCallback, useState } from '@wordpress/element';

type ViewTransitionDocument = Document & {
	startViewTransition: ( options: {
		update: () => void;
		types: string[];
	} ) => { finished: Promise< void > };
};

export const CHECKOUT_SECTIONS = '.wc-block-checkout__form > *';

// The parts of the Cart and Checkout that move when something above them grows or shrinks: the page columns,
// checkout sections, sidebar rows and totals, notices, and the page footer.
export const LAYOUT_SECTIONS = [
	'.wp-block-woocommerce-cart > *',
	'.wp-block-woocommerce-checkout > *',
	CHECKOUT_SECTIONS,
	'.wc-block-components-sidebar > *',
	'.wc-block-components-sidebar :is(.wp-block-woocommerce-cart-order-summary-block, .wp-block-woocommerce-cart-order-summary-totals-block, .wc-block-components-checkout-order-summary__content, .wp-block-woocommerce-checkout-order-summary-totals-block) > *',
	'.wc-block-components-notices > *',
	'.wp-site-blocks > footer',
].join( ', ' );
export const ADDRESS_FORM_SECTIONS = `${ LAYOUT_SECTIONS }, .wc-block-components-address-form > *`;

const BLOCKS = '.wp-block-woocommerce-cart, .wp-block-woocommerce-checkout';

type Options = {
	// Parts of the page that move; ones that appear or go wipe in or out.
	sections?: string | undefined;
	// Grows into its new state, crossfading.
	morph?: Element | null | undefined;
	// The element that changes, named before and after the update if it's there, so it wipes in or out.
	element?: ( () => Element | null ) | undefined;
	// Something closes or goes, which takes 200ms instead of 300ms when every change in the transition does.
	exit?: boolean | undefined;
};

// Whether the last input was a key press rather than a pointer. Keyboard changes, such as arrowing through radio
// options, are repeated quickly, so they apply at once instead of animating.
let isKeyboardInput = false;
if ( typeof document !== 'undefined' ) {
	document.addEventListener(
		'keydown',
		() => ( isKeyboardInput = true ),
		true
	);
	document.addEventListener(
		'pointerdown',
		() => ( isKeyboardInput = false ),
		true
	);
}
export const isUsingKeyboard = () => isKeyboardInput;

let isRunning = false;
export const isLayoutTransitionRunning = () => isRunning;
let batch: {
	updates: Array< () => void >;
	options: Options[];
} | null = null;

/**
 * Whether a change can run as a layout transition now: supported, motion allowed, a pointer change, inside the
 * Cart or Checkout (`element`, or this page), and no layout transition already running. The page ignores clicks
 * while one runs, and only one runs at a time.
 */
export const canStartLayoutTransition = ( element?: Element | null ) =>
	typeof ViewTransition !== 'undefined' &&
	'types' in ViewTransition.prototype &&
	! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches &&
	! isKeyboardInput &&
	! isRunning &&
	( element
		? !! element.closest( BLOCKS )
		: !! document.querySelector( BLOCKS ) );

const run = () => {
	const { updates, options } = batch ?? { updates: [], options: [] };
	batch = null;
	const doc = document as ViewTransitionDocument;
	const sections = [
		...new Set(
			options.map( ( { sections = LAYOUT_SECTIONS } ) => sections )
		),
	].join( ', ' );

	// Names are set inline, only for the transition, so React's class updates can't drop them.
	const named: HTMLElement[] = [];
	const setName = ( element: Element | null | undefined, name?: string ) => {
		if (
			element instanceof HTMLElement &&
			! element.style.getPropertyValue( 'view-transition-name' ) &&
			element.offsetHeight > 0
		) {
			element.style.setProperty(
				'view-transition-name',
				name ?? `wc-layout-section-${ named.length }`
			);
			named.push( element );
		}
	};
	const nameAll = () => {
		options.forEach( ( { element } ) => setName( element?.() ) );
		doc.querySelectorAll( sections ).forEach( ( section ) =>
			setName( section )
		);
	};

	setName( options.find( ( { morph } ) => morph )?.morph, 'wc-layout-morph' );
	nameAll();

	isRunning = true;
	const transition = doc.startViewTransition( {
		update: () => {
			updates.forEach( ( update ) => flushSync( update ) );
			// What only exists after the update wipes in.
			nameAll();
		},
		types: options.every( ( { exit } ) => exit )
			? [ 'wc-layout', 'wc-layout-exit' ]
			: [ 'wc-layout' ],
	} );

	const cleanup = () => {
		isRunning = false;
		named.forEach( ( element ) =>
			element.style.removeProperty( 'view-transition-name' )
		);
	};
	transition.finished.then( cleanup, cleanup );
};

/**
 * Applies a React state `update` as a view transition: sections slide to their new places, ones that appear or go
 * wipe in or out, and `morph` grows into its new state. Changes made together run as one transition. Where
 * `canStartLayoutTransition()` is false it applies `update` at once. Returns whether it transitioned.
 */
export const startLayoutChange = (
	update: () => void,
	options: Options = {}
): boolean => {
	if ( ! canStartLayoutTransition( options.morph ?? options.element?.() ) ) {
		update();
		return false;
	}
	if ( ! batch ) {
		batch = { updates: [], options: [] };
		queueMicrotask( run );
	}
	batch.updates.push( update );
	batch.options.push( options );
	return true;
};

/**
 * For a control whose change runs as a layout transition, which applies it a frame or two after the click:
 * `withLayoutChange( onChange )` holds the new value in `pending` until then, so render `pending ?? value`.
 */
export const usePendingLayoutChange = < T >( sections?: string ) => {
	const [ pending, setPending ] = useState< T | null >( null );
	const withLayoutChange = useCallback(
		( onChange: ( value: T ) => void ) => ( value: T ) => {
			const apply = () => {
				setPending( null );
				onChange( value );
			};
			if ( startLayoutChange( apply, { sections } ) ) {
				setPending( value );
			}
		},
		[ sections ]
	);
	return [ pending, withLayoutChange ] as const;
};
