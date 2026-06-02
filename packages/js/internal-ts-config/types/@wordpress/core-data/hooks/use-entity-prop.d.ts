declare module '@wordpress/core-data/build-types/hooks/use-entity-prop' {
	/**
	 * Hook that returns the value and a setter for the
	 * specified property of the nearest provided
	 * entity of the specified type.
	 *
	 * @param {string}        kind  The entity kind.
	 * @param {string}        name  The entity name.
	 * @param {string}        prop  The property name.
	 * @param {number|string} [_id] An entity ID to use instead of the context-provided one.
	 *
	 * @return {[*, Function, *]} An array where the first item is the
	 *                            property value, the second is the
	 *                            setter and the third is the full value
	 * 							  object from REST API containing more
	 * 							  information like `raw`, `rendered` and
	 * 							  `protected` props.
	 */
	export default function useEntityProp<PropType>(kind: string, name: string, prop: string, _id?: number | string): [PropType, (value: PropType) => void, PropType];
}
