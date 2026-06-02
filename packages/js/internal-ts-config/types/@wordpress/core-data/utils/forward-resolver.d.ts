declare module '@wordpress/core-data/build-types/utils/forward-resolver' {
	export default forwardResolver;
	/**
	 * Higher-order function which forward the resolution to another resolver with the same arguments.
	 *
	 * @param {string} resolverName forwarded resolver.
	 *
	 * @return {Function} Enhanced resolver.
	 */
	declare function forwardResolver(resolverName: string): Function;
}
