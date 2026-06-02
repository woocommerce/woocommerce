declare module '@wordpress/data/build-types/promise-middleware' {
	export default promiseMiddleware;
	/**
	 * Simplest possible promise redux middleware.
	 *
	 * @type {import('redux').Middleware}
	 */
	declare const promiseMiddleware: import("redux").Middleware;
}
