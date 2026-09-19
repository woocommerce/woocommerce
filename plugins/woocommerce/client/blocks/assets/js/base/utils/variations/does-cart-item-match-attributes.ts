/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type {
	OptimisticCartItem,
	SelectedAttributes,
	WooCommerceStore,
} from '@woocommerce/stores/woocommerce';

/**
 * Internal dependencies
 */
import { attributeNamesMatch } from './attribute-matching';

// No `import '@woocommerce/stores/woocommerce'` side effect here: this
// module's only caller is `notices.ts`, itself only reachable through the
// unified store's own module graph, so the store is always registering by
// the time this file loads. Importing the module directly here would create
// a cycle back into it.
const { state: wooState } = store< WooCommerceStore >(
	'woocommerce',
	{},
	{
		lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
	}
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

	const parentProductId = wooState.productVariations[ cartItem.id ]?.parent;
	const productAttributes =
		wooState.products[ parentProductId ]?.attributes ?? [];

	return cartItem.variation.every( ( { attribute, value: termName } ) =>
		selectedAttributes.some( ( selectedAttr: SelectedAttributes ) => {
			// Find the term matching the cart item's value label.
			const terms = productAttributes.find( ( attr ) =>
				attributeNamesMatch( attribute, attr.name )
			)?.terms;
			const termSlug =
				terms?.find( ( term ) => term.name === termName )?.slug ||
				termName; // Fallback to termName if no matching term is found.
			return (
				attributeNamesMatch( selectedAttr.attribute, attribute ) &&
				selectedAttr.value.toLowerCase() === termSlug?.toLowerCase()
			);
		} )
	);
};
