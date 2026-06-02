declare module '@wordpress/data/build-types/types' {
	/**
	 * External dependencies
	 */
	import type { combineReducers as reduxCombineReducers } from 'redux';
	type MapOf<T> = {
	    [name: string]: T;
	};
	export type ActionCreator = (...args: any[]) => any | Generator;
	export type Resolver = Function | Generator;
	export type Selector = Function;
	export type AnyConfig = ReduxStoreConfig<any, any, any>;
	export interface StoreInstance<Config extends AnyConfig> {
	    getSelectors: () => SelectorsOf<Config>;
	    getActions: () => ActionCreatorsOf<Config>;
	    subscribe: (listener: () => void) => () => void;
	}
	export interface StoreDescriptor<Config extends AnyConfig = AnyConfig> {
	    /**
	     * Store Name
	     */
	    name: string;
	    /**
	     * Creates a store instance
	     */
	    instantiate: (registry: DataRegistry) => StoreInstance<Config>;
	}
	export interface ReduxStoreConfig<State, ActionCreators, Selectors> {
	    initialState?: State;
	    reducer: (state: any, action: any) => any;
	    actions?: ActionCreators;
	    resolvers?: MapOf<Resolver>;
	    selectors?: Selectors;
	    controls?: MapOf<Function>;
	}
	export type UseSelectReturn<F extends MapSelect | StoreDescriptor<any>> = F extends MapSelect ? ReturnType<F> : F extends StoreDescriptor<any> ? CurriedSelectorsOf<F> : never;
	export type UseDispatchReturn<StoreNameOrDescriptor> =
	    StoreNameOrDescriptor extends StoreDescriptor<any> ? ActionCreatorsOf<StoreNameOrDescriptor> :
	    StoreNameOrDescriptor extends keyof StoreRegistry ? ActionCreatorsOf<StoreRegistry[StoreNameOrDescriptor]> :
	    StoreNameOrDescriptor extends undefined ? DispatchFunction :
	    any;
	export interface DispatchFunction {
	    <S extends StoreDescriptor<any>>(store: S): ActionCreatorsOf<S>;
	    <K extends keyof StoreRegistry>(store: K): ActionCreatorsOf<StoreRegistry[K]>;
	    (store: StoreNameOrDescriptor): ActionCreatorsOf<StoreDescriptor>;
	}
	export type DispatchReturn<StoreNameOrDescriptor> = StoreNameOrDescriptor extends StoreDescriptor<any> ? ActionCreatorsOf<ConfigOf<StoreNameOrDescriptor>> : unknown;
	export type MapSelect = (select: SelectFunction, registry: DataRegistry) => any;
	export interface SelectFunction {
	    <S extends StoreDescriptor<any>>(store: S): CurriedSelectorsOf<S>;
	    <K extends keyof StoreRegistry>(store: K): CurriedSelectorsOf<StoreRegistry[K]>;
	    (store: StoreNameOrDescriptor): CurriedSelectorsOf<StoreDescriptor>;
	}
	/**
	 * Callback for store's `subscribe()` method that
	 * runs when the store data has changed.
	 */
	export type ListenerFunction = () => void;
	export type CurriedSelectorsOf<S> = S extends StoreDescriptor<ReduxStoreConfig<any, any, infer Selectors>> ? {
	    [key in keyof Selectors]: CurriedState<Selectors[key]>;
	} & MetadataSelectors<S> : never;
	/**
	 * Like CurriedState but wraps the return type in a Promise.
	 * Used for resolveSelect where selectors return promises.
	 */
	type CurriedStateWithPromise<F> = F extends SelectorWithCustomCurrySignature & {
	    PromiseCurriedSignature: infer S;
	} ? S : F extends SelectorWithCustomCurrySignature & {
	    CurriedSignature: (...args: infer P) => infer R;
	} ? (...args: P) => Promise<R> : F extends (state: any, ...args: infer P) => infer R ? (...args: P) => Promise<R> : F;
	/**
	 * Like CurriedSelectorsOf but each selector returns a Promise.
	 * Used for resolveSelect.
	 */
	export type CurriedSelectorsResolveOf<S> = S extends StoreDescriptor<ReduxStoreConfig<any, any, infer Selectors>> ? {
	    [key in keyof Selectors]: CurriedStateWithPromise<Selectors[key]>;
	} : never;
	/**
	 * Removes the first argument from a function.
	 *
	 * By default, it removes the `state` parameter from
	 * registered selectors since that argument is supplied
	 * by the editor when calling `select(…)`.
	 *
	 * For functions with no arguments, which some selectors
	 * are free to define, returns the original function.
	 *
	 * It is possible to manually provide a custom curried signature
	 * and avoid the automatic inference. When the
	 * F generic argument passed to this helper extends the
	 * SelectorWithCustomCurrySignature type, the F['CurriedSignature']
	 * property is used verbatim.
	 *
	 * This is useful because TypeScript does not correctly remove
	 * arguments from complex function signatures constrained by
	 * interdependent generic parameters.
	 * For more context, see https://github.com/WordPress/gutenberg/pull/41578
	 */
	type CurriedState<F> = F extends SelectorWithCustomCurrySignature ? F['CurriedSignature'] : F extends (state: any, ...args: infer P) => infer R ? (...args: P) => R : F;
	/**
	 * Utility to manually specify curried selector signatures.
	 *
	 * It comes handy when TypeScript can't automatically produce the
	 * correct curried function signature. For example:
	 *
	 * ```ts
	 * type BadlyInferredSignature = CurriedState<
	 *     <K extends string | number>(
	 *         state: any,
	 *         kind: K,
	 *         key: K extends string ? 'one value' : false
	 *     ) => K
	 * >
	 * // BadlyInferredSignature evaluates to:
	 * // (kind: string number, key: false "one value") => string number
	 * ```
	 *
	 * With SelectorWithCustomCurrySignature, we can provide a custom
	 * signature and avoid relying on TypeScript inference:
	 * ```ts
	 * interface MySelectorSignature extends SelectorWithCustomCurrySignature {
	 *     <K extends string | number>(
	 *         state: any,
	 *         kind: K,
	 *         key: K extends string ? 'one value' : false
	 *     ): K;
	 *
	 *     CurriedSignature: <K extends string | number>(
	 *         kind: K,
	 *         key: K extends string ? 'one value' : false
	 *     ): K;
	 * }
	 * type CorrectlyInferredSignature = CurriedState<MySelectorSignature>
	 * // <K extends string | number>(kind: K, key: K extends string ? 'one value' : false): K;
	 *
	 * For even more context, see https://github.com/WordPress/gutenberg/pull/41578
	 * ```
	 */
	export interface SelectorWithCustomCurrySignature {
	    CurriedSignature: Function;
	    PromiseCurriedSignature?: Function;
	}
	/**
	 * An augmentable mapping of store names to their store descriptors.
	 *
	 * Packages that register stores can augment this interface so that
	 * string-based `select`, `dispatch`, `resolveSelect`, and `suspendSelect`
	 * calls are fully typed without needing to import the store descriptor.
	 */
	export interface StoreRegistry {}
	/**
	 * A store name or store descriptor, used throughout the API.
	 */
	export type StoreNameOrDescriptor = string | StoreDescriptor;
	export interface DataRegistry {
	    register: (store: StoreDescriptor<any>) => void;
	}
	/**
	 * Status of a selector resolution.
	 */
	export type ResolutionStatus = 'resolving' | 'finished' | 'error';
	/**
	 * State value for a single resolution.
	 */
	export type ResolutionState = {
	    status: 'resolving';
	} | {
	    status: 'finished';
	} | {
	    status: 'error';
	    error: Error | unknown;
	};
	/**
	 * A normalized resolver with a `fulfill` method and optional `isFulfilled`.
	 */
	export interface NormalizedResolver {
	    fulfill: (...args: any[]) => any;
	    isFulfilled?: (state: any, ...args: any[]) => boolean;
	    shouldInvalidate?: (action: any, ...args: any[]) => boolean;
	}
	/**
	 * A bound selector with optional resolver metadata.
	 */
	export interface BoundSelector {
	    (...args: any[]): any;
	    hasResolver: boolean;
	    __unstableNormalizeArgs?: (args: any[]) => any[];
	    isRegistrySelector?: boolean;
	    registry?: DataRegistry;
	}
	/**
	 * The shape of a store instance as seen internally by the registry.
	 */
	export interface InternalStoreInstance<Config extends AnyConfig = AnyConfig> extends StoreInstance<Config> {
	    store?: any;
	    emitter: {
	        subscribe: (listener: () => void) => () => void;
	        emit: () => void;
	        pause: () => void;
	        resume: () => void;
	        isPaused: boolean;
	    };
	    reducer?: (state: any, action: any) => any;
	    actions?: Record<string, ActionCreator>;
	    selectors?: Record<string, Selector>;
	    resolvers?: Record<string, NormalizedResolver>;
	    getResolveSelectors?: () => Record<string, (...args: any[]) => Promise<any>>;
	    getSuspendSelectors?: () => Record<string, (...args: any[]) => any>;
	}
	/**
	 * Control descriptor for the controls system.
	 */
	export interface ControlDescriptor {
	    type: string;
	    storeKey: string;
	    selectorName?: string;
	    actionName?: string;
	    args: any[];
	}
	export type ConfigOf<S> = S extends StoreDescriptor<infer C> ? C : never;
	export type ActionCreatorsOf<T> = T extends StoreDescriptor<ReduxStoreConfig<any, infer ActionCreators, any>>
	    ? PromisifiedActionCreators<ActionCreators> & MetadataActions<T>
	    : T extends ReduxStoreConfig<any, infer ActionCreators, any>
	    ? PromisifiedActionCreators<ActionCreators>
	    : never;
	export type PromisifiedActionCreators<ActionCreators> = {
	    [Action in keyof ActionCreators]: ActionCreators[Action] extends ActionCreator
	        ? PromisifyActionCreator<ActionCreators[Action]>
	        : ActionCreators[Action];
	};
	export type PromisifyActionCreator<Action extends ActionCreator> = (...args: Parameters<Action>) => Promise<
	    ReturnType<Action> extends (..._args: any[]) => any
	        ? ThunkReturnType<Action>
	        : ReturnType<Action> extends Generator<any, infer TReturn, any>
	        ? TReturn
	        : ReturnType<Action>
	>;
	export type ThunkReturnType<Action extends ActionCreator> = Awaited<ReturnType<ReturnType<Action>>>;
	type SelectorsOf<Config extends AnyConfig> = Config extends ReduxStoreConfig<any, any, infer Selectors> ? {
	    [name in keyof Selectors]: Function;
	} : never;
	/**
	 * Extracts selector key names from a store descriptor.
	 */
	export type SelectorKeysOf<S> = S extends StoreDescriptor<ReduxStoreConfig<any, any, infer Selectors>>
	    ? string & keyof Selectors
	    : string;
	/**
	 * Metadata selectors injected into every Redux store.
	 */
	export type MetadataSelectors<S = unknown> = {
	    getResolutionState: (selectorName: SelectorKeysOf<S>, args?: unknown[] | null) => ResolutionState | undefined;
	    getIsResolving: (selectorName: SelectorKeysOf<S>, args?: unknown[] | null) => boolean | undefined;
	    hasStartedResolution: (selectorName: SelectorKeysOf<S>, args?: unknown[] | null) => boolean;
	    hasFinishedResolution: (selectorName: SelectorKeysOf<S>, args?: unknown[] | null) => boolean;
	    hasResolutionFailed: (selectorName: SelectorKeysOf<S>, args?: unknown[] | null) => boolean;
	    getResolutionError: (selectorName: SelectorKeysOf<S>, args?: unknown[] | null) => Error | unknown;
	    isResolving: (selectorName: SelectorKeysOf<S>, args?: unknown[] | null) => boolean;
	    getCachedResolvers: () => Record<string, unknown>;
	    hasResolvingSelectors: () => boolean;
	    countSelectorsByStatus: () => Record<string, number>;
	};
	/**
	 * Metadata actions injected into every Redux store.
	 */
	export type MetadataActions<S = unknown> = {
	    startResolution: (selectorName: SelectorKeysOf<S>, args: unknown[]) => Promise<void>;
	    finishResolution: (selectorName: SelectorKeysOf<S>, args: unknown[]) => Promise<void>;
	    failResolution: (selectorName: SelectorKeysOf<S>, args: unknown[], error: Error | unknown) => Promise<void>;
	    startResolutions: (selectorName: SelectorKeysOf<S>, args: unknown[][]) => Promise<void>;
	    finishResolutions: (selectorName: SelectorKeysOf<S>, args: unknown[][]) => Promise<void>;
	    failResolutions: (selectorName: SelectorKeysOf<S>, args: unknown[], errors: (Error | unknown)[]) => Promise<void>;
	    invalidateResolution: (selectorName: SelectorKeysOf<S>, args: unknown[]) => Promise<void>;
	    invalidateResolutionForStore: () => Promise<void>;
	    invalidateResolutionForStoreSelector: (selectorName: SelectorKeysOf<S>) => Promise<void>;
	};
	/**
	 * The argument object passed to every thunk function.
	 */
	export interface ThunkArgs<S extends StoreDescriptor = StoreDescriptor> {
	    dispatch: ActionCreatorsOf<S> & ((action: Record<string, unknown> | Function) => unknown);
	    select: CurriedSelectorsOf<S>;
	    resolveSelect: CurriedSelectorsResolveOf<S>;
	    registry: DataRegistry;
	}
	export type combineReducers = typeof reduxCombineReducers;
	export {};
}
