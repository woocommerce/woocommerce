/**
 * External dependencies
 */
import type { CartItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { OptimisticCartItem } from './types';

/**
 * Tells a Store API cart line apart from an optimistic one. Every Store API
 * line carries a `name`; an optimistic line does not.
 *
 * @param item The cart line to test.
 * @return `true` when the line is a Store API cart line.
 */
export function isCartItem(
	item: OptimisticCartItem | CartItem
): item is CartItem {
	return 'name' in item;
}
