declare module '@wordpress/editor/build-types/components/post-excerpt' {
	/**
	 * Renders an editable textarea for the post excerpt.
	 * Templates, template parts and patterns use the `excerpt` field as a description semantically.
	 * Additionally templates and template parts override the `excerpt` field as `description` in
	 * REST API. So this component handles proper labeling and updating the edited entity.
	 *
	 * @param {Object}  props                             - Component props.
	 * @param {boolean} [props.hideLabelFromVision=false] - Whether to visually hide the textarea's label.
	 * @param {boolean} [props.updateOnBlur=false]        - Whether to update the post on change or use local state and update on blur.
	 */
	export default function PostExcerpt({ hideLabelFromVision, updateOnBlur, }: {
	    hideLabelFromVision?: boolean | undefined;
	    updateOnBlur?: boolean | undefined;
	}): import("react").JSX.Element;
}
