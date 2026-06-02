declare module '@wordpress/editor/build-types/dataviews/api' {
	/**
	 * @typedef {import('@wordpress/dataviews').Action} Action
	 * @typedef {import('@wordpress/dataviews').Field} Field
	 */
	/**
	 * Registers a new DataViews action.
	 *
	 * This is an experimental API and is subject to change.
	 * it's only available in the Gutenberg plugin for now.
	 *
	 * @param {string} kind   Entity kind.
	 * @param {string} name   Entity name.
	 * @param {Action} config Action configuration.
	 */
	export function registerEntityAction(kind: string, name: string, config: Action): void;
	/**
	 * Unregisters a DataViews action.
	 *
	 * This is an experimental API and is subject to change.
	 * it's only available in the Gutenberg plugin for now.
	 *
	 * @param {string} kind     Entity kind.
	 * @param {string} name     Entity name.
	 * @param {string} actionId Action ID.
	 */
	export function unregisterEntityAction(kind: string, name: string, actionId: string): void;
	/**
	 * Registers a new DataViews field.
	 *
	 * This is an experimental API and is subject to change.
	 * it's only available in the Gutenberg plugin for now.
	 *
	 * @param {string} kind   Entity kind.
	 * @param {string} name   Entity name.
	 * @param {Field}  config Field configuration.
	 */
	export function registerEntityField(kind: string, name: string, config: Field): void;
	/**
	 * Unregisters a DataViews field.
	 *
	 * This is an experimental API and is subject to change.
	 * it's only available in the Gutenberg plugin for now.
	 *
	 * @param {string} kind    Entity kind.
	 * @param {string} name    Entity name.
	 * @param {string} fieldId Field ID.
	 */
	export function unregisterEntityField(kind: string, name: string, fieldId: string): void;
	export type Action = any;
	export type Field = any;
}
