/// <reference path="./components/index.d.ts" />
/// <reference path="./dataviews/api.d.ts" />
/// <reference path="./private-apis.d.ts" />
/// <reference path="./store/index.d.ts" />
/// <reference path="./utils/index.d.ts" />

declare module '@wordpress/editor' {
	export * from "@wordpress/editor/build-types/components";
	export * from "@wordpress/editor/build-types/utils";
	export * from "@wordpress/editor/build-types/private-apis";
	export * from "@wordpress/editor/build-types/dataviews/api";
	export { storeConfig, store } from "@wordpress/editor/build-types/store";
}

declare module '@wordpress/data' {
	interface StoreRegistry {
		'core/editor': typeof import("@wordpress/editor/build-types/store").store;
	}
}
