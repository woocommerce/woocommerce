/// <reference path="./types.d.ts" />

declare module '@wordpress/data/build-types/dispatch' {
	/**
	 * Internal dependencies
	 */
	import type { AnyConfig, StoreDescriptor, StoreRegistry, StoreNameOrDescriptor, ActionCreatorsOf } from '@wordpress/data/build-types/types';
	/**
	 * Given a store descriptor, returns an object of the store's action creators.
	 * Calling an action creator will cause it to be dispatched, updating the state value accordingly.
	 *
	 * Note: Action creators returned by the dispatch will return a promise when
	 * they are called.
	 *
	 * @example
	 * ```js
	 * import { dispatch } from '@wordpress/data';
	 * import { store as myCustomStore } from 'my-custom-store';
	 *
	 * dispatch( myCustomStore ).setPrice( 'hammer', 9.75 );
	 * ```
	 * @return Object containing the action creators.
	 */
	export declare function dispatch<S extends StoreDescriptor<AnyConfig>>(storeDescriptor: S): ActionCreatorsOf<S>;
	export declare function dispatch<K extends keyof StoreRegistry>(storeName: K): ActionCreatorsOf<StoreRegistry[K]>;
	export declare function dispatch(storeNameOrDescriptor: StoreNameOrDescriptor): ActionCreatorsOf<StoreDescriptor>;
}
