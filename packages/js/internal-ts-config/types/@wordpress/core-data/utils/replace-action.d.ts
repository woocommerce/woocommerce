/// <reference path="../types.d.ts" />

declare module '@wordpress/core-data/build-types/utils/replace-action' {
	export default replaceAction;
	export type AnyFunction = import("@wordpress/core-data/build-types/types").AnyFunction;
	/** @typedef {import('../types').AnyFunction} AnyFunction */
	/**
	 * Higher-order reducer creator which substitutes the action object before
	 * passing to the original reducer.
	 *
	 * @param {AnyFunction} replacer Function mapping original action to replacement.
	 *
	 * @return {AnyFunction} Higher-order reducer.
	 */
	declare function replaceAction(replacer: AnyFunction): AnyFunction;
}
