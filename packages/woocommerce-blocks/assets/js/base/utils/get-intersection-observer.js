/** @typedef {import('window').IntersectionObserverCallback} IntersectionObserverCallback */

/**
 * Util that returns an IntersectionObserver if supported by the browser. If
 * it's not supported, it returns a shim object with the methods to prevent JS
 * errors. Notice it's a shim, not a polyfill. If the browser doesn't support
 * IntersectionObserver, the methods returned by this function will do nothing.
 *
 * @param {IntersectionObserverCallback} callback Callback function for the
 *                                                Intersection Observer.
 * @param {Object}                       options  Intersection Observer options.
 * @return {Object|IntersectionObserver} Intersection Observer if available,
 *                                       otherwise a shim object.
 *
 * @todo Remove IntersectionObserver shim when we drop IE11 support.
 */
export const getIntersectionObserver = ( callback, options ) => {
	if ( typeof IntersectionObserver !== 'function' ) {
		return {
			observe: () => void null,
			unobserve: () => void null,
		};
	}

	return new IntersectionObserver( callback, options );
};
