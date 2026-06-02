declare module '@wordpress/editor/build-types/components/collab-sidebar/comments' {
	export function Comments({ threads: noteThreads, onEditComment, onAddReply, onCommentDelete, newNoteFormState, setNewNoteFormState, commentSidebarRef, reflowComments, isFloating, commentLastUpdated, }: {
	    threads: any;
	    onEditComment: any;
	    onAddReply: any;
	    onCommentDelete: any;
	    newNoteFormState: any;
	    setNewNoteFormState: any;
	    commentSidebarRef: any;
	    reflowComments: any;
	    isFloating?: boolean | undefined;
	    commentLastUpdated: any;
	}): import("react").JSX.Element;
	export default Comments;
}
