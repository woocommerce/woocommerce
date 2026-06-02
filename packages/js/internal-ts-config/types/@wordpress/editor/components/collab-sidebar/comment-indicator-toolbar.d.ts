declare module '@wordpress/editor/build-types/components/collab-sidebar/comment-indicator-toolbar' {
	export default CommentAvatarIndicator;
	declare function CommentAvatarIndicator({ onClick, thread }: {
	    onClick: any;
	    thread: any;
	}): import("react").JSX.Element | null;
}
