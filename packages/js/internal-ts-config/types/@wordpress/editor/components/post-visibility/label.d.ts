declare module '@wordpress/editor/build-types/components/post-visibility/label' {
	/**
	 * Returns the label for the current post visibility setting.
	 *
	 * @return {string} Post visibility label.
	 */
	export default function PostVisibilityLabel(): string;
	/**
	 * Get the label for the current post visibility setting.
	 *
	 * @return {string} Post visibility label.
	 */
	export function usePostVisibilityLabel(): string;
}
