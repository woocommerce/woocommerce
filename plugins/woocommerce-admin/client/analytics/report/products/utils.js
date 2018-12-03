/**
 * Determine if a product or variation is in low stock.
 *
 * @format
 * @param {number} threshold - The number at which stock is determined to be low.
 * @returns {boolean} - Whether or not the stock is low.
 */

export function isLowStock( status, quantity, threshold ) {
	if ( ! quantity ) {
		// Sites that don't do inventory tracking will always return false.
		return false;
	}
	return 'instock' === status && quantity <= threshold;
}
