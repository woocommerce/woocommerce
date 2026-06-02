/// <reference path="../types.d.ts" />

declare module '@wordpress/core-data/build-types/utils/if-matching-action' {
	export default ifMatchingAction;
	export type AnyFunction = import("@wordpress/core-data/build-types/types").AnyFunction;
	/** @typedef {import('../types').AnyFunction} AnyFunction */
	/**
	 * A higher-order reducer creator which invokes the original reducer only if
	 * the dispatching action matches the given predicate, **OR** if state is
	 * initializing (undefined).
	 *
	 * @param {AnyFunction} isMatch Function predicate for allowing reducer call.
	 *
	 * @return {AnyFunction} Higher-order reducer.
	 */
	declare function ifMatchingAction(isMatch: AnyFunction): AnyFunction;
}
