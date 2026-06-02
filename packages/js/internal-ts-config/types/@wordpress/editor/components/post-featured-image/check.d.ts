declare module '@wordpress/editor/build-types/components/post-featured-image/check' {
	export default PostFeaturedImageCheck;
	/**
	 * Wrapper component that renders its children only if the post type supports a featured image
	 * and the theme supports post thumbnails.
	 *
	 * @param {Object}          props          Props.
	 * @param {React.ReactNode} props.children Children to be rendered.
	 *
	 * @return {React.ReactNode} The rendered component.
	 */
	declare function PostFeaturedImageCheck({ children }: {
	    children: React.ReactNode;
	}): React.ReactNode;
}
