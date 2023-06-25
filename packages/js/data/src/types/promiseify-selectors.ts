/**
 * Type helper that maps select() return types to their resolveSelect() return types.
 * This works by mapping over each Selector, and returning a function that
 * returns a Promise of the Selector's return type.
 */

export type PromiseifySelectors< Selectors > = {
	[ SelectorFunction in keyof Selectors ]: Selectors[ SelectorFunction ] extends (
		...args: infer SelectorArgs
	) => infer SelectorReturnType
		? ( ...args: SelectorArgs ) => Promise< SelectorReturnType >
		: never;
};
