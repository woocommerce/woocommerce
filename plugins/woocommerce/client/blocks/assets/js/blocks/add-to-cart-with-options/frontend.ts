/**
 * External dependencies
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';
import type {
	Store as WooCommerce,
	SelectedAttributes,
	ProductData,
	VariationData,
	WooCommerceConfig,
} from '@woocommerce/stores/woocommerce/cart';
import '@woocommerce/stores/woocommerce/product-data';
import type { Store as StoreNotices } from '@woocommerce/stores/store-notices';
import type { ProductDataStore } from '@woocommerce/stores/woocommerce/product-data';

/**
 * Internal dependencies
 */
import { getMatchedVariation } from '../../base/utils/variations/get-matched-variation';
import { doesCartItemMatchAttributes } from '../../base/utils/variations/does-cart-item-match-attributes';
import type { GroupedProductAddToCartWithOptionsStore } from './grouped-product-selector/frontend';
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

export const getProductData = (
	id: number,
	selectedAttributes: SelectedAttributes[]
) => {
	let productId = id;
	let productData: ProductData | VariationData | undefined;

	const { products } = getConfig( 'woocommerce' ) as WooCommerceConfig;

	let type: ProductData[ 'type' ] | 'variation' | null = null;
	if ( selectedAttributes && selectedAttributes.length > 0 ) {
		if ( ! products || ! products[ id ] ) {
			return null;
		}
		const variations = products[ id ].variations;
		const matchedVariation = getMatchedVariation(
			variations,
			selectedAttributes
		);
		if ( matchedVariation?.variation_id ) {
			productId = matchedVariation.variation_id;
			productData = products?.[ id ]?.variations?.[
				matchedVariation?.variation_id
			] as VariationData;
			type = 'variation';
		}
	} else {
		productData = products?.[ productId ] as ProductData;
		type = productData?.type;
	}

	if ( typeof productData !== 'object' || productData === null ) {
		return null;
	}

	const min = typeof productData.min === 'number' ? productData.min : 1;
	const max =
		typeof productData.max === 'number' && productData.max >= 1
			? productData.max
			: Infinity;
	const step = productData.step || 1;

	return {
		id: productId,
		...productData,
		min,
		max,
		step,
		type,
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
		isFormValid: boolean;
		noticeIds: string[];
		validationErrors: AddToCartError[];
		quantity: Record< number, number >;
		selectedAttributes: SelectedAttributes[];
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
			get quantity(): Record< number, number > {
				const context = getContext< Context >();
				return context.quantity || {};
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
				const { products } = getConfig(
					'woocommerce'
				) as WooCommerceConfig;
				const variations = products?.[ productId ].variations;

				if ( variations ) {
					const variationIds = Object.keys( variations );
					// Set the quantity for all variations, so when switching
					// variations the quantity persists.
					const idsToUpdate = [ productId, ...variationIds ];

					idsToUpdate.forEach( ( id ) => {
						context.quantity[ Number( id ) ] = value;
					} );
				} else {
					context.quantity = {
						...context.quantity,
						[ productId ]: value,
					};
				}

				const productObject = getProductData(
					productDataState.productId,
					context.selectedAttributes
				);
				if ( productObject?.type === 'grouped' ) {
					actions.validateGroupedProductQuantity();
				} else {
					actions.validateQuantity( productId, value );
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
