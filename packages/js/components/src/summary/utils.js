/**
 * Get a class name depending on item count.
 *
 * @param {number} count - Item count.
 * @return {string} - class name.
 */
export function getHasItemsClass( count ) {
	return count < 15 ? `has-${ count }-items` : 'has-15-items';
}
