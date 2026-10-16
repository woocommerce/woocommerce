const focusableSelector =
	'a[href], area[href], button, input, select, textarea, iframe, object, embed, summary, audio[controls], video[controls], [tabindex], [contenteditable]:not([contenteditable="false"])';

export function getTabbableElements( container: HTMLElement ): HTMLElement[] {
	const elements = Array.from(
		container.querySelectorAll< HTMLElement >( focusableSelector )
	).filter( ( element ) => {
		const tabIndex = element.getAttribute( 'tabindex' );
		return (
			! ( tabIndex !== null && Number( tabIndex ) < 0 ) &&
			( element.tabIndex >= 0 ||
				( tabIndex === null && element.isContentEditable ) ) &&
			! element.matches( ':disabled, input[type="hidden"]' ) &&
			! element.closest( '[inert]' ) &&
			element.getClientRects().length > 0 &&
			getComputedStyle( element ).visibility === 'visible'
		);
	} );

	return elements
		.filter( ( element ) => {
			if (
				! ( element instanceof HTMLInputElement ) ||
				element.type !== 'radio' ||
				! element.name
			) {
				return true;
			}
			const group = elements.filter(
				( candidate ): candidate is HTMLInputElement =>
					candidate instanceof HTMLInputElement &&
					candidate.type === 'radio' &&
					candidate.name === element.name &&
					candidate.form === element.form
			);
			return (
				element ===
				( group.find( ( radio ) => radio.checked ) || group[ 0 ] )
			);
		} )
		.sort( ( a, b ) => {
			const aIndex = a.tabIndex > 0 ? a.tabIndex : Infinity;
			const bIndex = b.tabIndex > 0 ? b.tabIndex : Infinity;
			return aIndex === bIndex ? 0 : aIndex - bIndex;
		} );
}

const openOverlays = new Set< HTMLElement >();
let previousOverflow = '';
let previousOverflowPriority = '';

export function setOverlayScrolling( element: HTMLElement, isOpen: boolean ) {
	if ( isOpen ) {
		if ( openOverlays.size === 0 ) {
			previousOverflow =
				document.body.style.getPropertyValue( 'overflow' );
			previousOverflowPriority =
				document.body.style.getPropertyPriority( 'overflow' );
		}
		openOverlays.add( element );
		document.body.style.setProperty( 'overflow', 'hidden' );
	} else if ( openOverlays.delete( element ) && openOverlays.size === 0 ) {
		if ( previousOverflow ) {
			document.body.style.setProperty(
				'overflow',
				previousOverflow,
				previousOverflowPriority
			);
		} else {
			document.body.style.removeProperty( 'overflow' );
		}
	}
}

export function isTopmostOverlay( element: HTMLElement ) {
	const overlays = Array.from( openOverlays );
	return overlays[ overlays.length - 1 ] === element;
}
