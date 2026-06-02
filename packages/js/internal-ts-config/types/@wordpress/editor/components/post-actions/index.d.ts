declare module '@wordpress/editor/build-types/components/post-actions' {
	export default function PostActions({ postType, postId, onActionPerformed }: {
	    postType: any;
	    postId: any;
	    onActionPerformed: any;
	}): import("react").JSX.Element;
	export function ActionModal({ action, items, closeModal }: {
	    action: any;
	    items: any;
	    closeModal: any;
	}): import("react").JSX.Element;
}
