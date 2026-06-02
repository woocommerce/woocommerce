declare module '@wordpress/editor/build-types/components/post-url/label' {
	/**
	 * Represents a label component for a post URL.
	 *
	 * @return {React.ReactNode} The PostURLLabel component.
	 */
	export default function PostURLLabel(): React.ReactNode;
	/**
	 * Custom hook to get the label for the post URL.
	 *
	 * @return {string} The filtered and decoded post URL label.
	 */
	export function usePostURLLabel(): string;
}
