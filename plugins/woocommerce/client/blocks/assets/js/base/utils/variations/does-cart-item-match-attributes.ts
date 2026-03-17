/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/products';
import type {
	OptimisticCartItem,
	SelectedAttributes,
} from '@woocommerce/stores/woocommerce/cart';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

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

	const parentProductId =
		productsState.productVariations[ cartItem.id ]?.parent;
	const productAttributes =
		productsState.products[ parentProductId ]?.attributes ?? [];

	return cartItem.variation.every( ( { attribute, value: termName } ) =>
		selectedAttributes.some( ( selectedAttr: SelectedAttributes ) => {
			// Find the term matching the cart item's value label.
			const terms = productAttributes.find(
				( attr ) => attribute === attr.name
			)?.terms;
			const termSlug = terms?.find(
				( term ) => term.name === termName
			)?.slug;
			return (
				selectedAttr.attribute === attribute &&
				selectedAttr.value === termSlug
			);
		} )
	);
};
