export {};

declare module '@wordpress/data' {
	interface StoreRegistry {
		'core/notices': typeof import( '@wordpress/core-data' ).store;
	}
}
