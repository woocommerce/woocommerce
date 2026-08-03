/**
 * The DataViews `/wp` export bundles the version-sensitive DataForm runtime
 * while leaving supported WordPress singleton dependencies external.
 *
 * Keep this facade intentionally narrow. Add exports only when Settings UI
 * consumes them, and never expose DataViews' private APIs from this package.
 */
export { DataForm } from '@wordpress/dataviews/wp';
