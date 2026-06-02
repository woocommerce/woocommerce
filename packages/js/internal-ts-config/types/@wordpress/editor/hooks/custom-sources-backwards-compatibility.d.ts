declare module '@wordpress/editor/build-types/hooks/custom-sources-backwards-compatibility' {
	export type WPHigherOrderComponent = import("@wordpress/compose").WPHigherOrderComponent;
	export type WPBlockSettings = any;
	/**
	 * Object whose keys are the names of block attributes, where each value
	 * represents the meta key to which the block attribute is intended to save.
	 */
	export type WPMetaAttributeMapping = {
	    [x: string]: string;
	};
}
