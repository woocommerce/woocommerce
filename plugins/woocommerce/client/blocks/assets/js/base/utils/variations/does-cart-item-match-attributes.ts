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
				// Both raw_attribute and item.attribute use the slug format
				// (e.g., "attribute_pa_color"), so direct comparison works.
				return (
					item.attribute === ( raw_attribute ?? attribute ) &&
					item.value.toLowerCase() === value?.toLowerCase()
				);
			} )
	);
};
