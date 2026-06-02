declare module '@wordpress/editor/build-types/components/post-discussion/panel' {
	/**
	 * This component allows to update comment and pingback
	 * settings for the current post. Internally there are
	 * checks whether the current post has support for the
	 * above and if the `discussion-panel` panel is enabled.
	 *
	 * @return {React.ReactNode} The rendered PostDiscussionPanel component.
	 */
	export default function PostDiscussionPanel(): React.ReactNode;
}
