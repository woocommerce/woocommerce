/**
 * External dependencies
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';
import type {
	DraftItem,
	Store as WooCommerce,
} from '@woocommerce/stores/woocommerce/cart';
import '@woocommerce/stores/woocommerce/products';
import '@woocommerce/stores/woocommerce/cart';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsStoreContext,
} from '../frontend';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

const { state: cartState, actions: cartActions } = store< WooCommerce >(
	'woocommerce/cart',
	{},
	{ lock: universalLock }
);

export type GroupedProductAddToCartWithOptionsStore =
	AddToCartWithOptionsStore & {
		actions: {
			validateGroupedProductQuantity: () => void;
			batchAddToCart: () => void;
		};
		callbacks: {
			validateGroupedProductQuantity: () => void;
		};
	};

const { actions } = store< GroupedProductAddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{
		actions: {
			validateGroupedProductQuantity() {
				actions.clearErrors( 'invalid-quantities' );

				const { errorMessages } = getConfig();
				const { groupedProductIds } =
					getContext< AddToCartWithOptionsStoreContext >();

				// Each grouped child owns a draft keyed by its own product id. An
				// untouched child has no draft (lazy birth) → quantity 0 → excluded
				// (the default IS the absence). Read each child's quantity through
				// the shared envelope by explicit id.
				const childQuantities = groupedProductIds.map(
					( childProductId ) => ( {
						id: childProductId,
						quantity:
							cartState.findItem( { id: childProductId } ).draft
								?.quantity ?? 0,
					} )
				);

				// Validate that at least one product quantity is above 0.
				const hasNonZeroQuantity = childQuantities.some(
					( { quantity } ) => quantity > 0
				);

				if ( ! hasNonZeroQuantity ) {
					actions.addError( {
						code: 'groupedProductAddToCartMissingItems',
						message:
							errorMessages?.groupedProductAddToCartMissingItems ||
							'',
						group: 'invalid-quantities',
					} );

					return;
				}

				// Validate that all product quantities are within the min and max (or 0).
				const hasInvalidQuantity = childQuantities.some(
					( { id, quantity } ) => {
						const product = productsState.findProduct( {
							id: Number( id ),
						} );
						if ( ! product ) {
							return false;
						}
						const { minimum, maximum } = product.add_to_cart;
						return (
							quantity !== 0 &&
							( quantity < minimum || quantity > maximum )
						);
					}
				);

				if ( hasInvalidQuantity ) {
					actions.addError( {
						code: 'invalidQuantities',
						message: errorMessages?.invalidQuantities || '',
						group: 'invalid-quantities',
					} );
				}
			},
			*batchAddToCart() {
				// Resolve the child ids SYNCHRONOUSLY, before any `yield`, so the
				// context read happens while the block's iAPI context is in scope.
				const { groupedProductIds } =
					getContext< AddToCartWithOptionsStoreContext >();

				// Collect the non-zero child drafts (one draft per child, keyed by
				// the child product id). Each is a pure add-item payload carrying
				// id + quantity. An untouched child has no stored draft →
				// `findItem` reports `draft: undefined` (it never fabricates one) →
				// skipped. Reading through the envelope (rather than the raw
				// `draftItems` map) keeps this on the single lookup surface.
				const childDrafts = groupedProductIds
					.map(
						( childProductId ) =>
							cartState.findItem( { id: childProductId } ).draft
					)
					.filter(
						( draft ): draft is DraftItem =>
							!! draft && ( draft.quantity ?? 0 ) > 0
					);

				// Loop addItem() per non-zero child draft. Same-frame calls
				// coalesce into a single Store API batch request via the mutation
				// batcher, which fires the sync/legacy events, notice pass and a11y
				// announcement once when the cycle settles. Dispatch all in the same
				// tick (do not await between them) so they land in one batch. Drafts
				// are NOT reset after add (quantities persist, a repeat submit
				// compounds server-side).
				const promises = childDrafts.map( ( draft ) =>
					cartActions.addItem( draft )
				);

				yield Promise.all( promises );
			},
		},
		callbacks: {
			// Bound via `data-wp-init` on the grouped selector so the initial
			// validation runs on hydration. `data-wp-init` expects a callback,
			// not an action, so this slot delegates to the action, which holds
			// the single implementation (the composer's quantity watch also
			// invokes that action imperatively).
			validateGroupedProductQuantity() {
				actions.validateGroupedProductQuantity();
			},
		},
	},
	{ lock: universalLock }
);
