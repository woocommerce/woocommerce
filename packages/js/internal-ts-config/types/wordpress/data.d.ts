declare module '@wordpress/data' {
	export { default as withSelect } from '@wordpress/data/build-types/components/with-select';
	export { default as withDispatch } from '@wordpress/data/build-types/components/with-dispatch';
	export { default as withRegistry } from '@wordpress/data/build-types/components/with-registry';

	export {
		RegistryProvider,
		RegistryConsumer,
	} from '@wordpress/data/build-types/components/registry-provider';
	export { AsyncModeProvider } from '@wordpress/data/build-types/components/async-mode-provider';

	export { createRegistry } from '@wordpress/data/build-types/registry';
	export { createSelector } from '@wordpress/data/build-types/create-selector';
	export { controls } from '@wordpress/data/build-types/controls';
	export { default as createReduxStore } from '@wordpress/data/build-types/redux-store';
	export { createRegistryControl } from '@wordpress/data/build-types/factory';

	export const combineReducers: import('@wordpress/data/build-types/types').combineReducers;
	export const subscribe: Function;
	export const registerGenericStore: Function;
	export const registerStore: Function;
	export const use: any;
	export const register: any;
	export const plugins: any;

	/**
	 * These are the base types we intend to extend.
	 */
	import type {
		StoreDescriptor as OriginalStoreDescriptor,
		AnyConfig as OriginalAnyConfig,
		ReduxStoreConfig as OriginalReduxStoreConfig,
		ActionCreator as OriginalActionCreator,
		Selector as OriginalSelector,
		SelectorWithCustomCurrySignature,
	} from '@wordpress/data/build-types/types';

	/**
	 * CurriedState - removes the first argument (state) from a selector.
	 * If the selector has a CurriedSignature property, use that instead.
	 * (Copied from @wordpress/data since it's not exported)
	 */
	type CurriedState< F > = F extends SelectorWithCustomCurrySignature
		? F[ 'CurriedSignature' ]
		: F extends ( state: any, ...args: infer P ) => infer R
		? ( ...args: P ) => R
		: F;

	import * as MetadataActions from '@wordpress/data/build-types/redux-store/metadata/actions';
	import * as MetadataSelectors from '@wordpress/data/build-types/redux-store/metadata/selectors';

	/**
	 * Internal helper types (not exported by @wordpress/data)
	 */
	type OriginalMapOf< T > = {
		[ name: string ]: T;
	};

	/**
	 * Extract the return type from a Generator function.
	 */
	type GeneratorReturnType< T > = T extends Generator< any, infer R, any >
		? R
		: never;

	/**
	 * Extract return type from thunk functions.
	 */
	export type ThunkReturnType< Action extends ActionCreator > = Awaited<
		ReturnType< ReturnType< Action > >
	>;

	/**
	 * Convert an action creator to its promisified form with Generator support.
	 */
	export type PromisifyActionCreator< Action extends ActionCreator > = (
		...args: Parameters< Action >
	) => Promise<
		ReturnType< Action > extends ( ..._args: any[] ) => any
			? ThunkReturnType< Action >
			: ReturnType< Action > extends Generator
			? GeneratorReturnType< ReturnType< Action > >
			: ReturnType< Action >
	>;

	/**
	 * Maps action creators to their promisified forms.
	 */
	export type PromisifiedActionCreators<
		ActionCreators extends MapOf< ActionCreator >
	> = {
		[ Action in keyof ActionCreators ]: PromisifyActionCreator<
			ActionCreators[ Action ]
		>;
	};

	/**
	 * Get selectors from a config.
	 */
	type SelectorsOf< Config extends AnyConfig > =
		Config extends ReduxStoreConfig< any, any, infer Selectors >
			? {
					[ name in keyof Selectors ]: Selectors[ name ] extends (
						...args: any[]
					) => any
						? (
								...args: Parameters< Selectors[ name ] >
						  ) => ReturnType< Selectors[ name ] >
						: ( ...args: any[] ) => any;
			  }
			: never;

	/**
	 * Helper type to extract selector parameters (excluding state)
	 */
	export type SelectorParameters< S extends Selector > = S extends (
		state: any,
		...args: infer P
	) => any
		? P
		: never;

	/**
	 * Metadata actions that are automatically added to every store.
	 */
	export type TypedMetadataActions< Config extends AnyConfig > = {
		startResolution< SelectorName extends keyof SelectorsOf< Config > >(
			selectorName: SelectorName,
			args: SelectorParameters< SelectorsOf< Config >[ SelectorName ] >
		): Generator;
		finishResolution< SelectorName extends keyof SelectorsOf< Config > >(
			selectorName: SelectorName,
			args: SelectorParameters< SelectorsOf< Config >[ SelectorName ] >
		): Generator;
		failResolution< SelectorName extends keyof SelectorsOf< Config > >(
			selectorName: SelectorName,
			args: SelectorParameters< SelectorsOf< Config >[ SelectorName ] >,
			error: Error | unknown
		): Generator;
		startResolutions< SelectorName extends keyof SelectorsOf< Config > >(
			selectorName: SelectorName,
			args: Array<
				SelectorParameters< SelectorsOf< Config >[ SelectorName ] >
			>
		): Generator;
		finishResolutions< SelectorName extends keyof SelectorsOf< Config > >(
			selectorName: SelectorName,
			args: Array<
				SelectorParameters< SelectorsOf< Config >[ SelectorName ] >
			>
		): Generator;
		failResolutions< SelectorName extends keyof SelectorsOf< Config > >(
			selectorName: SelectorName,
			args: SelectorParameters< SelectorsOf< Config >[ SelectorName ] >,
			errors: ( Error | unknown )[]
		): Generator;
		invalidateResolution<
			SelectorName extends keyof SelectorsOf< Config >
		>(
			selectorName: SelectorName,
			args?: SelectorParameters< SelectorsOf< Config >[ SelectorName ] >
		): void;
		invalidateResolutionForStore(): void;
		invalidateResolutionForStoreSelector<
			SelectorName extends keyof SelectorsOf< Config >
		>(
			selectorName: SelectorName,
			args?: SelectorParameters< SelectorsOf< Config >[ SelectorName ] >
		): void;
	};

	/**
	 * Metadata selectors that are automatically added to every store.
	 */
	export type TypedMetadataSelectors< Config extends AnyConfig > = {
		getResolutionState< SelectorName extends keyof SelectorsOf< Config > >(
			selectorName: SelectorName,
			args?: SelectorParameters<
				SelectorsOf< Config >[ SelectorName ]
			> | null
		): any;
		getIsResolving< SelectorName extends keyof SelectorsOf< Config > >(
			selectorName: SelectorName,
			args?: SelectorParameters< SelectorsOf< Config >[ SelectorName ] >
		): boolean;
		hasStartedResolution<
			SelectorName extends keyof SelectorsOf< Config >
		>(
			selectorName: SelectorName,
			args?: SelectorParameters< SelectorsOf< Config >[ SelectorName ] >
		): boolean;
		hasFinishedResolution<
			SelectorName extends keyof SelectorsOf< Config >
		>(
			selectorName: SelectorName,
			args?: SelectorParameters< SelectorsOf< Config >[ SelectorName ] >
		): boolean;
		hasResolutionFailed< SelectorName extends keyof SelectorsOf< Config > >(
			selectorName: SelectorName,
			args?: SelectorParameters< SelectorsOf< Config >[ SelectorName ] >
		): boolean;
		getResolutionError< SelectorName extends keyof SelectorsOf< Config > >(
			selectorName: SelectorName,
			args?: SelectorParameters< SelectorsOf< Config >[ SelectorName ] >
		): Error | unknown | undefined;
		isResolving< SelectorName extends keyof SelectorsOf< Config > >(
			selectorName: SelectorName,
			args?: SelectorParameters< SelectorsOf< Config >[ SelectorName ] >
		): boolean;
		getCachedResolvers(): unknown;
		hasResolvingSelectors(): boolean;
		countSelectorsByStatus(): {
			resolving: number;
			finished: number;
			error: number;
		};
	};

	/**
	 * Get action creators from a store config, INCLUDING metadata actions.
	 */
	export type ActionCreatorsOf< Config extends AnyConfig > =
		Config extends ReduxStoreConfig< any, infer ActionCreators, any >
			? keyof ActionCreators extends never
				? // If action creators are empty (e.g., store typed as `any`), fall back to `any`
				  // to allow module augmentations from @types/wordpress__* packages to work
				  any
				: PromisifiedActionCreators<
						ActionCreators & TypedMetadataActions< Config >
				  >
			: never;

	/**
	 * Get selectors from a store, INCLUDING metadata selectors.
	 */
	export type ConfigOf< S > = S extends StoreDescriptor< infer C >
		? C
		: never;

	export type CurriedSelectorsOf< S > = S extends StoreDescriptor<
		ReduxStoreConfig< any, any, infer Selectors >
	>
		? keyof Selectors extends never
			? // If selectors are empty (e.g., store typed as `any`), fall back to `any`
			  // to allow module augmentations from @types/wordpress__* packages to work
			  any
			: {
					[ key in keyof Selectors ]: CurriedState<
						Selectors[ key ]
					>;
			  } & TypedMetadataSelectors< ConfigOf< S > > // Already curried, no CurriedState needed
		: never;

	/**
	 * Helper to remove first argument (state) from a selector
	 */
	type RemoveFirstArgument< F > = F extends (
		state: any,
		...args: infer P
	) => infer R
		? ( ...args: P ) => R
		: F;

	/**
	 * Helper to curry and promisify a selector
	 *
	 * CURRENT STATE (Gutenberg < 22.4):
	 * - Generic type parameters cannot be preserved through this transformation
	 * - TypeScript's `infer` keyword loses generic type information
	 * - Example: `getEntityRecord<Settings>()` becomes `Promise<unknown>`
	 * - Workaround: Use type assertions like `await resolveSelect(store).getEntityRecord() as Settings`
	 *
	 * FUTURE STATE (Gutenberg >= 22.4 with PR #73973):
	 * - Selectors will have a `PromiseCurriedSignature` property that preserves generics
	 * - This type is ready to use it once available
	 * - Example: `resolveSelect(store).getEntityRecord<Settings>('root', 'site')` → `Promise<Settings>`
	 *
	 * The type checks for PromiseCurriedSignature first, then falls back to transformation.
	 */
	type CurriedAndPromisifiedSelector< F > =
		F extends SelectorWithCustomCurrySignature & {
			PromiseCurriedSignature: infer S;
		}
			? S // Use PromiseCurriedSignature directly - preserves generics!
			: F extends SelectorWithCustomCurrySignature
			? F[ 'CurriedSignature' ] extends ( ...args: any[] ) => infer Ret
				? (
						...args: Parameters< F[ 'CurriedSignature' ] >
				  ) => Promise< Ret >
				: never
			: RemoveFirstArgument< F > extends ( ...args: any[] ) => infer Ret
			? (
					...args: Parameters< RemoveFirstArgument< F > >
			  ) => Promise< Ret >
			: never;

	/**
	 * Helper to promisify metadata selectors while preserving optional parameters
	 */
	type PromisifyMetadataSelector< F > = F extends (
		...args: any[]
	) => infer Ret
		? ( ...args: Parameters< F > ) => Promise< Ret >
		: never;

	/**
	 * Curried and promisified selectors for resolveSelect
	 */
	export type CurriedAndPromisifiedSelectorsOf< S > =
		S extends StoreDescriptor<
			ReduxStoreConfig< any, any, infer Selectors >
		>
			? keyof Selectors extends never
				? // If selectors are empty (e.g., store typed as `any`), fall back to `any`
				  // to allow module augmentations from @types/wordpress__* packages to work
				  any
				: {
						[ key in keyof Selectors ]: CurriedAndPromisifiedSelector<
							Selectors[ key ]
						>;
				  } & {
						[ key in keyof TypedMetadataSelectors<
							ConfigOf< S >
						> ]: PromisifyMetadataSelector<
							TypedMetadataSelectors< ConfigOf< S > >[ key ]
						>;
				  }
			: never;

	/**
	 * Override dispatch() to use our custom ActionCreatorsOf type.
	 * Supports three modes:
	 * 1. StoreDescriptor → full typing
	 * 2. Registered string (in StoreRegistry) → full typing
	 * 3. Unknown string → `any` fallback
	 */
	export function dispatch< T extends StoreDescriptor< AnyConfig > >(
		storeDescriptor: T
	): T extends StoreDescriptor< infer Config extends AnyConfig >
		? ActionCreatorsOf< Config >
		: any;
	export function dispatch< K extends keyof StoreRegistry >(
		storeDescriptor: K
	): ActionCreatorsOf< ConfigOf< StoreRegistry[ K ] > >;
	export function dispatch( storeDescriptor: string ): any;

	/**
	 * Override select() to use our custom CurriedSelectorsOf type.
	 * Supports three modes:
	 * 1. StoreDescriptor → full typing
	 * 2. Registered string (in StoreRegistry) → full typing
	 * 3. Unknown string → `any` fallback
	 */
	export function select< T extends StoreDescriptor< AnyConfig > >(
		storeDescriptor: T
	): CurriedSelectorsOf< T >;
	export function select< K extends keyof StoreRegistry >(
		storeDescriptor: K
	): CurriedSelectorsOf< StoreRegistry[ K ] >;
	export function select( storeDescriptor: string ): any;

	/**
	 * Override resolveSelect() to use our custom types.
	 * Supports three modes:
	 * 1. StoreDescriptor → full typing
	 * 2. Registered string (in StoreRegistry) → full typing
	 * 3. Unknown string → `any` fallback
	 */
	export function resolveSelect< T extends StoreDescriptor< AnyConfig > >(
		storeDescriptor: T
	): CurriedAndPromisifiedSelectorsOf< T >;
	export function resolveSelect< K extends keyof StoreRegistry >(
		storeDescriptor: K
	): CurriedAndPromisifiedSelectorsOf< StoreRegistry[ K ] >;
	export function resolveSelect( storeDescriptor: string ): any;

	/**
	 * suspendSelect (similar to resolveSelect but throws promises)
	 */
	export const suspendSelect: typeof resolveSelect;

	/**
	 * Type for the dispatch function returned by useDispatch() with no arguments.
	 * Supports StoreDescriptor, registered strings, and unknown strings.
	 */
	interface DispatchFunction {
		< T extends StoreDescriptor< AnyConfig > >(
			storeDescriptor: T
		): ActionCreatorsOf< ConfigOf< T > >;
		< K extends keyof StoreRegistry >(
			storeDescriptor: K
		): ActionCreatorsOf< ConfigOf< StoreRegistry[ K ] > >;
		( storeDescriptor: string ): any;
	}

	/**
	 * Type for the select function parameter in useSelect callbacks.
	 * Supports StoreDescriptor, registered strings, and unknown strings.
	 */
	interface SelectFunction {
		< T extends StoreDescriptor< AnyConfig > >(
			storeDescriptor: T
		): CurriedSelectorsOf< T >;
		< K extends keyof StoreRegistry >(
			storeDescriptor: K
		): CurriedSelectorsOf< StoreRegistry[ K ] >;
		( storeDescriptor: string ): any;
	}

	type MapSelect = ( select: SelectFunction, registry: any ) => any;

	export type UseDispatchReturn< StoreNameOrDescriptor > =
		StoreNameOrDescriptor extends StoreDescriptor< any >
			? ActionCreatorsOf< ConfigOf< StoreNameOrDescriptor > >
			: StoreNameOrDescriptor extends keyof StoreRegistry
			? ActionCreatorsOf<
					ConfigOf< StoreRegistry[ StoreNameOrDescriptor ] >
			  >
			: StoreNameOrDescriptor extends undefined
			? DispatchFunction
			: any;

	export type UseSelectReturn<
		F extends MapSelect | StoreDescriptor< any >
	> = F extends MapSelect
		? ReturnType< F >
		: F extends StoreDescriptor< any >
		? CurriedSelectorsOf< F >
		: never;

	export function useDispatch<
		StoreNameOrDescriptor extends
			| string
			| StoreDescriptor< any >
			| undefined
	>(
		storeNameOrDescriptor?: StoreNameOrDescriptor
	): UseDispatchReturn< StoreNameOrDescriptor >;

	export function useSelect< F extends MapSelect | StoreDescriptor< any > >(
		mapSelect: F,
		deps?: any[]
	): UseSelectReturn< F >;

	export const useSuspenseSelect: typeof useSelect;

	export type StoreDescriptor< Config extends AnyConfig = AnyConfig > =
		OriginalStoreDescriptor< Config >;
	export type AnyConfig = OriginalAnyConfig;
	export type ReduxStoreConfig<
		State,
		ActionCreators extends MapOf< ActionCreator >,
		Selectors
	> = OriginalReduxStoreConfig< State, ActionCreators, Selectors >;
	export type MapOf< T > = OriginalMapOf< T >;
	export type ActionCreator = OriginalActionCreator;
	export { CurriedState };
	export type Selector = OriginalSelector;

	export interface SelectorWithCustomCurrySignature {
		CurriedSignature: Function;
		PromiseCurriedSignature?: Function;
	}

	/**
	 * Registry object returned by useRegistry().
	 * Provides access to store operations and batching.
	 */
	export interface DataRegistry {
		select: typeof select;
		dispatch: typeof dispatch;
		resolveSelect: typeof resolveSelect;
		subscribe: ( listener: () => void ) => () => void;
		batch: ( callback: () => void ) => void;
	}

	export function useRegistry(): DataRegistry;
	export function createRegistrySelector<
		T extends ( state: any, ...args: any[] ) => any
	>( registrySelector: ( select: SelectFunction ) => T ): T;
}
