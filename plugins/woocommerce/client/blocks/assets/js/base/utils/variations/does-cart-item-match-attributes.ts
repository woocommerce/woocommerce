/**
 * External dependencies
 */
import type {
	OptimisticCartItem,
	SelectedAttributes,
} from '@woocommerce/stores/woocommerce/cart';

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
			// eslint-disable-next-line
			raw_attribute,
			value,
		}: {
			raw_attribute: string;
			value: string;
		} ) =>
			selectedAttributes.some( ( item: SelectedAttributes ) => {
				return (
					item.attribute === raw_attribute &&
					( item.value.toLowerCase() === value.toLowerCase() ||
						( item.value && value === '' ) ) // Handle "any" attribute type
				);
			} )
	);
};
