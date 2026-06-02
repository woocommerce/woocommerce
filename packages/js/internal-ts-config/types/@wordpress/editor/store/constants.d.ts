declare module '@wordpress/editor/build-types/store/constants' {
	/**
	 * Set of post properties for which edits should assume a merging behavior,
	 * assuming an object value.
	 *
	 * @type {Set}
	 */
	export declare const EDIT_MERGE_PROPERTIES: Set<string>;
	/**
	 * Constant for the store module (or reducer) key.
	 */
	export declare const STORE_NAME = "core/editor";
	export declare const PERMALINK_POSTNAME_REGEX: RegExp;
	export declare const ONE_MINUTE_IN_MS: number;
	export declare const AUTOSAVE_PROPERTIES: string[];
	export declare const TEMPLATE_PART_AREA_DEFAULT_CATEGORY = "uncategorized";
	export declare const TEMPLATE_POST_TYPE = "wp_template";
	export declare const TEMPLATE_PART_POST_TYPE = "wp_template_part";
	export declare const PATTERN_POST_TYPE = "wp_block";
	export declare const NAVIGATION_POST_TYPE = "wp_navigation";
	export declare const TEMPLATE_ORIGINS: {
	    custom: string;
	    theme: string;
	    plugin: string;
	};
	export declare const TEMPLATE_POST_TYPES: string[];
	export declare const GLOBAL_POST_TYPES: string[];
}
