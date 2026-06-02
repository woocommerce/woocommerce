declare module '@wordpress/editor/build-types/components/document-outline' {
	/**
	 * Renders a document outline component.
	 *
	 * @param {Object}   props                         Props.
	 * @param {Function} props.onSelect                Function to be called when an outline item is selected
	 * @param {boolean}  props.hasOutlineItemsDisabled Indicates whether the outline items are disabled.
	 *
	 * @return {React.ReactNode} The rendered component.
	 */
	export default function DocumentOutline({ onSelect, hasOutlineItemsDisabled, }: {
	    onSelect: Function;
	    hasOutlineItemsDisabled: boolean;
	}): React.ReactNode;
}
