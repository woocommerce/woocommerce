/// <reference path="./types.d.ts" />

declare module '@wordpress/data/build-types/select' {
	/**
	 * Internal dependencies
	 */
	import type { AnyConfig, CurriedSelectorsOf, StoreDescriptor, StoreRegistry, StoreNameOrDescriptor } from '@wordpress/data/build-types/types';
	/**
	 * Given a store descriptor, returns an object of the store's selectors.
	 * The selector functions are been pre-bound to pass the current state automatically.
	 * As a consumer, you need only pass arguments of the selector, if applicable.
	 *
	 * @example
	 * ```js
	 * import { select } from '@wordpress/data';
	 * import { store as myCustomStore } from 'my-custom-store';
	 *
	 * select( myCustomStore ).getPrice( 'hammer' );
	 * ```
	 *
	 * @return Object containing the store's selectors.
	 */
	export declare function select<S extends StoreDescriptor<AnyConfig>>(storeDescriptor: S): CurriedSelectorsOf<S>;
	export declare function select<K extends keyof StoreRegistry>(storeName: K): CurriedSelectorsOf<StoreRegistry[K]>;
	export declare function select(storeNameOrDescriptor: StoreNameOrDescriptor): CurriedSelectorsOf<StoreDescriptor>;
}
