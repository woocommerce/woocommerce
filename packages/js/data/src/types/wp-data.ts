// Type for the basic selectors built into @wordpress/data, note these
// types define the interface for the public selectors, so state is not an
// argument.
export type WPDataSelectors = {
	hasStartedResolution: ( selector: string, args?: string[] ) => boolean;
	hasFinishedResolution: ( selector: string, args?: string[] ) => boolean;
	isResolving: ( selector: string, args: string[] ) => boolean;
};

export type WPDataActions = {
	startResolution: ( selector: string, args?: string[] ) => void;
	finishResolution: ( selector: string, args?: string[] ) => void;
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
