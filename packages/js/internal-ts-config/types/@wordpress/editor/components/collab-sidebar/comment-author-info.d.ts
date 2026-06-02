declare module '@wordpress/editor/build-types/components/collab-sidebar/comment-author-info' {
	export default CommentAuthorInfo;
	declare function CommentAuthorInfo({ avatar, name, date, userId }: {
	    avatar: any;
	    name: any;
	    date: any;
	    userId: any;
	}): import("react").JSX.Element;
}
