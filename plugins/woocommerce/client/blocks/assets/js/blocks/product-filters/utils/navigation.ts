/**
 * Performs a full-page navigation to the given URL.
 *
 * Wrapping `window.location.assign` in a module boundary keeps the store's
 * `navigate` action testable: jsdom does not implement navigation and its
 * `window.location` is non-configurable, so tests mock this function instead.
 *
 * @param {string} url The URL to navigate to.
 */
export function reload( url: string ) {
	window.location.assign( url );
}
