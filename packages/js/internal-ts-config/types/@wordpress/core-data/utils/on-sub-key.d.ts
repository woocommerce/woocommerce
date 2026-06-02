/// <reference path="../types.d.ts" />

declare module '@wordpress/core-data/build-types/utils/on-sub-key' {
	export function onSubKey(actionProperty: string): AnyFunction;
	export default onSubKey;
	export type AnyFunction = import("@wordpress/core-data/build-types/types").AnyFunction;
}
