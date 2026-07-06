/**
 * External dependencies
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';
import type {
	DraftItem,
	Store as WooCommerce,
} from '@woocommerce/stores/woocommerce/cart';
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsStoreContext,
} from '../frontend';
import { getDraft, getDraftQuantity } from '../cart-drafts';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
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
			validateQuantities: () => void;
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

				// Grouped children each own a draft (keyed by the child product
				// id). Validation reads the child draft quantities — the new
				// source of truth — rather than the `context.quantity` map.
				const childQuantities = groupedProductIds.map(
					( childProductId ) => ( {
						id: childProductId,
						quantity: getDraftQuantity( childProductId ),
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
				// Resolve the child ids and their drafts SYNCHRONOUSLY, before
				// any `yield`, so the reads happen while the block's iAPI context
				// is guaranteed in scope. `getDraft` addresses each child draft by
				// explicit id (no shared-context dependency), but `groupedProductIds`
				// comes from context, so it must be read up front.
				const { groupedProductIds } =
					getContext< AddToCartWithOptionsStoreContext >();

				// Collect the non-zero child drafts (one draft per child product
				// context, keyed by the child product id). Each is a pure
				// add-item payload already carrying id + quantity.
				const childDrafts = groupedProductIds
					.map( ( childProductId ) => getDraft( childProductId ) )
					.filter(
						( draft ): draft is DraftItem =>
							!! draft && ( draft.quantity ?? 0 ) > 0
					);

				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				yield import( '@woocommerce/stores/woocommerce/cart' );

				const { actions: wooActions } = store< WooCommerce >(
					'woocommerce/cart',
					{},
					{ lock: universalLock }
				);

				// Loop addItem() per non-zero child draft. Same-frame calls
				// coalesce into a single Store API batch request via the mutation
				// batcher, and the batcher fires the sync/legacy events, notice
				// pass and a11y announcement once when the cycle settles —
				// behaviorally identical to the old single batch action. Dispatch
				// all in the same tick (do not await between them) so they land in
				// one batch. Drafts are NOT reset after add (parity: quantities
				// persist, a repeat submit compounds server-side).
				const promises = childDrafts.map( ( draft ) =>
					wooActions.addItem( draft )
				);

				yield Promise.all( promises );
			},
		},
		callbacks: {
			validateQuantities() {
				actions.validateGroupedProductQuantity();
			},
		},
	},
	{ lock: universalLock }
);
