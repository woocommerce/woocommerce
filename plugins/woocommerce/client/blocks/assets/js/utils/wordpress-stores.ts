/**
 * WordPress store names for use with @wordpress/data select/dispatch.
 *
 * Using string-based store names instead of importing store objects avoids
 * adding unnecessary script dependencies. For example, importing
 * `store as editorStore from '@wordpress/editor'` would enqueue the whole
 * wp-editor package as a dependency.
 */

export const CORE_EDITOR_STORE = 'core/editor' as const;
