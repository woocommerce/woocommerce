/**
 * Internal dependencies
 */
import './layout-transition.scss';

type ViewTransitionDocument = Document & {
	startViewTransition?: ( options: {
		update: () => void;
		types: string[];
	} ) => { finished: Promise< void > };
};

// Every section of the checkout form, so the sections below a change slide to their new positions.
const SECTIONS = '.wc-block-checkout__form > *';

/**
 * Runs `update` inside a same-document view transition, so the checkout form changes layout smoothly: sections
 * below the change slide to their new positions, and sections that appear or disappear fade. Pass `morph` to grow
 * one element into its new state as the same object, such as an address card becoming its form.
 *
 * Returns false, without calling `update`, when view transitions or their types aren't supported, the shopper
 * prefers reduced motion, or the checkout form isn't in this document (in the editor it renders in an iframe).
 * Callers then apply the change themselves.
 */
export const startCheckoutLayoutTransition = (
	update: () => void,
	morph?: { element: HTMLElement | null; name: string }
): boolean => {
	const doc = document as ViewTransitionDocument;
	if (
		typeof doc.startViewTransition !== 'function' ||
		typeof ViewTransition === 'undefined' ||
		! ( 'types' in ViewTransition.prototype ) ||
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ||
		! doc.querySelector( SECTIONS )
	) {
		return false;
	}

	// Names are set inline, only for the transition, so React's class updates can't drop them.
	const named: HTMLElement[] = [];
	let sectionCount = 0;
	const setName = ( element: HTMLElement, name: string ) => {
		element.style.setProperty( 'view-transition-name', name );
		named.push( element );
	};
	const nameSections = () => {
		document
			.querySelectorAll< HTMLElement >( SECTIONS )
			.forEach( ( section ) => {
				if (
					! section.style.getPropertyValue(
						'view-transition-name'
					) &&
					section.offsetHeight > 0
				) {
					setName(
						section,
						`wc-checkout-section-${ sectionCount++ }`
					);
				}
			} );
	};

	if ( morph?.element ) {
		setName( morph.element, morph.name );
	}
	nameSections();

	const transition = doc.startViewTransition( {
		update: () => {
			update();
			// Sections that only exist after the update fade in.
			nameSections();
		},
		types: [ 'wc-checkout-layout' ],
	} );

	const cleanup = () =>
		named.forEach( ( element ) =>
			element.style.removeProperty( 'view-transition-name' )
		);
	transition.finished.then( cleanup, cleanup );

	return true;
};
