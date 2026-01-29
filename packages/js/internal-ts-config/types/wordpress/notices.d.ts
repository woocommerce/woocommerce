import type { store } from '@wordpress/notices/build-types/store';

declare module '@wordpress/data' {
	interface StoreRegistry {
		'core/notices': typeof store;
	}
}
