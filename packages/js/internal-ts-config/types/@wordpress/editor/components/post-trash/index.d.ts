declare module '@wordpress/editor/build-types/components/post-trash' {
	/**
	 * Displays the Post Trash Button and Confirm Dialog in the Editor.
	 *
	 * @param {?{onActionPerformed: Object}} An object containing the onActionPerformed function.
	 * @return {React.ReactNode} The rendered PostTrash component.
	 */
	export default function PostTrash({ onActionPerformed }: {
	    onActionPerformed: Object;
	} | null): React.ReactNode;
}
