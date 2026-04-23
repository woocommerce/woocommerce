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
		quantity: Record< number, number >;
		selectedAttributes: SelectedAttributes[];
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
			get quantity(): Record< number, number > {
				const context = getContext< Context >();
				return context.quantity;
			},
			get selectedAttributes(): SelectedAttributes[] {
				const context = getContext< Context >();
				return context.selectedAttributes || [];
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
				const isValueNaN = Number.isNaN( inputElement?.valueAsNumber );

				const { mainProductInContext: productFromStore } =
					productsState;
				const variationIds =
					productFromStore?.variations?.map( ( v ) => v.id ) ?? [];

				if ( variationIds.length > 0 ) {
					// Set the quantity for all variations, so when switching
					// variations the quantity persists.
					const idsToUpdate = [ productId, ...variationIds ];

					idsToUpdate.forEach( ( id ) => {
						if ( isValueNaN ) {
							// Modify the value first before setting the real
							// value to ensure that a signal update happens.
							context.quantity[ Number( id ) ] = NaN;
						}

						context.quantity[ Number( id ) ] = value;
					} );
				} else {
					if ( isValueNaN ) {
						// Modify the value first before setting the real value
						// to ensure that a signal update happens.
						context.quantity = {
							...context.quantity,
							[ productId ]: NaN,
						};
					}

					context.quantity = {
						...context.quantity,
						[ productId ]: value,
					};
				}

				if ( productsState.mainProductInContext?.type === 'grouped' ) {
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

				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				yield import( '@woocommerce/stores/woocommerce/cart' );

				const product = productsState.productInContext;

				if ( ! product ) {
					return;
				}

				if ( product.type === 'grouped' ) {
					yield actions.batchAddToCart();
					return;
				}

				const { quantity, selectedAttributes } =
					getContext< Context >();

				const { actions: wooActions } = store< WooCommerce >(
					'woocommerce',
					{},
					{ lock: universalLock }
				);
				yield wooActions.addCartItem(
					{
						id: product.id,
						quantityToAdd: quantity[ product.id ],
						variation: selectedAttributes,
						type: product.type,
					},
					{
						showCartUpdatesNotices: false,
					}
				);
			} ),
		},
	},
	{ lock: universalLock }
);
