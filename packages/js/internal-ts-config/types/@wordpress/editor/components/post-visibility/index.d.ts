declare module '@wordpress/editor/build-types/components/post-visibility' {
	/**
	 * Allows users to set the visibility of a post.
	 *
	 * @param {Object}   props         The component props.
	 * @param {Function} props.onClose Function to call when the popover is closed.
	 * @return {React.ReactNode} The rendered component.
	 */
	export default function PostVisibility({ onClose }: {
	    onClose: Function;
	}): React.ReactNode;
}
