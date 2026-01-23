/**
 * Complete type replacement for @wordpress/data to support Generator functions.
 *
 * This file completely replaces @wordpress/data's type definitions while
 * re-exporting its runtime values. This allows us to define Generator-aware
 * types without the limitations of module augmentation.
 */

declare module '@wordpress/data' {
	/**
	 * ============================================================================
	 * RE-EXPORT RUNTIME VALUES (functions, objects, components)
	 * ============================================================================
	 * These import the actual runtime implementations from @wordpress/data
	 */

	// Components
	export { default as withSelect } from '@wordpress/data/build-types/components/with-select';
	export { default as withDispatch } from '@wordpress/data/build-types/components/with-dispatch';
	export { default as withRegistry } from '@wordpress/data/build-types/components/with-registry';
	// useDispatch and useSelect overridden below
	export { RegistryProvider, RegistryConsumer, useRegistry } from '@wordpress/data/build-types/components/registry-provider';
	export { AsyncModeProvider } from '@wordpress/data/build-types/components/async-mode-provider';

	// Core functions
	export { createRegistry } from '@wordpress/data/build-types/registry';
	export { createSelector } from '@wordpress/data/build-types/create-selector';
	export { controls } from '@wordpress/data/build-types/controls';
	export { default as createReduxStore } from '@wordpress/data/build-types/redux-store';
	export { createRegistrySelector, createRegistryControl } from '@wordpress/data/build-types/factory';

	// Store interaction - we'll define custom type signatures below
	// (Runtime comes from @wordpress/data, but we override the types)

	// Other exports
	export const combineReducers: import('@wordpress/data/build-types/types').combineReducers;
	export const subscribe: Function;
	export const registerGenericStore: Function;
	export const registerStore: Function;
	export const use: any;
	export const register: any;
	export const plugins: any;

	/**
	 * ============================================================================
	 * IMPORT BASE TYPES WE'LL EXTEND
	 * ============================================================================
	 */

	import type {
		StoreDescriptor as OriginalStoreDescriptor,
		AnyConfig as OriginalAnyConfig,
		ReduxStoreConfig as OriginalReduxStoreConfig,
		ActionCreator as OriginalActionCreator,
		Selector as OriginalSelector,
	} from '@wordpress/data/build-types/types';

	import * as MetadataActions from '@wordpress/data/build-types/redux-store/metadata/actions';
	import * as MetadataSelectors from '@wordpress/data/build-types/redux-store/metadata/selectors';

	/**
	 * Internal helper types (not exported by @wordpress/data)
	 */
	type OriginalMapOf<T> = {
		[name: string]: T;
	};

	type OriginalCurriedState<F> = F extends (state: any, ...args: infer P) => infer R
		? (...args: P) => R
		: F;

	/**
	 * ============================================================================
	 * CUSTOM TYPE DEFINITIONS WITH GENERATOR SUPPORT
	 * ============================================================================
	 */

	/**
	 * Extract the return type from a Generator function.
	 */
	type GeneratorReturnType<T> = T extends Generator<any, infer R, any> ? R : never;

	/**
	 * Extract return type from thunk functions.
	 */
	export type ThunkReturnType<Action extends ActionCreator> = Awaited<ReturnType<ReturnType<Action>>>;

	/**
	 * Convert an action creator to its promisified form with Generator support.
	 */
	export type PromisifyActionCreator<Action extends ActionCreator> =
		(...args: Parameters<Action>) =>
			Promise<
				ReturnType<Action> extends (..._args: any[]) => any
					? ThunkReturnType<Action>
					: ReturnType<Action> extends Generator
					? GeneratorReturnType<ReturnType<Action>>
					: ReturnType<Action>
			>;

	/**
	 * Maps action creators to their promisified forms.
	 */
	export type PromisifiedActionCreators<ActionCreators extends MapOf<ActionCreator>> = {
		[Action in keyof ActionCreators]: PromisifyActionCreator<ActionCreators[Action]>;
	};

	/**
	 * Get selectors from a config.
	 */
	type SelectorsOf<Config extends AnyConfig> = Config extends ReduxStoreConfig<any, any, infer Selectors> ? {
		[name in keyof Selectors]: Selectors[name] extends (...args: any[]) => any
			? (...args: Parameters<Selectors[name]>) => ReturnType<Selectors[name]>
			: (...args: any[]) => any;
	} : never;

	/**
	 * Helper type to extract selector parameters (excluding state)
	 */
	export type SelectorParameters<S extends Selector> = S extends (state: any, ...args: infer P) => any ? P : never;

	/**
	 * Metadata actions that are automatically added to every store.
	 */
	export type TypedMetadataActions<Config extends AnyConfig> = {
		startResolution<SelectorName extends keyof SelectorsOf<Config>>(
			selectorName: SelectorName,
			args: SelectorParameters<SelectorsOf<Config>[SelectorName]>
		): Generator;
		finishResolution<SelectorName extends keyof SelectorsOf<Config>>(
			selectorName: SelectorName,
			args: SelectorParameters<SelectorsOf<Config>[SelectorName]>
		): Generator;
		failResolution<SelectorName extends keyof SelectorsOf<Config>>(
			selectorName: SelectorName,
			args: SelectorParameters<SelectorsOf<Config>[SelectorName]>,
			error: Error | unknown
		): Generator;
		startResolutions<SelectorName extends keyof SelectorsOf<Config>>(
			selectorName: SelectorName,
			args: Array<SelectorParameters<SelectorsOf<Config>[SelectorName]>>
		): Generator;
		finishResolutions<SelectorName extends keyof SelectorsOf<Config>>(
			selectorName: SelectorName,
			args: Array<SelectorParameters<SelectorsOf<Config>[SelectorName]>>
		): Generator;
		failResolutions<SelectorName extends keyof SelectorsOf<Config>>(
			selectorName: SelectorName,
			args: SelectorParameters<SelectorsOf<Config>[SelectorName]>,
			errors: (Error | unknown)[]
		): Generator;
		invalidateResolution<SelectorName extends keyof SelectorsOf<Config>>(
			selectorName: SelectorName,
			args?: SelectorParameters<SelectorsOf<Config>[SelectorName]>
		): void;
		invalidateResolutionForStore(): void;
		invalidateResolutionForStoreSelector<SelectorName extends keyof SelectorsOf<Config>>(
			selectorName: SelectorName,
			args?: SelectorParameters<SelectorsOf<Config>[SelectorName]>
		): void;
	};

	/**
	 * Metadata selectors that are automatically added to every store.
	 */
	export type TypedMetadataSelectors<Config extends AnyConfig> = {
		getResolutionState<SelectorName extends keyof SelectorsOf<Config>>(
			selectorName: SelectorName,
			args?: SelectorParameters<SelectorsOf<Config>[SelectorName]> | null
		): any;
		getIsResolving<SelectorName extends keyof SelectorsOf<Config>>(
			selectorName: SelectorName,
			args?: SelectorParameters<SelectorsOf<Config>[SelectorName]>
		): boolean;
		hasStartedResolution<SelectorName extends keyof SelectorsOf<Config>>(
			selectorName: SelectorName,
			args?: SelectorParameters<SelectorsOf<Config>[SelectorName]>
		): boolean;
		hasFinishedResolution<SelectorName extends keyof SelectorsOf<Config>>(
			selectorName: SelectorName,
			args?: SelectorParameters<SelectorsOf<Config>[SelectorName]>
		): boolean;
		hasResolutionFailed<SelectorName extends keyof SelectorsOf<Config>>(
			selectorName: SelectorName,
			args?: SelectorParameters<SelectorsOf<Config>[SelectorName]>
		): boolean;
		getResolutionError<SelectorName extends keyof SelectorsOf<Config>>(
			selectorName: SelectorName,
			args?: SelectorParameters<SelectorsOf<Config>[SelectorName]>
		): Error | unknown | undefined;
		isResolving<SelectorName extends keyof SelectorsOf<Config>>(
			selectorName: SelectorName,
			args?: SelectorParameters<SelectorsOf<Config>[SelectorName]>
		): boolean;
		getCachedResolvers(): unknown;
		hasResolvingSelectors(): boolean;
		countSelectorsByStatus(): { resolving: number; finished: number; error: number };
	};

	/**
	 * Get action creators from a store config, INCLUDING metadata actions.
	 */
	export type ActionCreatorsOf<Config extends AnyConfig> =
		Config extends ReduxStoreConfig<any, infer ActionCreators, any>
			? PromisifiedActionCreators<ActionCreators & TypedMetadataActions<Config>>
			: never;

	/**
	 * Get selectors from a store, INCLUDING metadata selectors.
	 */
	export type ConfigOf<S> = S extends StoreDescriptor<infer C> ? C : never;

	export type CurriedSelectorsOf<S> = S extends StoreDescriptor<ReduxStoreConfig<any, any, infer Selectors>> ? {
		[key in keyof Selectors]: CurriedState<Selectors[key]>;
	} & TypedMetadataSelectors<ConfigOf<S>> : never;

	/**
	 * PromiseifySelectors for resolveSelect
	 */
	export type PromiseifySelectors<Selectors> = {
		[SelectorFunction in keyof Selectors]: Selectors[SelectorFunction] extends (
			...args: infer SelectorArgs
		) => infer SelectorReturnType
			? (...args: SelectorArgs) => Promise<SelectorReturnType>
			: never;
	};

	/**
	 * Override dispatch() to use our custom ActionCreatorsOf type
	 * Multiple overloads for better type inference in different contexts
	 */
	export function dispatch<T extends StoreDescriptor<AnyConfig>>(
		storeDescriptor: T
	): T extends StoreDescriptor<infer Config extends AnyConfig> ? ActionCreatorsOf<Config> : any;
	export function dispatch(
		storeDescriptor: string
	): any;

	/**
	 * Override select() to use our custom CurriedSelectorsOf type
	 * Multiple overloads for better type inference in different contexts
	 */
	export function select<T extends StoreDescriptor<AnyConfig>>(
		storeDescriptor: T
	): CurriedSelectorsOf<T>;
	export function select(
		storeDescriptor: string
	): any;

	/**
	 * Override resolveSelect to use our custom types
	 * Multiple overloads for better type inference in different contexts
	 */
	export function resolveSelect<T extends StoreDescriptor<AnyConfig>>(
		storeDescriptor: T
	): PromiseifySelectors<CurriedSelectorsOf<T>>;
	export function resolveSelect(
		storeDescriptor: string
	): any;

	/**
	 * suspendSelect (similar to resolveSelect but throws promises)
	 */
	export const suspendSelect: typeof resolveSelect;

	/**
	 * ============================================================================
	 * OVERRIDE REACT HOOKS
	 * ============================================================================
	 */

	/**
	 * Helper types for hooks
	 */
	type DispatchFunction = <T extends StoreDescriptor<AnyConfig>>(
		storeDescriptor: string | T
	) => T extends StoreDescriptor<infer Config extends AnyConfig> ? ActionCreatorsOf<Config> : any;

	/**
	 * Type for the select function parameter in useSelect callbacks
	 * Using a more explicit interface to help TypeScript resolve overloads
	 */
	interface SelectFunction {
		<T extends StoreDescriptor<AnyConfig>>(storeDescriptor: T): CurriedSelectorsOf<T>;
		(storeDescriptor: string): any;
	}

	type MapSelect = (select: SelectFunction, registry: any) => any;

	export type UseDispatchReturn<StoreNameOrDescriptor> =
		StoreNameOrDescriptor extends StoreDescriptor<any>
			? ActionCreatorsOf<ConfigOf<StoreNameOrDescriptor>>
			: StoreNameOrDescriptor extends undefined
			? DispatchFunction
			: any;

	export type UseSelectReturn<F extends MapSelect | StoreDescriptor<any>> =
		F extends MapSelect
			? ReturnType<F>
			: F extends StoreDescriptor<any>
			? CurriedSelectorsOf<F>
			: never;

	/**
	 * Override useDispatch hook
	 */
	export function useDispatch<StoreNameOrDescriptor extends string | StoreDescriptor<any> | undefined>(
		storeNameOrDescriptor?: StoreNameOrDescriptor
	): UseDispatchReturn<StoreNameOrDescriptor>;

	/**
	 * Override useSelect hook
	 */
	export function useSelect<F extends MapSelect | StoreDescriptor<any>>(
		mapSelect: F,
		deps?: any[]
	): UseSelectReturn<F>;

	/**
	 * useSuspenseSelect (similar to useSelect but suspends)
	 */
	export const useSuspenseSelect: typeof useSelect;

	/**
	 * Re-export types
	 * We use type aliases to properly export the imported types
	 */
	export type StoreDescriptor<Config extends AnyConfig = AnyConfig> = OriginalStoreDescriptor<Config>;
	export type AnyConfig = OriginalAnyConfig;
	export type ReduxStoreConfig<State, ActionCreators extends MapOf<ActionCreator>, Selectors> = OriginalReduxStoreConfig<State, ActionCreators, Selectors>;
	export type MapOf<T> = OriginalMapOf<T>;
	export type ActionCreator = OriginalActionCreator;
	export type CurriedState<F> = OriginalCurriedState<F>;
	export type Selector = OriginalSelector;
}
