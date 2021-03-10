/**
 * Usually we use ReturnType of all the action creators to deduce all the actions.
 * This works until one of the action creators is a generator and doesn't actually "Return" an action.
 * This type helper allows for actions to be both functions and generators
 */
export type ReturnOrGeneratorYieldUnion<
	T extends ( ...args: any ) => any
> = T extends ( ...args: any ) => infer Return
	? Return extends Generator< infer T, infer U, any >
		? T | U
		: Return
	: never;

/**
 * Maps a "raw" actionCreators object to the actions available when registered on the @wordpress/data store.
 *
 * @template A Selector map, usually from `import * as actions from './my-store/actions';`
 */
// Todo: This is a temporary implementation of what exists in @automattic/data-stores
//  already: https://github.com/Automattic/wp-calypso/blob/0ba7f5a91571b3ba6a84be7db021c7eab247e434/packages/data-stores/src/mapped-types.ts
//  We will need to remove this when @automattic/data-stores is updated on NPM.
export type DispatchFromMap<
	A extends Record< string, ( ...args: any[] ) => any >
> = {
	[ actionCreator in keyof A ]: (
		...args: Parameters< A[ actionCreator ] >
	) => A[ actionCreator ] extends ( ...args: any[] ) => Generator
		? Promise< GeneratorReturnType< A[ actionCreator ] > >
		: void;
};

/**
 * Obtain the type finally returned by the generator when it's done iterating.
 */
// Todo: This is a temporary implementation of what exists in @automattic/data-stores
//  already: https://github.com/Automattic/wp-calypso/blob/0ba7f5a91571b3ba6a84be7db021c7eab247e434/packages/data-stores/src/mapped-types.ts
//  We will need to remove this when @automattic/data-stores is updated on NPM.
export type GeneratorReturnType<
	T extends ( ...args: any[] ) => Generator
> = T extends ( ...args: any ) => Generator< any, infer R, any > ? R : never;
