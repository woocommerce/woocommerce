// Type for the basic selectors built into @wordpress/data, note these
// types define the interface for the public selectors, so state is not an
// argument.
export type WPDataSelectors = {
	hasStartedResolution: ( selector: string, args?: string[] ) => boolean;
	hasFinishedResolution: ( selector: string, args?: string[] ) => boolean;
	isResolving: ( selector: string, args: [  ] ) => boolean;
};
