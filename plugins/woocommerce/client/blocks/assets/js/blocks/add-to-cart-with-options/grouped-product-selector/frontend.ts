/**
 * External dependencies
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';
import type {
	AddCartItemResult,
	ClientCartItem,
	Store as WooCommerce,
	WooCommerceConfig,
} from '@woocommerce/stores/woocommerce/cart';
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';
import type { Store as StoreNotices } from '@woocommerce/stores/store-notices';

/**
 * Internal dependencies
 */
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsStoreContext,
} from '../frontend';
import { triggerAddedToCartEvent } from '../../../base/stores/woocommerce/legacy-events';

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
				const context =
					getContext< AddToCartWithOptionsStoreContext >();

				// Validate that at least one product quantity is above 0.
				const hasNonZeroQuantity = Object.values(
					context.quantity
				).some( ( qty ) => qty > 0 );

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
				const hasInvalidQuantity = Object.entries(
					context.quantity
				).some( ( [ id, qty ] ) => {
					const product = productsState.findProduct( {
						id: Number( id ),
						selectedAttributes: context.selectedAttributes,
					} );
					if ( ! product ) {
						return false;
					}
					const { minimum, maximum } = product.add_to_cart;
					return qty !== 0 && ( qty < minimum || qty > maximum );
				} );

				if ( hasInvalidQuantity ) {
					actions.addError( {
						code: 'invalidQuantities',
						message: errorMessages?.invalidQuantities || '',
						group: 'invalid-quantities',
					} );
				}
			},
			*batchAddToCart() {
				// Kick off the a11y module fetch immediately, mirroring the
				// pattern `addCartItem` itself uses, so it's likely already
				// resolved by the time the once-per-batch announcement needs
				// it.
				const a11yModulePromise = import( '@wordpress/a11y' );

				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				const { emitSyncEvent } = ( yield import(
					'@woocommerce/stores/woocommerce/cart'
				) ) as typeof import('@woocommerce/stores/woocommerce/cart');

				const { quantity, selectedAttributes, groupedProductIds } =
					getContext< AddToCartWithOptionsStoreContext >();

				const addedItems: ClientCartItem[] = [];

				for ( const childProductId of groupedProductIds ) {
					if ( quantity[ childProductId ] === 0 ) {
						continue;
					}

					const product = productsState.findProduct( {
						id: Number( childProductId ),
						selectedAttributes,
					} );

					if ( ! product ) {
						continue;
					}

					addedItems.push( {
						id: Number( childProductId ),
						quantityToAdd: quantity[ childProductId ],
						variation: selectedAttributes,
						type: product.type,
					} );
				}

				const { actions: wooActions } = store< WooCommerce >(
					'woocommerce',
					{},
					{ lock: universalLock }
				);

				// Fire one `addCartItem` per selected child concurrently: a
				// synchronous `.map()` with no `yield`/`await` between calls,
				// so every submit lands in the same microtask tick and the
				// cart store's mutation batcher coalesces them into a single
				// `POST /wc/store/v1/batch` — the same request shape the
				// removed `batchAddCartItems` action produced.
				const promises = addedItems.map( ( item ) =>
					wooActions.addCartItem( item, {
						showCartUpdatesNotices: false,
						suppressPostAddSideEffects: true,
					} )
				);

				const results = ( yield Promise.all(
					promises
				) ) as AddCartItemResult[];

				// Every rejected child surfaces its own raw-text error
				// notice, in items order; accepted children produce none.
				const failedResults = results.filter(
					(
						result
					): result is Extract<
						AddCartItemResult,
						{ success: false }
					> => ! result.success
				);

				if ( failedResults.length > 0 ) {
					// Todo: Use the module exports instead of `store()` once
					// the store-notices store is public.
					yield import( '@woocommerce/stores/store-notices' );
					const { actions: noticeActions } = store< StoreNotices >(
						'woocommerce/store-notices',
						{},
						{ lock: universalLock }
					);

					failedResults.forEach( ( result ) => {
						noticeActions.addNotice( {
							notice: result.error.message,
							type: 'error',
							dismissible: true,
						} );
					} );
				}

				// Fire the once-per-batch effects only if at least one child
				// succeeded. When every child is rejected none of the three
				// fire, matching the removed batch action's fire-zero
				// behavior.
				const anySucceeded = results.some(
					( result ) => result.success
				);

				if ( anySucceeded ) {
					triggerAddedToCartEvent( { preserveCartData: true } );

					emitSyncEvent( {
						quantityChanges: {
							productsPendingAdd: addedItems.map(
								( item ) => item.id
							),
						},
					} );

					const addedToCartText = (
						getConfig( 'woocommerce' ) as
							| WooCommerceConfig
							| undefined
					 )?.messages?.addedToCartText;

					if ( addedToCartText ) {
						const { speak } =
							( yield a11yModulePromise ) as Awaited<
								typeof a11yModulePromise
							>;
						speak( addedToCartText, 'polite' );
					}
				}
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
