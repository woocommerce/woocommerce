declare module '@wordpress/editor/build-types/components/provider/use-hide-blocks-from-inserter' {
	/**
	 * In some specific contexts,
	 * the template part and post content blocks need to be hidden.
	 *
	 * @param {string} postType Post Type
	 * @param {string} mode     Rendering mode
	 */
	export function useHideBlocksFromInserter(postType: string, mode: string): void;
}
