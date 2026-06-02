declare module '@wordpress/editor/build-types/utils/get-item-title' {
	/**
	 * Helper function to get the title of a post item.
	 * This is duplicated from the `@wordpress/fields` package.
	 * `packages/fields/src/actions/utils.ts`
	 *
	 * @param {Object} item The post item.
	 * @return {string} The title of the item, or an empty string if the title is not found.
	 */
	export function getItemTitle(item: Object): string;
}
