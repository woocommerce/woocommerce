/**
 * External dependencies
 */
import type { CartItem } from '@woocommerce/types';
import type {
	OptimisticCartItem,
	SelectedAttributes,
} from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import { doesCartItemMatchAttributes } from '../../../base/utils/variations/does-cart-item-match-attributes';

/** Product ID and optional selected attributes used to count cart lines. */
type InCartQuantityTarget = {
	/** Product or variation ID to count. */
	id: number;
	/** When provided, variation lines must match these shopper selections. */
	selectedAttributes?: SelectedAttributes[];
};

/**
 * Sum eligible cart-line quantities for a product or variation.
 *
 * When selected attributes are provided, variation lines must match them.
 *
 * @param items  Cart response or optimistic cart lines.
 * @param target Product ID and optional shopper-selected variation attributes.
 * @return The sum of matching quantities, or zero when none match.
 */
export const getInCartQuantity = (
	items: ReadonlyArray< CartItem | OptimisticCartItem >,
	target: InCartQuantityTarget
): number => {
	let quantity = 0;

	for ( const item of items ) {
		if ( item.id !== target.id ) {
			continue;
		}

		if ( 'parent_item_key' in item && item.parent_item_key ) {
			continue;
		}

		if (
			target.selectedAttributes !== undefined &&
			item.type === 'variation' &&
			! doesCartItemMatchAttributes( item, target.selectedAttributes )
		) {
			continue;
		}

		quantity += item.quantity;
	}

	return quantity;
};
