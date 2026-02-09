/**
 * External dependencies
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';
import type {
	Store as WooCommerce,
	SelectedAttributes,
} from '@woocommerce/stores/woocommerce/cart';
import '@woocommerce/stores/woocommerce/product-data';
import '@woocommerce/stores/woocommerce/products';
import type { Store as StoreNotices } from '@woocommerce/stores/store-notices';
import type { ProductDataStore } from '@woocommerce/stores/woocommerce/product-data';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import { doesCartItemMatchAttributes } from '../../base/utils/variations/does-cart-item-match-attributes';
import { findMatchingVariation } from '../../base/utils/variations/attribute-matching';
import type { GroupedProductAddToCartWithOptionsStore } from './grouped-product-selector/frontend';
import type { Context as QuantitySelectorContext } from './quantity-selector/frontend';
import type { VariableProductAddToCartWithOptionsStore } from './variation-selector/frontend';
import type { NormalizedProductData, NormalizedVariationData } from './types';

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

const { state: wooState } = store< WooCommerce >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

const { state: productDataState } = store< ProductDataStore >(
	'woocommerce/product-data',
	{},
	{ lock: universalLock }
);

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

export const getProductData = (
	id: number,
	selectedAttributes: SelectedAttributes[]
): NormalizedProductData | NormalizedVariationData | null => {
	const productFromStore = productsState.products[ id ];

	if ( ! productFromStore ) {
		return null;
	}

	// Determine which product to use for the response.
	let product = productFromStore;

	// For variable products with selected attributes, find the matching variation.
	if (
		productFromStore.type === 'variable' &&
		selectedAttributes?.length > 0
	) {
		const matchedVariation = findMatchingVariation(
			productFromStore,
			selectedAttributes
		);

		if ( matchedVariation ) {
			const variation =
				productsState.productVariations[ matchedVariation.id ];
			if ( ! variation ) {
				// Variation was matched but its data isn't in the store.
				// Return null to prevent using stale parent product data.
				return null;
			}
			product = variation;
		}
	}

	const { add_to_cart: addToCart } = product;
	const maximum = addToCart?.maximum ?? 0;

	return {
		id: product.id,
		type: product.type,
		is_in_stock: product.is_purchasable && product.is_in_stock,
		sold_individually: product.sold_individually,
		min: addToCart?.minimum ?? 1,
		max: maximum > 0 ? maximum : Number.MAX_SAFE_INTEGER,
		step: addToCart?.multiple_of ?? 1,
	};
};

export const getNewQuantity = (
	productId: number,
	quantity: number,
	variation?: SelectedAttributes[]
) => {
	const product = wooState.cart?.items.find( ( item ) => {
		if ( item.type === 'variation' ) {
			// If it's a variation, check that attributes match.
			// While different variations have different attributes,
			// some variations might accept 'Any' value for an attribute,
			// in which case, we need to check that the attributes match.
			if (
				item.id !== productId ||
				! item.variation ||
				! variation ||
				item.variation.length !== variation.length
			) {
				return false;
			}
			return doesCartItemMatchAttributes( item, variation );
		}

		return item.id === productId;
	} );
	const currentQuantity = product?.quantity || 0;
	return currentQuantity + quantity;
};

export type AddToCartWithOptionsStore = {
	state: {
		noticeIds: string[];
		validationErrors: AddToCartError[];
		isFormValid: boolean;
		allowsAddingToCart: boolean;
		quantity: Record< number, number >;
		selectedAttributes: SelectedAttributes[];
		productData: NormalizedProductData | NormalizedVariationData | null;
	};
	actions: {
		validateQuantity: ( productId: number, value?: number ) => void;
		setQuantity: ( productId: number, value: number ) => void;
		addError: ( error: AddToCartError ) => string;
		clearErrors: ( group?: string ) => void;
		addToCart: () => void;
		handleSubmit: ( event: SubmitEvent ) => void;
	};
};

const { actions, state } = store<
	AddToCartWithOptionsStore &
		Partial< GroupedProductAddToCartWithOptionsStore > &
		Partial< VariableProductAddToCartWithOptionsStore >
>(
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
				const { productData } = state;

				// For grouped products, the button should always be visible.
				// Its enabled/disabled state is controlled by isFormValid which
				// checks whether any child products are selected.
				if ( productData?.type === 'grouped' ) {
					return true;
				}

				return productData?.is_in_stock ?? true;
			},
			get quantity(): Record< number, number > {
				const context = getContext< Context >();
				return context.quantity;
			},
			get selectedAttributes(): SelectedAttributes[] {
				const context = getContext< Context >();
				return context.selectedAttributes || [];
			},
			get productData() {
				const { selectedAttributes } = getContext< Context >();

				return getProductData(
					productDataState.productId,
					selectedAttributes
				);
			},
		},
		actions: {
			validateQuantity( productId: number, value?: number ) {
				actions.clearErrors( 'invalid-quantities' );

				if ( typeof value !== 'number' ) {
					return;
				}

				const { selectedAttributes } = getContext< Context >();

				// If selected quantity is invalid, add an error.
				const productObject = getProductData(
					productId,
					selectedAttributes
				);

				if (
					value === 0 ||
					( productObject &&
						( value < productObject.min ||
							value > productObject.max ) )
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

				// Get variations from the products store.
				const productFromStore = productsState.products[ productId ];
				const variationIds =
					productFromStore?.variations?.map( ( v ) => v.id ) ?? [];

				if ( variationIds.length > 0 ) {
					// Set the quantity for all variations, so when switching
					// variations the quantity persists.
					const idsToUpdate = [ productId, ...variationIds ];

					idsToUpdate.forEach( ( id ) => {
						if ( isValueNaN ) {
							// Null the value first before setting the real value to ensure that
							// a signal update happens.
							context.quantity[ Number( id ) ] = null;
						}

						context.quantity[ Number( id ) ] = value;
					} );
				} else {
					if ( isValueNaN ) {
						// Null the value first before setting the real value to ensure that
						// a signal update happens.
						context.quantity = {
							...context.quantity,
							[ productId ]: null,
						};
					}

					context.quantity = {
						...context.quantity,
						[ productId ]: value,
					};
				}

				if ( state.productData?.type === 'grouped' ) {
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
			*addToCart() {
				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				yield import( '@woocommerce/stores/woocommerce/cart' );

				const { selectedAttributes } = getContext< Context >();

				const id =
					productDataState.variationId || productDataState.productId;

				const productType = productDataState.variationId
					? 'variation'
					: getProductData( id, selectedAttributes )?.type;

				if ( ! productType ) {
					return;
				}

				if ( productType === 'grouped' ) {
					yield actions.batchAddToCart();
					return;
				}

				const { quantity } = getContext< Context >();

				const newQuantity = getNewQuantity(
					id,
					quantity[ id ],
					selectedAttributes
				);

				const { actions: wooActions } = store< WooCommerce >(
					'woocommerce',
					{},
					{ lock: universalLock }
				);
				yield wooActions.addCartItem(
					{
						id,
						quantity: newQuantity,
						variation: selectedAttributes,
						type: productType,
					},
					{
						showCartUpdatesNotices: false,
					}
				);
			},
			*handleSubmit( event: SubmitEvent ) {
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

				yield actions.addToCart();
			},
		},
	},
	{ lock: universalLock }
);
