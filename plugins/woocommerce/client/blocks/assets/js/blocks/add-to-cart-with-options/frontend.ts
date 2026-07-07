/**
 * External dependencies
 */
import {
	store,
	getContext,
	getConfig,
	withSyncEvent,
} from '@wordpress/interactivity';
import type { Store as WooCommerce } from '@woocommerce/stores/woocommerce/cart';
import type { Store as StoreNotices } from '@woocommerce/stores/store-notices';
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';
// The cart store is a hard dependency: quantity writers and `addToCart` call its
// actions directly, and the form's validation watch reads `itemInContext.draft`.
// Static import guarantees the module (and its store registration) is loaded on
// every page carrying this form.
import '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import type { GroupedProductAddToCartWithOptionsStore } from './grouped-product-selector/frontend';
import type { VariableProductAddToCartWithOptionsStore } from './variation-selector/frontend';

export type Context = {
	validationErrors: AddToCartError[];
	groupedProductIds: number[];
};

export type AddToCartError = {
	code: string;
	group: string;
	message: string;
};

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

export type AddToCartWithOptionsStore = {
	state: {
		noticeIds: string[];
		validationErrors: AddToCartError[];
		isFormValid: boolean;
		allowsAddingToCart: boolean;
	};
	actions: {
		validateQuantity: ( value?: number ) => void;
		addError: ( error: AddToCartError ) => string;
		clearErrors: ( group?: string ) => void;
		addToCart: ( event: SubmitEvent ) => void;
	};
	callbacks: {
		validateQuantityConstraints: () => void;
	};
};

type MergedAddToCartWithOptionsStores = AddToCartWithOptionsStore &
	Partial< GroupedProductAddToCartWithOptionsStore > &
	Partial< VariableProductAddToCartWithOptionsStore >;

const { state } = store< MergedAddToCartWithOptionsStores >(
	'woocommerce/add-to-cart-with-options',
	{},
	{ lock: universalLock }
);
const { actions } = store< MergedAddToCartWithOptionsStores >(
	'woocommerce/add-to-cart-with-options',
	{
		state: {
			noticeIds: [],
			get validationErrors(): Array< AddToCartError > {
				const context = getContext< Context >();

				if ( context && context.validationErrors ) {
					return context.validationErrors;
				}

				return [];
			},
			get isFormValid(): boolean {
				return state.validationErrors.length === 0;
			},
			get allowsAddingToCart(): boolean {
				const product = productsState.productInContext;

				if ( ! product ) {
					return false;
				}

				// For grouped products, the button should always be visible.
				// Its enabled/disabled state is controlled by isFormValid which
				// checks whether any child products are selected.
				if ( product.type === 'grouped' ) {
					return true;
				}

				return product.is_purchasable && product.is_in_stock;
			},
		},
		actions: {
			validateQuantity( value?: number ) {
				actions.clearErrors( 'invalid-quantities' );

				if ( typeof value !== 'number' ) {
					return;
				}

				// If selected quantity is invalid, add an error.
				const product = productsState.productInContext;

				if (
					value === 0 ||
					( product &&
						( value < product.add_to_cart.minimum ||
							value > product.add_to_cart.maximum ) )
				) {
					const { errorMessages } = getConfig();

					actions.addError( {
						code: 'invalidQuantities',
						message: errorMessages?.invalidQuantities || '',
						group: 'invalid-quantities',
					} );
				}
			},
			addError: ( error: AddToCartError ): string => {
				const { validationErrors } = state;

				validationErrors.push( error );

				return error.code;
			},
			clearErrors: ( group?: string ): void => {
				const { validationErrors } = state;

				if ( group ) {
					const remaining = validationErrors.filter(
						( error ) => error.group !== group
					);
					validationErrors.splice(
						0,
						validationErrors.length,
						...remaining
					);
				} else {
					// Clear all.
					validationErrors.length = 0;
				}
			},
			addToCart: withSyncEvent( function* ( event: SubmitEvent ) {
				event.preventDefault();

				const { isFormValid } = state;

				if ( ! isFormValid ) {
					// Dynamically import the store module first
					yield import( '@woocommerce/stores/store-notices' );

					const { actions: noticeActions } = store< StoreNotices >(
						'woocommerce/store-notices',
						{},
						{
							lock: universalLock,
						}
					);

					const { noticeIds, validationErrors } = state;

					// Clear previous notices.
					noticeIds.forEach( ( id ) => {
						noticeActions.removeNotice( id );
					} );
					noticeIds.splice( 0, noticeIds.length );

					// Add new notices and track their IDs.
					const newNoticeIds = validationErrors.map( ( error ) =>
						noticeActions.addNotice( {
							notice: error.message,
							type: 'error',
							dismissible: true,
						} )
					);

					// Store the new IDs in-place.
					noticeIds.push( ...newNoticeIds );

					return;
				}

				const product = productsState.productInContext;

				if ( ! product ) {
					return;
				}

				if ( product.type === 'grouped' ) {
					yield actions.batchAddToCart();
					return;
				}

				// Submit the context draft. Adds are always adds — never converted
				// to an update of an existing line by client-side matching. The
				// draft — keyed by the shared context product id — already carries
				// the shopper's quantity and variation; `addItem()` resolves it,
				// swaps in the purchasable variation id, and POSTs add-item.
				// Falls back to the min-purchase product id when no draft exists
				// (a form the shopper never touched). The draft is NOT reset after
				// a successful add: attribute selection and quantity persist, and a
				// repeat submit compounds server-side.
				yield cartActions.addItem();
			} ),
		},
		callbacks: {
			// Quantity validation watch: re-runs whenever the context draft's
			// quantity changes. Simple/variable products validate the quantity
			// against the product's min/max; grouped products validate their
			// child quantities. Replaces the old `setQuantity` write-path side
			// effect now that quantity writers upsert the draft directly.
			validateQuantityConstraints() {
				const product = productsState.productInContext;
				if ( ! product ) {
					return;
				}

				if ( product.type === 'grouped' ) {
					actions.validateGroupedProductQuantity();
					return;
				}

				actions.validateQuantity(
					cartState.itemInContext.draft?.quantity
				);
			},
		},
	},
	{ lock: universalLock }
);
