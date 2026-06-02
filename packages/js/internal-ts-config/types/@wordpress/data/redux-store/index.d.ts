/// <reference path="./combine-reducers.d.ts" />
/// <reference path="../types.d.ts" />

declare module '@wordpress/data/build-types/redux-store' {
	/**
	 * Creates a data store descriptor for the provided Redux store configuration containing
	 * properties describing reducer, actions, selectors, controls and resolvers.
	 *
	 * @example
	 * ```js
	 * import { createReduxStore } from '@wordpress/data';
	 *
	 * const store = createReduxStore( 'demo', {
	 *     reducer: ( state = 'OK' ) => state,
	 *     selectors: {
	 *         getValue: ( state ) => state,
	 *     },
	 * } );
	 * ```
	 *
	 * @template State
	 * @template {Record<string,import('../types').ActionCreator>} Actions
	 * @template Selectors
	 * @param {string}                                    key     Unique namespace identifier.
	 * @param {ReduxStoreConfig<State,Actions,Selectors>} options Registered store options, with properties
	 *                                                            describing reducer, actions, selectors,
	 *                                                            and resolvers.
	 *
	 * @return   {StoreDescriptor<ReduxStoreConfig<State,Actions,Selectors>>} Store Object.
	 */
	export default function createReduxStore<State, Actions extends Record<string, import("@wordpress/data/build-types/types").ActionCreator>, Selectors>(key: string, options: ReduxStoreConfig<State, Actions, Selectors>): StoreDescriptor<ReduxStoreConfig<State, Actions, Selectors>>;
	export { combineReducers };
	export type DataRegistry = import("@wordpress/data/build-types/types").DataRegistry;
	export type ListenerFunction = import("@wordpress/data/build-types/types").ListenerFunction;
	export type StoreDescriptor<C extends import("@wordpress/data/build-types/types").AnyConfig> = import("@wordpress/data/build-types/types").StoreDescriptor<C>;
	export type ReduxStoreConfig<State, Actions extends Record<string, import("@wordpress/data/build-types/types").ActionCreator>, Selectors> = import("@wordpress/data/build-types/types").ReduxStoreConfig<State, Actions, Selectors>;
	import { combineReducers } from '@wordpress/data/build-types/redux-store/combine-reducers';
}
