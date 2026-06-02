/// <reference path="../index.d.ts" />

declare module '@wordpress/block-editor/utils' {
	import { EditorStyle } from "@wordpress/block-editor";

	/**
	 * Applies a series of CSS rule transforms to wrap selectors inside a given class and/or rewrite
	 * URLs depending on the parameters passed.
	 */
	export function transformStyles(styles: EditorStyle[], wrapperClassName?: string): string[];
}
