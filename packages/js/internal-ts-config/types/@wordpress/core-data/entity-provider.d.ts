declare module '@wordpress/core-data/build-types/entity-provider' {
	/**
	 * Context provider component for providing
	 * an entity for a specific entity.
	 *
	 * @param {Object} props          The component's props.
	 * @param {string} props.kind     The entity kind.
	 * @param {string} props.type     The entity name.
	 * @param {number} props.id       The entity ID.
	 * @param {*}      props.children The children to wrap.
	 *
	 * @return {Object} The provided children, wrapped with
	 *                   the entity's context provider.
	 */
	export default function EntityProvider({ kind, type: name, id, children }: {
	    kind: string;
	    type: string;
	    id: number;
	    children: any;
	}): any;
}
