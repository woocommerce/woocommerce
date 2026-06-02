/// <reference path="./store/index.d.ts" />

declare module '@wordpress/notices' {
	export { store } from "@wordpress/notices/build-types/store";
}

declare module '@wordpress/data' {
	interface StoreRegistry {
		'core/notices': typeof import("@wordpress/notices/build-types/store").store;
	}
}
