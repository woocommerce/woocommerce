declare module '@wordpress/core-data/build-types/hooks/use-entity-id' {
	/**
	 * Hook that returns the ID for the nearest
	 * provided entity of the specified type.
	 *
	 * @param {string} kind The entity kind.
	 * @param {string} name The entity name.
	 */
	export default function useEntityId(kind: string, name: string): any;
}
