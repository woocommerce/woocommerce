/// <reference path="./registry.d.ts" />

declare module '@wordpress/data/build-types/resolvers-cache-middleware' {
	export default createResolversCacheMiddleware;
	export type WPDataRegistry = import("@wordpress/data/build-types/registry").WPDataRegistry;
	/** @typedef {import('./registry').WPDataRegistry} WPDataRegistry */
	/**
	 * Creates a middleware handling resolvers cache invalidation.
	 *
	 * @param {WPDataRegistry} registry  Registry for which to create the middleware.
	 * @param {string}         storeName Name of the store for which to create the middleware.
	 *
	 * @return {Function} Middleware function.
	 */
	declare function createResolversCacheMiddleware(registry: WPDataRegistry, storeName: string): Function;
}
