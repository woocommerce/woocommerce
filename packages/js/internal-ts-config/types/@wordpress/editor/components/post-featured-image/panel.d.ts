declare module '@wordpress/editor/build-types/components/post-featured-image/panel' {
	/**
	 * Renders the panel for the post featured image.
	 *
	 * @param {Object}  props               Props.
	 * @param {boolean} props.withPanelBody Whether to include the panel body. Default true.
	 *
	 * @return {React.ReactNode} The component to be rendered.
	 * Return Null if the editor panel is disabled for featured image.
	 */
	export default function PostFeaturedImagePanel({ withPanelBody }: {
	    withPanelBody: boolean;
	}): React.ReactNode;
}
