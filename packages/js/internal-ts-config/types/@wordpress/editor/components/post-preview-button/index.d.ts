declare module '@wordpress/editor/build-types/components/post-preview-button' {
	/**
	 * Renders a button that opens a new window or tab for the preview,
	 * writes the interstitial message to this window, and then navigates
	 * to the actual preview link. The button is not rendered if the post
	 * is not viewable and disabled if the post is not saveable.
	 *
	 * @param {Object}   props                     The component props.
	 * @param {string}   props.className           The class name for the button.
	 * @param {string}   props.textContent         The text content for the button.
	 * @param {boolean}  props.forceIsAutosaveable Whether to force autosave.
	 * @param {string}   props.role                The role attribute for the button.
	 * @param {Function} props.onPreview           The callback function for preview event.
	 *
	 * @return {React.ReactNode} The rendered button component.
	 */
	export default function PostPreviewButton({ className, textContent, forceIsAutosaveable, role, onPreview, }: {
	    className: string;
	    textContent: string;
	    forceIsAutosaveable: boolean;
	    role: string;
	    onPreview: Function;
	}): React.ReactNode;
}
