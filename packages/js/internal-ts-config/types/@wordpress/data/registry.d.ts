/// <reference path="./types.d.ts" />

declare module '@wordpress/data/build-types/registry' {
	import type {
		AnyConfig,
		StoreDescriptor,
		StoreInstance,
		ReduxStoreConfig,
		SelectFunction,
		DispatchFunction,
		CurriedSelectorsResolveOf,
		CurriedSelectorsOf,
		StoreRegistry,
		StoreNameOrDescriptor,
		ListenerFunction,
		InternalStoreInstance,
	} from '@wordpress/data/build-types/types';
	/**
	 * Creates a new store registry, given an optional object of initial store
	 * configurations.
	 *
	 * @param storeConfigs Initial store configurations.
	 * @param parent       Parent registry.
	 *
	 * @return Data registry.
	 */
	export function createRegistry(storeConfigs?: Record<string, ReduxStoreConfig<any, any, any>>, parent?: WPDataRegistry | null): WPDataRegistry;
	export type StoreDescriptor = import("@wordpress/data/build-types/types").StoreDescriptor<any>;
	/**
	 * An isolated orchestrator of store registrations.
	 */
	export type WPDataRegistry = {
	    batch: (callback: () => void) => void;
	    stores: Record<string, InternalStoreInstance>;
	    namespaces: Record<string, InternalStoreInstance>;
	    subscribe: (listener: ListenerFunction, storeNameOrDescriptor?: StoreNameOrDescriptor) => () => void;
	    select: SelectFunction;
	    resolveSelect: {
	        <S extends StoreDescriptor<any>>(storeDescriptor: S): CurriedSelectorsResolveOf<S>;
	        <K extends keyof StoreRegistry>(storeName: K): CurriedSelectorsResolveOf<StoreRegistry[K]>;
	        (storeNameOrDescriptor: StoreNameOrDescriptor): CurriedSelectorsResolveOf<StoreDescriptor>;
	    };
	    suspendSelect: {
	        <S extends StoreDescriptor<any>>(storeDescriptor: S): CurriedSelectorsOf<S>;
	        <K extends keyof StoreRegistry>(storeName: K): CurriedSelectorsOf<StoreRegistry[K]>;
	        (storeNameOrDescriptor: StoreNameOrDescriptor): CurriedSelectorsOf<StoreDescriptor>;
	    };
	    dispatch: DispatchFunction;
	    use: (plugin: WPDataPlugin, options?: Record<string, unknown>) => WPDataRegistry;
	    register: (store: StoreDescriptor<any>) => void;
	    registerGenericStore: (name: string, store: StoreInstance<AnyConfig>) => void;
	    registerStore: (storeName: string, options: ReduxStoreConfig<any, any, any>) => any;
	    __unstableMarkListeningStores: <T>(callback: () => T, ref: { current: string[] | null }) => T;
	};
	/**
	 * An object of registry function overrides.
	 */
	export type WPDataPlugin = (
	    registry: WPDataRegistry,
	    options?: Record<string, unknown>
	) => Partial<WPDataRegistry>;
}
