/**
 * External dependencies
 */
import type {
	OptimisticCartItem,
	SelectedAttributes,
} from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import { attributeNamesMatch } from './attribute-matching';

export const doesCartItemMatchAttributes = (
	cartItem: OptimisticCartItem,
	selectedAttributes: SelectedAttributes[]
) => {
	if (
		! Array.isArray( cartItem.variation ) ||
		! Array.isArray( selectedAttributes )
	) {
		return false;
	}

	if ( cartItem.variation.length !== selectedAttributes.length ) {
		return false;
	}

	return cartItem.variation.every(
		( {
			attribute,
			// eslint-disable-next-line
			raw_attribute,
			value,
		}: {
			attribute: string;
			raw_attribute: string;
			value: string;
		} ) =>
			selectedAttributes.some( ( item: SelectedAttributes ) => {
				return (
					attributeNamesMatch(
						item.attribute,
						// It needs to check both because it uses different keys from the same value depending on the context.
						raw_attribute ?? attribute
					) && item.value.toLowerCase() === value?.toLowerCase()
				);
			} )
	);
};
