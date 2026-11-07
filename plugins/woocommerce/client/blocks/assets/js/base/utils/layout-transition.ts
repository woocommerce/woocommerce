type ViewTransitionDocument = Document & {
	startViewTransition: ( options: {
		update: () => void | Promise< void >;
		types: string[];
	} ) => { finished: Promise< void > };
};

export const CHECKOUT_SECTIONS = '.wc-block-checkout__form > *';
export const ADDRESS_FORM_SECTIONS = `${ CHECKOUT_SECTIONS }, .wc-block-components-address-form > *`;

/**
 * Runs `update` (sync, or a promise for async renders) as a view transition: `sections` slide to their new places
 * and ones that appear or go wipe in or out; `morph` grows into its new state. Returns false without calling
 * `update` when unsupported, under reduced motion, or when no section is in this document.
 */
export const startLayoutTransition = (
	update: () => void | Promise< void >,
	{
		sections = CHECKOUT_SECTIONS,
		morph,
	}: { sections?: string | undefined; morph?: HTMLElement | null } = {}
): boolean => {
	const doc = document as ViewTransitionDocument;
	if (
		typeof ViewTransition === 'undefined' ||
		! ( 'types' in ViewTransition.prototype ) ||
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ||
		! doc.querySelector( sections )
	) {
		return false;
	}

	// Names are set inline, only for the transition, so React's class updates can't drop them.
	const named: HTMLElement[] = [];
	const setName = ( element: HTMLElement, name: string ) => {
		element.style.setProperty( 'view-transition-name', name );
		named.push( element );
	};
	const nameSections = () => {
		doc.querySelectorAll< HTMLElement >( sections ).forEach(
			( section ) => {
				if (
					! section.style.getPropertyValue(
						'view-transition-name'
					) &&
					section.offsetHeight > 0
				) {
					setName( section, `wc-layout-section-${ named.length }` );
				}
			}
		);
	};

	if ( morph ) {
		setName( morph, 'wc-layout-morph' );
	}
	nameSections();

	const transition = doc.startViewTransition( {
		update: () => {
			const updated = update();
			// Sections that only exist after the update wipe in.
			if ( updated instanceof Promise ) {
				return updated.then( nameSections );
			}
			nameSections();
		},
		types: [ 'wc-layout' ],
	} );

	const cleanup = () =>
		named.forEach( ( element ) =>
			element.style.removeProperty( 'view-transition-name' )
		);
	transition.finished.then( cleanup, cleanup );

	return true;
};
