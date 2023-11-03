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
	invalidateResolution: ( selector: string ) => void;
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
