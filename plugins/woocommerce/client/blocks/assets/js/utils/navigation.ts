/**
 * Performs a full-page navigation to the given URL.
 *
 * Wrapping `window.location.assign` in a module boundary keeps callers
 * testable: jsdom does not implement navigation, and since jsdom 21
 * `window.location` is non-configurable, so it cannot be stubbed directly.
 * Tests mock this function instead.
 *
 * @param {string} url The URL to navigate to.
 */
export function reload( url: string ) {
	window.location.assign( url );
}
