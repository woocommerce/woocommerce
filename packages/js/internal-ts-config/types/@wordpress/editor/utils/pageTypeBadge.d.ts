declare module '@wordpress/editor/build-types/utils/pageTypeBadge' {
	/**
	 * Custom hook to get the page type badge for the current post on edit site view.
	 *
	 * @param {number|string} postId postId of the current post being edited.
	 */
	export default function usePageTypeBadge(postId: number | string): false | import("@wordpress/i18n").TranslatableText<"Homepage"> | import("@wordpress/i18n").TranslatableText<"Posts Page">;
}
