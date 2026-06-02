declare module '@wordpress/editor/build-types/components/post-last-revision/check' {
	export default PostLastRevisionCheck;
	/**
	 * Wrapper component that renders its children if the post has more than one revision.
	 *
	 * @param {Object}          props          Props.
	 * @param {React.ReactNode} props.children Children to be rendered.
	 *
	 * @return {React.ReactNode} Rendered child components if post has more than one revision, otherwise null.
	 */
	declare function PostLastRevisionCheck({ children }: {
	    children: React.ReactNode;
	}): React.ReactNode;
}
