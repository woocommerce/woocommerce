declare module '@wordpress/editor/build-types/components/collab-sidebar/utils' {
	/**
	 * Sanitizes a comment string by removing non-printable ASCII characters.
	 *
	 * @param {string} str - The comment string to sanitize.
	 * @return {string} - The sanitized comment string.
	 */
	export function sanitizeCommentString(str: string): string;
	/**
	 * A no-operation function that does nothing.
	 */
	export function noop(): void;
	/**
	 * Gets the border color for an avatar based on the user ID.
	 *
	 * @param {number} userId - The user ID.
	 * @return {string} - The border color.
	 */
	export function getAvatarBorderColor(userId: number): string;
	/**
	 * Generates a comment excerpt from text based on word count type and length.
	 *
	 * @param {string} text          - The comment text to generate excerpt from.
	 * @param {number} excerptLength - The maximum length for the commentexcerpt.
	 * @return {string} - The generated comment excerpt.
	 */
	export function getCommentExcerpt(text: string, excerptLength?: number): string;
	/**
	 * Shift focus to the comment thread associated with a particular comment ID.
	 * If an additional selector is provided, the focus will be shifted to the element matching the selector.
	 *
	 * @typedef {import('@wordpress/element').RefObject} RefObject
	 *
	 * @param {string}       commentId          The ID of the comment thread to focus.
	 * @param {?HTMLElement} container          The container element to search within.
	 * @param {string}       additionalSelector The additional selector to focus on.
	 */
	export function focusCommentThread(commentId: string, container: HTMLElement | null, additionalSelector: string): Promise<any> | undefined;
	/**
	 * Shift focus to the comment thread associated with a particular comment ID.
	 * If an additional selector is provided, the focus will be shifted to the element matching the selector.
	 */
	export type RefObject = any;
}
