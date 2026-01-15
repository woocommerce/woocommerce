/**
 * Based on https://github.com/WordPress/gutenberg/blob/trunk/packages/components/src/modal/aria-helper.ts
 *
 * Modified to support a custom `data-keep-visible` attribute,
 * which prevents elements from being hidden from screen readers.
 */
const LIVE_REGION_ARIA_ROLES = new Set( [
	'alert',
	'status',
	'log',
	'marquee',
	'timer',
] );

let hiddenElements: Element[] = [],
	isHidden = false;

/**
 * Determines if the passed element should be hidden from screen readers.
 *
 * An element will be hidden unless it is one of the following:
 * - a <script> tag
 * - has `aria-hidden`
 * - has `aria-live`
 * - has `data-keep-visible`
 * - has a live region role (`alert`, `status`, `log`, `marquee`, or `timer`)
 *
 * @return {boolean} Whether the element should be hidden from screen-readers.
 */
export function elementShouldBeHidden( element: Element ) {
	const role = element.getAttribute( 'role' );
	return ! (
		element.tagName === 'SCRIPT' ||
		element.hasAttribute( 'aria-hidden' ) ||
		element.hasAttribute( 'aria-live' ) ||
		element.hasAttribute( 'data-keep-visible' ) ||
		( role && LIVE_REGION_ARIA_ROLES.has( role ) )
	);
}

/**
 * Hides all elements in the body element from screen-readers except
 * the provided element and elements that should not be hidden from
 * screen-readers.
 *
 * The reason we do this is because `aria-modal="true"` currently is bugged
 * in Safari, and support is spotty in other browsers overall. In the future
 * we should consider removing these helper functions in favor of
 * `aria-modal="true"`.
 *
 * @param {HTMLDivElement} unhiddenElement The element that should not be hidden.
 */
export function hideApp( unhiddenElement?: HTMLDivElement ) {
	if ( isHidden ) {
		return;
	}
	const elements = Array.from( document.body.children );
	elements.forEach( ( element ) => {
		if ( element === unhiddenElement ) {
			return;
		}
		if ( elementShouldBeHidden( element ) ) {
			element.setAttribute( 'aria-hidden', 'true' );
			hiddenElements.push( element );
		}
	} );
	isHidden = true;
}

/**
 * Makes all elements in the body that have been hidden by `hideApp`
 * visible again to screen-readers.
 */
export function showApp() {
	if ( ! isHidden ) {
		return;
	}
	hiddenElements.forEach( ( element ) => {
		element.removeAttribute( 'aria-hidden' );
	} );
	hiddenElements = [];
	isHidden = false;
}
