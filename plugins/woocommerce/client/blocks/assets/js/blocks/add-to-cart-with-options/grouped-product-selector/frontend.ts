/**
 * External dependencies
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce';
import type { WooCommerceStore } from '@woocommerce/stores/woocommerce';

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

const { state: wooState, actions: wooActions } = store< WooCommerceStore >(
	'woocommerce',
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

/**
 * Resolves a grouped child's effective quantity: the typed quantity when its
 * own scope holds one, and the form's `initialQuantity` entry for that child
 * otherwise (0 when the child was never touched).
 *
 * @param childProductId  The child's product id.
 * @param scopeName       The child row's own scope name.
 * @param initialQuantity The form's `initialQuantity` context map.
 * @return The child's effective quantity.
 */
function getChildEffectiveQuantity(
	childProductId: number,
	scopeName: string,
	initialQuantity: Record< number, number >
): number {
	// Read the record directly rather than through `draftCartItem.quantity`:
	// the draft's fallback of `1` cannot tell "typed 1" from "nothing typed".
	const typedQuantity =
		wooState.productScopes[ scopeName ]?.draftCartItem?.quantity;

	if ( typeof typedQuantity === 'number' ) {
		return typedQuantity;
	}

	return initialQuantity?.[ childProductId ] ?? 0;
}

const { actions } = store< GroupedProductAddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{
		actions: {
			validateGroupedProductQuantity() {
				actions.clearErrors( 'invalid-quantities' );

				const { errorMessages } = getConfig();
				const {
					groupedProductIds,
					groupedScopeNames,
					initialQuantity,
				} = getContext< AddToCartWithOptionsStoreContext >();

				const effectiveQuantities = groupedProductIds.map(
					( childProductId, index ) =>
						getChildEffectiveQuantity(
							childProductId,
							groupedScopeNames[ index ],
							initialQuantity
						)
				);

				// Validate that at least one product quantity is above 0.
				const hasNonZeroQuantity = effectiveQuantities.some(
					( qty ) => qty > 0
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
				const hasInvalidQuantity = groupedProductIds.some(
					( childProductId, index ) => {
						const qty = effectiveQuantities[ index ];
						const { product } = wooState.findProductScope( {
							productId: childProductId,
							scopeName: groupedScopeNames[ index ],
						} );
						if ( ! product ) {
							return false;
						}
						const { minimum, maximum } = product.add_to_cart;
						return qty !== 0 && ( qty < minimum || qty > maximum );
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
				const {
					groupedProductIds,
					groupedScopeNames,
					initialQuantity,
				} = getContext< AddToCartWithOptionsStoreContext >();

				const promises: ReturnType< typeof wooActions.addCartItem >[] =
					[];

				groupedProductIds.forEach( ( childProductId, index ) => {
					const scopeName = groupedScopeNames[ index ];
					const quantity = getChildEffectiveQuantity(
						childProductId,
						scopeName,
						initialQuantity
					);

					if ( quantity === 0 ) {
						return;
					}

					const envelope = wooState.findProductScope( {
						productId: childProductId,
						scopeName,
					} );
					const record =
						wooState.productScopes[ scopeName ]?.draftCartItem;

					promises.push(
						wooActions.addCartItem(
							{
								...record,
								id: envelope.productId,
								variation: envelope.variation,
								quantity,
							},
							{ showCartUpdatesNotices: false }
						)
					);
				} );

				// All calls are issued above, in the same tick, so the
				// mutation batcher below them coalesces them into one batch
				// request; this only waits for the combined result.
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
