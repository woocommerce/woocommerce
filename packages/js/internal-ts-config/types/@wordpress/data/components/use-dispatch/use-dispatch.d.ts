/// <reference path="../../types.d.ts" />
/// <reference path="../../registry.d.ts" />

declare module '@wordpress/data/build-types/components/use-dispatch/use-dispatch' {
	import type { StoreDescriptor, StoreNameOrDescriptor, StoreRegistry, AnyConfig, ActionCreatorsOf, DispatchFunction } from '@wordpress/data/build-types/types';
	/**
	 * A custom react hook returning the current registry dispatch actions creators.
	 *
	 * Note: The component using this hook must be within the context of a
	 * RegistryProvider.
	 */
	declare function useDispatch(): DispatchFunction;
	declare function useDispatch<S extends StoreDescriptor<AnyConfig>>(storeDescriptor: S): ActionCreatorsOf<S>;
	declare function useDispatch<K extends keyof StoreRegistry>(storeName: K): ActionCreatorsOf<StoreRegistry[K]>;
	declare function useDispatch(storeNameOrDescriptor: StoreNameOrDescriptor): ActionCreatorsOf<StoreDescriptor>;
	export default useDispatch;
}
