declare module '@wordpress/editor/build-types/components/visual-editor/edit-template-blocks-notification' {
	/**
	 * Component that:
	 *
	 * - Displays a 'Edit your template to edit this block' notification when the
	 *   user is focusing on editing page content and clicks on a disabled template
	 *   block.
	 * - Displays a 'Edit your template to edit this block' dialog when the user
	 *   is focusing on editing page content and double clicks on a disabled
	 *   template block.
	 *
	 * @param {Object}                                 props
	 * @param {import('react').RefObject<HTMLElement>} props.contentRef Ref to the block
	 *                                                                  editor iframe canvas.
	 */
	export default function EditTemplateBlocksNotification({ contentRef }: {
	    contentRef: import("react").RefObject<HTMLElement>;
	}): import("react").JSX.Element | null;
}
