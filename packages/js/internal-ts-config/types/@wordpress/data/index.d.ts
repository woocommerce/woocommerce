/// <reference path="./components/async-mode-provider/index.d.ts" />
/// <reference path="./components/registry-provider/index.d.ts" />
/// <reference path="./components/use-dispatch/index.d.ts" />
/// <reference path="./components/use-select/index.d.ts" />
/// <reference path="./components/with-dispatch/index.d.ts" />
/// <reference path="./components/with-registry/index.d.ts" />
/// <reference path="./components/with-select/index.d.ts" />
/// <reference path="./controls.d.ts" />
/// <reference path="./create-selector.d.ts" />
/// <reference path="./dispatch.d.ts" />
/// <reference path="./factory.d.ts" />
/// <reference path="./plugins/index.d.ts" />
/// <reference path="./redux-store/index.d.ts" />
/// <reference path="./registry.d.ts" />
/// <reference path="./select.d.ts" />
/// <reference path="./types.d.ts" />

declare module '@wordpress/data' {
	import type {
		AnyConfig,
		StoreDescriptor as _SD,
		StoreRegistry,
		StoreNameOrDescriptor,
		CurriedSelectorsResolveOf,
		CurriedSelectorsOf,
		ReduxStoreConfig,
	} from '@wordpress/data/build-types/types';
	export { default as withSelect } from "@wordpress/data/build-types/components/with-select";
	export { default as withDispatch } from "@wordpress/data/build-types/components/with-dispatch";
	export { default as withRegistry } from "@wordpress/data/build-types/components/with-registry";
	export { useDispatch } from "@wordpress/data/build-types/components/use-dispatch";
	export { AsyncModeProvider } from "@wordpress/data/build-types/components/async-mode-provider";
	export { createRegistry } from "@wordpress/data/build-types/registry";
	export { createSelector } from "@wordpress/data/build-types/create-selector";
	export { controls } from "@wordpress/data/build-types/controls";
	export { default as createReduxStore } from "@wordpress/data/build-types/redux-store";
	export { dispatch } from "@wordpress/data/build-types/dispatch";
	export { select } from "@wordpress/data/build-types/select";
	export { plugins };
	/**
	 * The combineReducers helper function turns an object whose values are different
	 * reducing functions into a single reducing function you can pass to registerReducer.
	 *
	 * @type  {import('./types').combineReducers}
	 * @param {Object} reducers An object whose values correspond to different reducing
	 *                          functions that need to be combined into one.
	 *
	 * @example
	 * ```js
	 * import { combineReducers, createReduxStore, register } from '@wordpress/data';
	 *
	 * const prices = ( state = {}, action ) => {
	 * 	return action.type === 'SET_PRICE' ?
	 * 		{
	 * 			...state,
	 * 			[ action.item ]: action.price,
	 * 		} :
	 * 		state;
	 * };
	 *
	 * const discountPercent = ( state = 0, action ) => {
	 * 	return action.type === 'START_SALE' ?
	 * 		action.discountPercent :
	 * 		state;
	 * };
	 *
	 * const store = createReduxStore( 'my-shop', {
	 * 	reducer: combineReducers( {
	 * 		prices,
	 * 		discountPercent,
	 * 	} ),
	 * } );
	 * register( store );
	 * ```
	 *
	 * @return {Function} A reducer that invokes every reducer inside the reducers
	 *                    object, and constructs a state object with the same shape.
	 */
	export const combineReducers: import("@wordpress/data/build-types/types").combineReducers;
	/**
	 * Given a store descriptor, returns an object containing the store's selectors pre-bound to state
	 * so that you only need to supply additional arguments, and modified so that they return promises
	 * that resolve to their eventual values, after any resolvers have ran.
	 *
	 * @param {StoreDescriptor|string} storeNameOrDescriptor The store descriptor. The legacy calling
	 *                                                       convention of passing the store name is
	 *                                                       also supported.
	 *
	 * @example
	 * ```js
	 * import { resolveSelect } from '@wordpress/data';
	 * import { store as myCustomStore } from 'my-custom-store';
	 *
	 * resolveSelect( myCustomStore ).getPrice( 'hammer' ).then(console.log)
	 * ```
	 *
	 * @return {Object} Object containing the store's promise-wrapped selectors.
	 */
	export function resolveSelect<S extends _SD<AnyConfig>>(storeDescriptor: S): CurriedSelectorsResolveOf<S>;
	export function resolveSelect<K extends keyof StoreRegistry>(storeName: K): CurriedSelectorsResolveOf<StoreRegistry[K]>;
	export function resolveSelect(storeNameOrDescriptor: StoreNameOrDescriptor): CurriedSelectorsResolveOf<_SD>;
	/**
	 * Given a store descriptor, returns an object containing the store's selectors pre-bound to state
	 * so that you only need to supply additional arguments, and modified so that they throw promises
	 * in case the selector is not resolved yet.
	 *
	 * @param {StoreDescriptor|string} storeNameOrDescriptor The store descriptor. The legacy calling
	 *                                                       convention of passing the store name is
	 *                                                       also supported.
	 *
	 * @return {Object} Object containing the store's suspense-wrapped selectors.
	 */
	export function suspendSelect<S extends _SD<AnyConfig>>(storeDescriptor: S): CurriedSelectorsOf<S>;
	export function suspendSelect<K extends keyof StoreRegistry>(storeName: K): CurriedSelectorsOf<StoreRegistry[K]>;
	export function suspendSelect(storeNameOrDescriptor: StoreNameOrDescriptor): CurriedSelectorsOf<_SD>;
	/**
	 * Given a listener function, the function will be called any time the state value
	 * of one of the registered stores has changed. If you specify the optional
	 * `storeNameOrDescriptor` parameter, the listener function will be called only
	 * on updates on that one specific registered store.
	 *
	 * This function returns an `unsubscribe` function used to stop the subscription.
	 *
	 * @param {Function}                listener              Callback function.
	 * @param {string|StoreDescriptor?} storeNameOrDescriptor Optional store name.
	 *
	 * @example
	 * ```js
	 * import { subscribe } from '@wordpress/data';
	 *
	 * const unsubscribe = subscribe( () => {
	 * 	// You could use this opportunity to test whether the derived result of a
	 * 	// selector has subsequently changed as the result of a state update.
	 * } );
	 *
	 * // Later, if necessary...
	 * unsubscribe();
	 * ```
	 */
	export const subscribe: (listener: () => void, storeNameOrDescriptor?: string | _SD<ReduxStoreConfig<any, any, any>>) => (() => void);
	/**
	 * Registers a generic store instance.
	 *
	 * @deprecated Use `register( storeDescriptor )` instead.
	 *
	 * @param {string} name  Store registry name.
	 * @param {Object} store Store instance (`{ getSelectors, getActions, subscribe }`).
	 */
	export const registerGenericStore: Function;
	/**
	 * Registers a standard `@wordpress/data` store.
	 *
	 * @deprecated Use `register` instead.
	 *
	 * @param {string} storeName Unique namespace identifier for the store.
	 * @param {Object} options   Store description (reducer, actions, selectors, resolvers).
	 *
	 * @return {Object} Registered store object.
	 */
	export const registerStore: Function;
	/**
	 * Extends a registry to inherit functionality provided by a given plugin. A
	 * plugin is an object with properties aligning to that of a registry, merged
	 * to extend the default registry behavior.
	 *
	 * @param {Object} plugin Plugin object.
	 */
	export const use: any;
	/**
	 * Registers a standard `@wordpress/data` store descriptor.
	 *
	 * @example
	 * ```js
	 * import { createReduxStore, register } from '@wordpress/data';
	 *
	 * const store = createReduxStore( 'demo', {
	 *     reducer: ( state = 'OK' ) => state,
	 *     selectors: {
	 *         getValue: ( state ) => state,
	 *     },
	 * } );
	 * register( store );
	 * ```
	 *
	 * @param {StoreDescriptor} store Store descriptor.
	 */
	export const register: (store: _SD<ReduxStoreConfig<any, any, any>>) => void;
	import * as plugins from '@wordpress/data/build-types/plugins';
	export { RegistryProvider, RegistryConsumer, useRegistry } from "@wordpress/data/build-types/components/registry-provider";
	export { default as useSelect, useSuspenseSelect } from "@wordpress/data/build-types/components/use-select";
	export { createRegistrySelector, createRegistryControl } from "@wordpress/data/build-types/factory";
	export type StoreDescriptor = import("@wordpress/data/build-types/types").StoreDescriptor<any>;
	export { StoreRegistry } from "@wordpress/data/build-types/types";
}
