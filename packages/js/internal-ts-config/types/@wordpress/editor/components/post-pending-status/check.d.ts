declare module '@wordpress/editor/build-types/components/post-pending-status/check' {
	/**
	 * This component checks the publishing status of the current post.
	 * If the post is already published or the user doesn't have the
	 * capability to publish, it returns null.
	 *
	 * @param {Object}          props          Component properties.
	 * @param {React.ReactNode} props.children Children to be rendered.
	 *
	 * @return {React.ReactNode} The rendered child elements or null if the post is already published or the user doesn't have the capability to publish.
	 */
	export function PostPendingStatusCheck({ children }: {
	    children: React.ReactNode;
	}): React.ReactNode;
	export default PostPendingStatusCheck;
}
