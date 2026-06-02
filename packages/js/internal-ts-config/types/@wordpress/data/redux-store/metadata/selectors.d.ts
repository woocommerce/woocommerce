/// <reference path="./reducer.d.ts" />

declare module '@wordpress/data/build-types/redux-store/metadata/selectors' {
	/** @typedef {Record<string, import('./reducer').State>} State */
	/** @typedef {import('./reducer').StateValue} StateValue */
	/** @typedef {import('./reducer').Status} Status */
	/**
	 * Returns the raw resolution state value for a given selector name,
	 * and arguments set. May be undefined if the selector has never been resolved
	 * or not resolved for the given set of arguments, otherwise true or false for
	 * resolution started and completed respectively.
	 *
	 * @param {State}      state        Data state.
	 * @param {string}     selectorName Selector name.
	 * @param {unknown[]?} args         Arguments passed to selector.
	 *
	 * @return {StateValue|undefined} isResolving value.
	 */
	export function getResolutionState(state: State, selectorName: string, args: unknown[] | null): StateValue | undefined;
	/**
	 * Returns an `isResolving`-like value for a given selector name and arguments set.
	 * Its value is either `undefined` if the selector has never been resolved or has been
	 * invalidated, or a `true`/`false` boolean value if the resolution is in progress or
	 * has finished, respectively.
	 *
	 * This is a legacy selector that was implemented when the "raw" internal data had
	 * this `undefined | boolean` format. Nowadays the internal value is an object that
	 * can be retrieved with `getResolutionState`.
	 *
	 * @deprecated
	 *
	 * @param {State}      state        Data state.
	 * @param {string}     selectorName Selector name.
	 * @param {unknown[]?} args         Arguments passed to selector.
	 *
	 * @return {boolean | undefined} isResolving value.
	 */
	export function getIsResolving(state: State, selectorName: string, args: unknown[] | null): boolean | undefined;
	/**
	 * Returns true if resolution has already been triggered for a given
	 * selector name, and arguments set.
	 *
	 * @param {State}      state        Data state.
	 * @param {string}     selectorName Selector name.
	 * @param {unknown[]?} args         Arguments passed to selector.
	 *
	 * @return {boolean} Whether resolution has been triggered.
	 */
	export function hasStartedResolution(state: State, selectorName: string, args: unknown[] | null): boolean;
	/**
	 * Returns true if resolution has completed for a given selector
	 * name, and arguments set.
	 *
	 * @param {State}      state        Data state.
	 * @param {string}     selectorName Selector name.
	 * @param {unknown[]?} args         Arguments passed to selector.
	 *
	 * @return {boolean} Whether resolution has completed.
	 */
	export function hasFinishedResolution(state: State, selectorName: string, args: unknown[] | null): boolean;
	/**
	 * Returns true if resolution has failed for a given selector
	 * name, and arguments set.
	 *
	 * @param {State}      state        Data state.
	 * @param {string}     selectorName Selector name.
	 * @param {unknown[]?} args         Arguments passed to selector.
	 *
	 * @return {boolean} Has resolution failed
	 */
	export function hasResolutionFailed(state: State, selectorName: string, args: unknown[] | null): boolean;
	/**
	 * Returns the resolution error for a given selector name, and arguments set.
	 * Note it may be of an Error type, but may also be null, undefined, or anything else
	 * that can be `throw`-n.
	 *
	 * @param {State}      state        Data state.
	 * @param {string}     selectorName Selector name.
	 * @param {unknown[]?} args         Arguments passed to selector.
	 *
	 * @return {Error|unknown} Last resolution error
	 */
	export function getResolutionError(state: State, selectorName: string, args: unknown[] | null): Error | unknown;
	/**
	 * Returns true if resolution has been triggered but has not yet completed for
	 * a given selector name, and arguments set.
	 *
	 * @param {State}      state        Data state.
	 * @param {string}     selectorName Selector name.
	 * @param {unknown[]?} args         Arguments passed to selector.
	 *
	 * @return {boolean} Whether resolution is in progress.
	 */
	export function isResolving(state: State, selectorName: string, args: unknown[] | null): boolean;
	/**
	 * Returns the list of the cached resolvers.
	 *
	 * @param {State} state Data state.
	 *
	 * @return {State} Resolvers mapped by args and selectorName.
	 */
	export function getCachedResolvers(state: State): State;
	/**
	 * Whether the store has any currently resolving selectors.
	 *
	 * @param {State} state Data state.
	 *
	 * @return {boolean} True if one or more selectors are resolving, false otherwise.
	 */
	export function hasResolvingSelectors(state: State): boolean;
	/**
	 * Retrieves the total number of selectors, grouped per status.
	 *
	 * @param {State} state Data state.
	 *
	 * @return {Object} Object, containing selector totals by status.
	 */
	export const countSelectorsByStatus: ((state: any) => {}) & import("rememo").EnhancedSelector;
	export type State = Record<string, import("@wordpress/data/build-types/redux-store/metadata/reducer").State>;
	export type StateValue = import("@wordpress/data/build-types/redux-store/metadata/reducer").StateValue;
	export type Status = import("@wordpress/data/build-types/redux-store/metadata/reducer").Status;
}
