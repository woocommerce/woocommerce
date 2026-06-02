/// <reference path="./registry.d.ts" />

declare module '@wordpress/data/build-types/default-registry' {
	declare const _default: import("@wordpress/data/build-types/registry").WPDataRegistry;
	export default _default;
}
