/// <reference path="../../registry.d.ts" />

declare module '@wordpress/data/build-types/plugins/persistence' {
	/**
	 * Creates a persistence interface, exposing getter and setter methods (`get`
	 * and `set` respectively).
	 *
	 * @param {WPDataPersistencePluginOptions} options Plugin options.
	 *
	 * @return {Object} Persistence interface.
	 */
	export function createPersistenceInterface(options: WPDataPersistencePluginOptions): Object;
	export function withLazySameState(reducer: Function): Function;
	export default persistencePlugin;
	export type WPDataRegistry = import("@wordpress/data/build-types/registry").WPDataRegistry;
	export type WPDataPlugin = import("@wordpress/data/build-types/registry").WPDataPlugin;
	/**
	 * Persistence plugin options.
	 */
	export type WPDataPersistencePluginOptions = {
	    /**
	     * Persistent storage implementation. This must
	     * at least implement `getItem` and `setItem` of
	     * the Web Storage API.
	     */
	    storage: Storage;
	    /**
	     * Key on which to set in persistent storage.
	     */
	    storageKey: string;
	};
	/**
	 * Data plugin to persist store state into a single storage key.
	 *
	 * @param {WPDataRegistry}                  registry      Data registry.
	 * @param {?WPDataPersistencePluginOptions} pluginOptions Plugin options.
	 *
	 * @return {WPDataPlugin} Data plugin.
	 */
	declare function persistencePlugin(registry: WPDataRegistry, pluginOptions: WPDataPersistencePluginOptions | null): WPDataPlugin;
	declare namespace persistencePlugin {
	    function __unstableMigrate(): void;
	}
}
