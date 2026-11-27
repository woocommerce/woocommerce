/**
 * External dependencies
 */
import type { CartItem, CartVariationItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { SelectedAttributes } from '../types';
import type { OptimisticCartItem } from './types';
import { doesCartItemMatchAttributes } from '../../../utils/variations/does-cart-item-match-attributes';

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

/**
 * Returns `true` when the given cart line matches the product identified by
 * `id` and `variation`, using the same matching logic as `findItemInCart`.
 *
 * Simple items match by `id` equality. Variation items additionally require
 * `variation.length` equality and `doesCartItemMatchAttributes`.
 *
 * @param item      The cart line to test.
 * @param id        The product id to match against.
 * @param variation The variation attributes to match against, if any.
 * @return `true` when the line belongs to the specified product.
 */
export function lineMatchesProduct(
	item: OptimisticCartItem | CartItem,
	id: number,
	variation?: CartVariationItem[] | SelectedAttributes[]
): boolean {
	if ( isCartItem( item ) && item.type === 'variation' ) {
		if (
			id !== item.id ||
			! item.variation ||
			! variation ||
			item.variation.length !== variation.length
		) {
			return false;
		}
		return doesCartItemMatchAttributes( item, variation );
	}
	return id === item.id;
}
