/* eslint-disable @typescript-eslint/no-explicit-any */

// Type for the basic selectors built into @wordpress/data, note these
// types define the interface for the public selectors, so state is not an
// argument.
// [wp.data.getSelectors](https://github.com/WordPress/gutenberg/blob/319deee5f4d4838d6bc280e9e2be89c7f43f2509/packages/data/src/store/index.js#L16-L20)
// [selector.js](https://github.com/WordPress/gutenberg/blob/trunk/packages/data/src/redux-store/metadata/selectors.js#L48-L52)
export type WPDataSelectors = {
	getIsResolving: ( selector: string, args?: unknown[] ) => boolean;
	hasStartedResolution: ( selector: string, args?: unknown[] ) => boolean;
	hasFinishedResolution: ( selector: string, args?: unknown[] ) => boolean;
	isResolving: ( selector: string, args?: unknown[] ) => boolean;
	getCachedResolvers: () => unknown;
};

// [wp.data.getActions](https://github.com/WordPress/gutenberg/blob/319deee5f4d4838d6bc280e9e2be89c7f43f2509/packages/data/src/store/index.js#L31-L35)
// [actions.js](https://github.com/WordPress/gutenberg/blob/aa2bed9010aa50467cb43063e370b70a91591e9b/packages/data/src/redux-store/metadata/actions.js)
export type WPDataActions = {
	startResolution: ( selector: string, args?: unknown[] ) => void;
	finishResolution: ( selector: string, args?: unknown[] ) => void;
	invalidateResolution: ( selector: string, args?: unknown[] ) => void;
	invalidateResolutionForStore: ( selector: string ) => void;
	invalidateResolutionForStoreSelector: ( selector: string ) => void;
};

// Omitting state from selector parameter
export type WPDataSelector< T > = T extends (
	state: infer S,
	...args: infer A
) => infer R
	? ( ...args: A ) => R
	: T;

export type WPError< ErrorKey extends string = string, ErrorData = unknown > = {
	errors: Record< ErrorKey, string[] >;
	error_data?: Record< ErrorKey, ErrorData >;
	additional_data?: Record< ErrorKey, ErrorData[] >;
};

/**
 * Obtain the type finally returned by the generator when it's done iterating.
 */
type GeneratorReturnType< T extends ( ...args: any[] ) => Generator > =
	T extends ( ...args: any ) => Generator< any, infer R, any > ? R : never;

/**
 * Maps a "raw" actionCreators object to the actions available when registered on the @wordpress/data store.
 *
 * @template A Selector map, usually from `import * as actions from './my-store/actions';`
 */
export type DispatchFromMap<
	A extends Record< string, ( ...args: any[] ) => any >
> = {
	[ actionCreator in keyof A ]: (
		...args: Parameters< A[ actionCreator ] >
	) => A[ actionCreator ] extends ( ...args: any[] ) => Generator
		? Promise< GeneratorReturnType< A[ actionCreator ] > >
		: Promise< void >;
};

/**
 * Maps a "raw" selector object to the selectors available when registered on the @wordpress/data store.
 *
 * @template S Selector map, usually from `import * as selectors from './my-store/selectors';`
 */
type FunctionKeys< T extends object > = {
	[ K in keyof T ]: T[ K ] extends ( ...args: any[] ) => any ? K : never;
}[ keyof T ];

// See https://github.com/microsoft/TypeScript/issues/46855#issuecomment-974484444
type Cast< T, U > = T extends U ? T : T & U;
type CastToFunction< T > = Cast< T, ( ...args: any[] ) => any >;

/**
 * Parameters type of a function, excluding the first parameter.
 *
 * This is useful for typing some @wordpress/data functions that make a leading
 * `state` argument implicit.
 */
// eslint-disable-next-line @typescript-eslint/ban-types
type TailParameters< F extends Function > = F extends (
	head: any,
	...tail: infer T
) => any
	? T
	: never;

export type SelectFromMap< S extends object > = {
	[ selector in FunctionKeys< S > ]: (
		...args: TailParameters< CastToFunction< S[ selector ] > >
	) => ReturnType< CastToFunction< S[ selector ] > >;
};

/* eslint-enable @typescript-eslint/no-explicit-any */
