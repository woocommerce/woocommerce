/**
 * TypeScript's legacy `node` module resolution does not follow package export
 * maps, so it cannot find the types for the official DataViews `/wp` entry.
 * That entry exposes the same typed API as the package root.
 */
declare module '@wordpress/dataviews/wp' {
	export { DataForm } from '@wordpress/dataviews';
}
