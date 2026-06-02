declare module '@wordpress/data/build-types/redux-store/metadata/utils' {
	/**
	 * External dependencies
	 */
	import type { AnyAction, Reducer } from 'redux';
	/**
	 * Higher-order reducer creator which creates a combined reducer object, keyed
	 * by a property on the action object.
	 *
	 * @param actionProperty Action property by which to key object.
	 * @return Higher-order reducer.
	 */
	export declare const onSubKey: <TState extends unknown, TAction extends AnyAction>(actionProperty: string) => (reducer: Reducer<TState, TAction>) => Reducer<Record<string, TState>, TAction>;
	/**
	 * Normalize selector argument array by defaulting `undefined` value to an empty array
	 * and removing trailing `undefined` values.
	 *
	 * @param args Selector argument array
	 * @return Normalized state key array
	 */
	export declare function selectorArgsToStateKey(args: unknown[] | null | undefined): unknown[];
}
