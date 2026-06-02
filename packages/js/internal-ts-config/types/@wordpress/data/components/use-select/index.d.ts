/// <reference path="../../types.d.ts" />

declare module '@wordpress/data/build-types/components/use-select' {
	import type { MapSelect, CurriedSelectorsOf, StoreDescriptor, StoreNameOrDescriptor, StoreRegistry, AnyConfig } from '@wordpress/data/build-types/types';
	/**
	 * Custom react hook for retrieving props from registered selectors.
	 *
	 * In general, this custom React hook follows the
	 * [rules of hooks](https://react.dev/reference/rules/rules-of-hooks).
	 */
	declare function useSelect<T extends MapSelect>(mapSelect: T, deps?: unknown[]): ReturnType<T>;
	declare function useSelect<S extends StoreDescriptor<AnyConfig>>(storeDescriptor: S): CurriedSelectorsOf<S>;
	declare function useSelect<K extends keyof StoreRegistry>(storeName: K): CurriedSelectorsOf<StoreRegistry[K]>;
	declare function useSelect(storeNameOrDescriptor: StoreNameOrDescriptor): CurriedSelectorsOf<StoreDescriptor>;
	export default useSelect;
	/**
	 * A variant of the `useSelect` hook that has the same API, but is a compatible
	 * Suspense-enabled data source.
	 *
	 * @param mapSelect Function called on every state change.
	 * @param deps      A dependency array used to memoize the `mapSelect`.
	 *
	 * @throws A suspense Promise that is thrown if any of the called
	 * selectors is in an unresolved state.
	 *
	 * @return Data object returned by the `mapSelect` function.
	 */
	export function useSuspenseSelect<T extends MapSelect>(mapSelect: T, deps: unknown[]): ReturnType<T>;
}
