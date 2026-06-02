declare module '@wordpress/editor/build-types/components/post-card-panel' {
	/**
	 * Renders a title of the post type and the available quick actions available within a 3-dot dropdown.
	 *
	 * @param {Object}          props                     - Component props.
	 * @param {string}          [props.postType]          - The post type string.
	 * @param {string|string[]} [props.postId]            - The post id or list of post ids.
	 * @param {Function}        [props.onActionPerformed] - A callback function for when a quick action is performed.
	 * @return {React.ReactNode} The rendered component.
	 */
	export default function PostCardPanel({ postType, postId, onActionPerformed, }: {
	    postType?: string | undefined;
	    postId?: string | string[] | undefined;
	    onActionPerformed?: Function | undefined;
	}): React.ReactNode;
}
