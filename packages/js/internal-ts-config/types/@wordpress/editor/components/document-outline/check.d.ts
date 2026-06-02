declare module '@wordpress/editor/build-types/components/document-outline/check' {
	/**
	 * Component check if there are any headings (core/heading blocks) present in the document.
	 *
	 * @param {Object}          props          Props.
	 * @param {React.ReactNode} props.children Children to be rendered.
	 *
	 * @return {React.ReactNode} The component to be rendered or null if there are headings.
	 */
	export default function DocumentOutlineCheck({ children }: {
	    children: React.ReactNode;
	}): React.ReactNode;
}
