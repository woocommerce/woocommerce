/// <reference path="./use-cached-truthy.d.ts" />
/// <reference path="./use-typography-props.d.ts" />

declare module '@wordpress/block-editor/hooks' {
	export { useCachedTruthy } from "@wordpress/block-editor/hooks/use-cached-truthy";
	export { getTypographyClassesAndStyles } from "@wordpress/block-editor/hooks/use-typography-props";
}
