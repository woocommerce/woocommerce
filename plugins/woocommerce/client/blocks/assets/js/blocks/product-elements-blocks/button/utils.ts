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
import { doesCartItemMatchAttributes } from '../../../../base/utils/variations/does-cart-item-match-attributes';

/** Product ID and optional selected attributes used to count cart lines. */
type InCartQuantityTarget = {
	/** Product or variation ID to count. */
	id: number;
	/** When provided, variation lines must match these shopper selections. */
	selectedAttributes?: SelectedAttributes[];
};

/**
 * Check whether a cart entry is a plain object.
 *
 * @param value Cart entry to inspect.
 * @return Whether the entry is a plain object.
 */
const isPlainObject = (
	value: unknown
): value is Record< string, unknown > => {
	if (
		typeof value !== 'object' ||
		value === null ||
		Array.isArray( value )
	) {
		return false;
	}

	const prototype = Object.getPrototypeOf( value );
	return prototype === Object.prototype || prototype === null;
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
		if ( ! isPlainObject( item ) || typeof item.id !== 'number' ) {
			continue;
		}

		if (
			'parent_item_key' in item &&
			typeof item.parent_item_key === 'string' &&
			item.parent_item_key !== ''
		) {
			continue;
		}

		if ( item.id !== target.id ) {
			continue;
		}

		if (
			target.selectedAttributes !== undefined &&
			item.type === 'variation' &&
			! doesCartItemMatchAttributes( item, target.selectedAttributes )
		) {
			continue;
		}

		if ( typeof item.quantity === 'number' ) {
			quantity += item.quantity;
		}
	}

	return quantity;
};
