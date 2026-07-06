/**
 * External dependencies
 */
import {
	store,
	getContext,
	getConfig,
	withSyncEvent,
} from '@wordpress/interactivity';
import type {
	Store as WooCommerce,
	SelectedAttributes,
} from '@woocommerce/stores/woocommerce/cart';
import type { Store as StoreNotices } from '@woocommerce/stores/store-notices';
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import type { GroupedProductAddToCartWithOptionsStore } from './grouped-product-selector/frontend';
import type { Context as QuantitySelectorContext } from './quantity-selector/frontend';
import type { VariableProductAddToCartWithOptionsStore } from './variation-selector/frontend';
import { getContextProductId, getDraft, setDraftQuantity } from './cart-drafts';

export type Context = {
	selectedAttributes: SelectedAttributes[];
	quantity: Record< number, number >;
	validationErrors: AddToCartError[];
	tempQuantity: number;
	groupedProductIds: number[];
};

export type AddToCartError = {
	code: string;
	group: string;
	message: string;
};

/**
 * Manually dispatches a 'change' event on the quantity input element.
 *
 * When users click the plus/minus stepper buttons, no 'change' event is fired
 * since there is no direct interaction with the input. However, some extensions
 * rely on the change event to detect quantity changes. This function ensures
 * those extensions continue working by programmatically dispatching the event.
 *
 * @see https://github.com/woocommerce/woocommerce/issues/53031
 *
 * @param inputElement - The quantity input element to dispatch the event on.
 */
const dispatchChangeEvent = ( inputElement: HTMLInputElement ) => {
	const event = new Event( 'change', { bubbles: true } );
	inputElement.dispatchEvent( event );
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
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
		validateQuantity: ( productId: number, value?: number ) => void;
		setQuantity: ( productId: number, value: number ) => void;
		addError: ( error: AddToCartError ) => string;
		clearErrors: ( group?: string ) => void;
		addToCart: ( event: SubmitEvent ) => void;
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
			validateQuantity( productId: number, value?: number ) {
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
			setQuantity( productId: number, value: number ) {
				const context = getContext< Context >();
				const quantitySelectorContext =
					getContext< QuantitySelectorContext >(
						'woocommerce/add-to-cart-with-options-quantity-selector'
					);
				const inputElement = quantitySelectorContext?.inputElement;

				// Shopper quantity is now owned by the shared-store draft (T6):
				// one draft per product context, keyed by the main/context
				// product id. `upsertDraftItem` creates it on first touch and
				// mutates it thereafter. The draft's quantity is
				// variation-independent, so the old per-variation-id fan-out
				// (which existed only to keep a `context.quantity` map in sync
				// across variation switches) is no longer needed.
				setDraftQuantity( productId, value );

				// Keep `context.quantity` populated as the compatibility surface
				// (server-seeded; still part of the block's public context shape).
				// `setDraftQuantity` owns the reactive-signal handling (including
				// the same-value NaN pre-write that forces the input to re-render).
				context.quantity = {
					...context.quantity,
					[ productId ]: value,
				};

				const parentProduct = productsState.findProduct( {
					id: productsState.productId,
					selectedAttributes: context.selectedAttributes,
				} );
				if ( parentProduct?.type === 'grouped' ) {
					actions.validateGroupedProductQuantity();
				} else {
					actions.validateQuantity( productId, value );
				}

				if ( inputElement ) {
					dispatchChangeEvent( inputElement );
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

				// Resolve the context draft SYNCHRONOUSLY, before any `yield`.
				// The draft read relies on the `woocommerce/products` context,
				// which is only guaranteed in scope for the synchronous portion of
				// the action (iAPI restores scope around directive-invoked actions,
				// but resolving up front and passing the draft explicitly to
				// `addItem` removes any dependency on scope surviving the async
				// import below).
				//
				// IMPORTANT: the draft is keyed by the products context product id
				// (`woocommerce/products::productId` — the MAIN/parent product,
				// identity rule 3), NOT by `productInContext.id`, which for variable
				// products resolves to the selected VARIATION's id. `getDraft()`
				// with no argument reads the products context. The draft already
				// carries `id`, `quantity` and the shopper's `variation` (the
				// variation selector mirrors the selection into it); `addItem`
				// posts parent id + variation and the server resolves the
				// purchasable variation.
				const contextProductId = getContextProductId();
				const draft = getDraft();

				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				yield import( '@woocommerce/stores/woocommerce/cart' );

				const { actions: wooActions } = store< WooCommerce >(
					'woocommerce/cart',
					{},
					{ lock: universalLock }
				);

				// Submit the draft (identity rule 5: adds are adds). Passing it
				// explicitly (rather than relying on `addItem()`'s context
				// default) keeps submission deterministic across the async
				// boundary. When no draft exists (defensive — the form seeds one
				// server-side), fall back to the context product id so a variable
				// selection still travels as parent id semantics. The draft is
				// NOT reset after a successful add — matching current UX
				// (attribute selection persists, and a repeat submit compounds
				// server-side); draft death is deliberately a no-op here (see the
				// T6 report / draft-lifecycle note in the schema).
				yield wooActions.addItem(
					draft ?? {
						id: contextProductId ?? product.id,
						quantity: 1,
					}
				);
			} ),
		},
	},
	{ lock: universalLock }
);
