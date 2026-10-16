/**
 * Elements that can be reached with Tab.
 * Disabled fields, hidden inputs, and aria-hidden fields are excluded.
 * A negative tabindex is excluded for elements that are not already a link, field, or button.
 */
export const FOCUSABLE_SELECTOR = [
	'a[href]',
	'input:not([disabled]):not([type="hidden"]):not([aria-hidden])',
	'select:not([disabled]):not([aria-hidden])',
	'textarea:not([disabled]):not([aria-hidden])',
	'button:not([disabled]):not([aria-hidden])',
	'[contenteditable]',
	'[tabindex]:not([tabindex^="-"])',
].join( ',' );

/**
 * Focusable descendants that are currently shown.
 * An element hidden with `display: none` has no offsetParent, so it is skipped.
 *
 * @param container Element to search within. Nothing is returned when it is missing.
 */
export function getFocusableElements(
	container?: HTMLElement | null
): HTMLElement[] {
	if ( ! container ) {
		return [];
	}

	return Array.from(
		container.querySelectorAll< HTMLElement >( FOCUSABLE_SELECTOR )
	).filter( ( element ) => element.offsetParent !== null );
}
