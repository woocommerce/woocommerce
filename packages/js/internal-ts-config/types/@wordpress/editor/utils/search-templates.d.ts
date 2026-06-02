declare module '@wordpress/editor/build-types/utils/search-templates' {
	/**
	 * Filters a template list given a search term.
	 *
	 * @param {Array}  templates   Item list
	 * @param {string} searchValue Search input.
	 *
	 * @return {Array} Filtered template list.
	 */
	export function searchTemplates(templates?: any[], searchValue?: string): any[];
}
