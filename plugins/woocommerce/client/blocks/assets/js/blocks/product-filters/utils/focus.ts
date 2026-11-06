/**
 * Internal dependencies
 */
import { getFocusableElements } from '../../../utils/get-focusable-elements';

const FILTERS_SELECTOR = '.wc-block-product-filters';
const CHIP_SELECTOR = '.wc-block-product-filter-removable-chips__remove';

/**
 * Identify the same control in new markup without relying on its DOM reference.
 */
function matchControl( element: HTMLElement ) {
	const { id, tagName } = element;
	const attributes = [ 'type', 'name', 'aria-label' ].map(
		( name ) => [ name, element.getAttribute( name ) ] as const
	);
	const isChoice = element.matches(
		'input[type="checkbox"], input[type="radio"]'
	);
	const value = element.getAttribute( 'value' );
	const text = element.textContent?.replace( /\s+/g, ' ' ).trim();

	return ( candidate: HTMLElement ) =>
		candidate.tagName === tagName &&
		( id
			? candidate.id === id
			: attributes.every(
					( [ name, expected ] ) =>
						candidate.getAttribute( name ) === expected
				) &&
				( isChoice
					? candidate.getAttribute( 'value' ) === value
					: candidate.textContent?.replace( /\s+/g, ' ' ).trim() ===
						text ) );
}

/**
 * Find the results count belonging to the nearest enclosing layout.
 */
function findResultsCount( root: HTMLElement ): HTMLElement | null {
	let container = root.parentElement;
	while ( container ) {
		const count = Array.from(
			container.querySelectorAll< HTMLElement >(
				'.wp-block-woocommerce-product-results-count'
			)
		).find( ( element ) => element.offsetParent !== null );
		if ( count ) {
			return count;
		}
		container = container.parentElement;
	}
	return null;
}

/**
 * Keep the shopper's place when the router replaces a focused filter control.
 * Call cleanup even when navigation fails or is cancelled.
 */
export function prepareFocusRestoration( ref: Element | null ) {
	const ownerDocument = ref?.ownerDocument;
	if ( ! ownerDocument ) {
		return null;
	}
	const element = ownerDocument.activeElement;
	if ( ! ( element instanceof HTMLElement ) ) {
		return null;
	}
	const root = element.closest< HTMLElement >( FILTERS_SELECTOR );
	if ( ! root ) {
		return null;
	}
	const matches = matchControl( element );
	const controls = getFocusableElements( root );
	const controlIndex = controls.filter( matches ).indexOf( element );
	if ( controlIndex < 0 ) {
		return null;
	}
	const regionId = root
		.closest( '[data-wp-router-region]' )
		?.getAttribute( 'data-wp-router-region' );
	const getRoots = () =>
		Array.from(
			ownerDocument.querySelectorAll< HTMLElement >( FILTERS_SELECTOR )
		).filter(
			( candidate ) =>
				candidate
					.closest( '[data-wp-router-region]' )
					?.getAttribute( 'data-wp-router-region' ) === regionId
		);
	const rootIndex = getRoots().indexOf( root );
	const chipIndex = controls
		.filter( ( control ) => control.matches( CHIP_SELECTOR ) )
		.indexOf( element );
	let focusMoved = false;
	const onFocus = ( event: FocusEvent ) => {
		if (
			event.target !== element &&
			event.target !== ownerDocument.body &&
			event.target !== ownerDocument.documentElement
		) {
			focusMoved = true;
		}
	};
	ownerDocument.addEventListener( 'focusin', onFocus );

	return {
		cleanup: () => ownerDocument.removeEventListener( 'focusin', onFocus ),
		restore: () => {
			if ( focusMoved || ownerDocument.activeElement === element ) {
				return;
			}
			const currentRoot = root.isConnected
				? root
				: getRoots()[ rootIndex ];
			if ( ! currentRoot ) {
				return;
			}
			const currentControls = getFocusableElements( currentRoot );
			const nextChip =
				chipIndex < 0
					? undefined
					: currentControls.filter( ( control ) =>
							control.matches( CHIP_SELECTOR )
						)[ chipIndex ];
			const clear = currentControls.find( ( control ) =>
				control.closest( '.wc-block-product-filter-clear-button' )
			);
			const dialog = currentRoot.querySelector< HTMLElement >(
				'.wc-block-product-filters__overlay-dialog[aria-modal="true"]'
			);
			const resultsCount = dialog
				? null
				: findResultsCount( currentRoot );
			const fallback = dialog
				? getFocusableElements( dialog )[ 0 ]
				: ( resultsCount ?? currentControls[ 0 ] );
			const target =
				currentControls.filter( matches )[ controlIndex ] ??
				nextChip ??
				clear ??
				fallback;
			if ( ! target ) {
				return;
			}
			if (
				! target.hasAttribute( 'tabindex' ) &&
				target === resultsCount
			) {
				target.setAttribute( 'tabindex', '-1' );
				target.addEventListener(
					'blur',
					() => {
						if ( target.getAttribute( 'tabindex' ) === '-1' ) {
							target.removeAttribute( 'tabindex' );
						}
					},
					{ once: true }
				);
			}
			target.focus( { preventScroll: true } );
		},
	};
}
