declare module '@wordpress/data/build-types/components/use-dispatch/use-dispatch-with-map' {
	export default useDispatchWithMap;
	/**
	 * Custom react hook for returning aggregate dispatch actions using the provided
	 * dispatchMap.
	 *
	 * Currently this is an internal api only and is implemented by `withDispatch`
	 *
	 * @param {Function} dispatchMap Receives the `registry.dispatch` function as
	 *                               the first argument and the `registry` object
	 *                               as the second argument.  Should return an
	 *                               object mapping props to functions.
	 * @param {Array}    deps        An array of dependencies for the hook.
	 * @return {Object}  An object mapping props to functions created by the passed
	 *                   in dispatchMap.
	 */
	declare function useDispatchWithMap(dispatchMap: Function, deps: any[]): Object;
}
