declare module '@wordpress/core-data/build-types/hooks/use-entity-block-editor' {
	/**
	 * Hook that returns block content getters and setters for
	 * the nearest provided entity of the specified type.
	 *
	 * The return value has the shape `[ blocks, onInput, onChange ]`.
	 * `onInput` is for block changes that don't create undo levels
	 * or dirty the post, non-persistent changes, and `onChange` is for
	 * persistent changes. They map directly to the props of a
	 * `BlockEditorProvider` and are intended to be used with it,
	 * or similar components or hooks.
	 *
	 * @param {string} kind         The entity kind.
	 * @param {string} name         The entity name.
	 * @param {Object} options
	 * @param {string} [options.id] An entity ID to use instead of the context-provided one.
	 *
	 * @return {[unknown[], Function, Function]} The block array and setters.
	 */
	export default function useEntityBlockEditor(kind: string, name: string, { id: _id }?: {
	    id?: string | undefined;
	}): [unknown[], Function, Function];
}
