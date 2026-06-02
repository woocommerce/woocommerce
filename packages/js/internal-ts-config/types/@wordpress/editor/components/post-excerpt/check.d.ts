declare module '@wordpress/editor/build-types/components/post-excerpt/check' {
	export default PostExcerptCheck;
	/**
	 * Component for checking if the post type supports the excerpt field.
	 *
	 * @param {Object}          props          Props.
	 * @param {React.ReactNode} props.children Children to be rendered.
	 *
	 * @return {React.ReactNode} The rendered component.
	 */
	declare function PostExcerptCheck({ children }: {
	    children: React.ReactNode;
	}): React.ReactNode;
}
