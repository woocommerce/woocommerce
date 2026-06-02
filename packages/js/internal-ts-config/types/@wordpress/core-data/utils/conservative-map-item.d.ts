declare module '@wordpress/core-data/build-types/utils/conservative-map-item' {
	/**
	 * Given the current and next item entity record, returns the minimally "modified"
	 * result of the next item, preferring value references from the original item
	 * if equal. If all values match, the original item is returned.
	 *
	 * @param {Object} item     Original item.
	 * @param {Object} nextItem Next item.
	 *
	 * @return {Object} Minimally modified merged item.
	 */
	export default function conservativeMapItem(item: any, nextItem: any): any;
}
