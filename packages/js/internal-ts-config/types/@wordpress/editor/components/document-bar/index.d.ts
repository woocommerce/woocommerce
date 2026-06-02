declare module '@wordpress/editor/build-types/components/document-bar' {
	/**
	 * This component renders a navigation bar at the top of the editor. It displays the title of the current document,
	 * a back button (if applicable), and a command center button. It also handles different states of the document,
	 * such as "not found" or "unsynced".
	 *
	 * @example
	 * ```jsx
	 * <DocumentBar />
	 * ```
	 * @param {Object}   props       The component props.
	 * @param {string}   props.title A title for the document, defaulting to the document or
	 *                               template title currently being edited.
	 * @param {IconType} props.icon  An icon for the document, no default.
	 *                               (A default icon indicating the document post type is no longer used.)
	 *
	 * @return {React.ReactNode} The rendered DocumentBar component.
	 */
	export default function DocumentBar(props: {
	    title: string;
	    icon: IconType;
	}): React.ReactNode;
	export type IconType = import("@wordpress/components").IconType;
}
