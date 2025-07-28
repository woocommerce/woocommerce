/**
 * External dependencies
 */
import type { FormEvent, HTMLElementEvent } from 'react';
import { store, getContext } from '@wordpress/interactivity';
import type {
	Store as WooCommerce,
	SelectedAttributes,
} from '@woocommerce/stores/woocommerce/cart';
import '@woocommerce/stores/woocommerce/product-data';
import type { ProductDataStore } from '@woocommerce/stores/woocommerce/product-data';
import type { Store as StoreNotices } from '@woocommerce/stores/store-notices';

/**
 * Internal dependencies
 */
import {
	getMatchedVariation,
	type AvailableVariation,
} from '../../base/utils/variations/get-matched-variation';
import { doesCartItemMatchAttributes } from '../../base/utils/variations/does-cart-item-match-attributes';

export type Context = {
	productId: number;
	productType: string;
	selectedAttributes: SelectedAttributes[];
	availableVariations: AvailableVariation[];
	quantity: Record< number, number >;
	tempQuantity: number;
	groupedProductIds: number[];
	childProductId: number;
	quantityConstraints: Record<
		number,
		{ min: number; max: number | null; step: number }
	>;
};

interface GroupedCartItem {
	id: number;
	quantity: number;
	variation: SelectedAttributes[];
	type: string;
}

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: wooState } = store< WooCommerce >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

const getDefaultConstraints = (
	productType: string,
	childProductId?: number
) => ( {
	min: productType === 'grouped' && childProductId ? 0 : 1,
	step: 1,
	max: null,
} );

const getInputElementFromEvent = (
	event: HTMLElementEvent< HTMLButtonElement, HTMLInputElement >
) => {
	let inputElement = null;

	if ( event.target instanceof HTMLButtonElement ) {
		inputElement = event.target.parentElement?.querySelector( '.qty' );
	}

	if ( event.target instanceof HTMLInputElement ) {
		inputElement = event.target;
	}

	return inputElement;
};

const getInputData = (
	event: HTMLElementEvent< HTMLButtonElement, HTMLInputElement >
) => {
	const inputElement = getInputElementFromEvent( event );

	if ( ! inputElement ) {
		return;
	}

	const parsedValue = parseInt( inputElement.value, 10 );
	const { productType, productId, quantityConstraints } =
		getContext< Context >();
	const childProductId = parseInt(
		inputElement.name.match( /\[(\d+)\]/ )?.[ 1 ] ?? '0',
		10
	);
	const id = childProductId || productId;
	const constraints =
		quantityConstraints?.[ id ] ||
		getDefaultConstraints( productType, childProductId );
	const minValue = constraints.min;
	const maxValue = constraints.max;
	const step = constraints.step;

	const currentValue = isNaN( parsedValue ) ? 0 : parsedValue;

	return {
		currentValue,
		minValue,
		maxValue,
		step,
		childProductId,
		inputElement,
	};
};

const getNewQuantity = (
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

const dispatchChangeEvent = ( inputElement: HTMLInputElement ) => {
	const event = new Event( 'change', { bubbles: true } );
	inputElement.dispatchEvent( event );
};

const addToCartWithOptionsStore = store(
	'woocommerce/add-to-cart-with-options',
	{
		state: {
			get isFormValid(): boolean {
				const context = getContext< Context >();
				if ( ! context ) {
					return true;
				}
				const {
					availableVariations,
					selectedAttributes,
					productType,
					quantity,
				} = context;
				if ( productType === 'variable' ) {
					const matchedVariation = getMatchedVariation(
						availableVariations,
						selectedAttributes
					);

					// Variable products must be in stock and have a selected variation
					return Boolean(
						matchedVariation?.is_in_stock &&
							matchedVariation?.variation_id
					);
				}
				if ( productType === 'grouped' ) {
					return Object.values( quantity ).some( ( qty ) => qty > 0 );
				}
				return true;
			},
			get variationId(): number | null {
				const context = getContext< Context >();
				if ( ! context ) {
					return null;
				}
				const { availableVariations, selectedAttributes } = context;
				const matchedVariation = getMatchedVariation(
					availableVariations,
					selectedAttributes
				);
				return matchedVariation?.variation_id || null;
			},
			get selectedAttributes(): SelectedAttributes[] {
				const context = getContext< Context >();
				if ( ! context ) {
					return [];
				}
				return context.selectedAttributes;
			},
			get allowsDecrease() {
				const {
					quantity,
					childProductId,
					productType,
					quantityConstraints,
					productId,
					availableVariations,
					selectedAttributes,
				} = getContext< Context >();

				const matchedVariation = getMatchedVariation(
					availableVariations,
					selectedAttributes
				);

				const id =
					matchedVariation?.variation_id ||
					childProductId ||
					productId;
				const currentQuantity = quantity[ id ] || 0;
				const constraints =
					quantityConstraints?.[ id ] ||
					getDefaultConstraints( productType, childProductId );
				const minValue = constraints.min;
				const step = constraints.step;
				return currentQuantity - step >= minValue;
			},
			get allowsIncrease() {
				const {
					quantity,
					childProductId,
					productType,
					quantityConstraints,
					productId,
					availableVariations,
					selectedAttributes,
				} = getContext< Context >();

				const matchedVariation = getMatchedVariation(
					availableVariations,
					selectedAttributes
				);

				const id =
					matchedVariation?.variation_id ||
					childProductId ||
					productId;
				const currentQuantity = quantity[ id ] || 0;
				const constraints =
					quantityConstraints?.[ id ] ||
					getDefaultConstraints( productType, childProductId );
				const maxValue = constraints.max;
				const step = constraints.step;
				return maxValue === null || currentQuantity + step <= maxValue;
			},
		},
		actions: {
			setQuantity( value: number, childProductId?: number ) {
				const context = getContext< Context >();

				if ( context.productType === 'variable' ) {
					// Set the quantity for all variations, so when switching
					// variations the quantity persists.
					const variationIds = context.availableVariations.map(
						( variation ) => variation.variation_id
					);

					variationIds.forEach( ( id ) => {
						context.quantity[ id ] = value;
					} );
				} else {
					const id = childProductId || context.productId;

					context.quantity = {
						...context.quantity,
						[ id ]: value,
					};
				}
			},
			setAttribute( attribute: string, value: string ) {
				const { selectedAttributes } = getContext< Context >();
				const index = selectedAttributes.findIndex(
					( selectedAttribute ) =>
						selectedAttribute.attribute === attribute
				);

				if ( index >= 0 ) {
					selectedAttributes[ index ] = {
						attribute,
						value,
					};
				} else {
					selectedAttributes.push( {
						attribute,
						value,
					} );
				}
			},
			removeAttribute( attribute: string ) {
				const { selectedAttributes } = getContext< Context >();
				const index = selectedAttributes.findIndex(
					( selectedAttribute ) =>
						selectedAttribute.attribute === attribute
				);
				if ( index >= 0 ) {
					selectedAttributes.splice( index, 1 );
				}
			},
			increaseQuantity: (
				event: HTMLElementEvent< HTMLButtonElement >
			) => {
				const inputData = getInputData( event );
				if ( ! inputData ) {
					return;
				}
				const {
					currentValue,
					maxValue,
					minValue,
					step,
					childProductId,
					inputElement,
				} = inputData;
				const newValue = currentValue + step;

				if ( maxValue === null || newValue <= maxValue ) {
					const updatedValue = Math.max( minValue, newValue );
					addToCartWithOptionsStore.actions.setQuantity(
						updatedValue,
						childProductId
					);
					inputElement.value = updatedValue.toString();
					dispatchChangeEvent( inputElement );
				}
			},
			decreaseQuantity: (
				event: HTMLElementEvent< HTMLButtonElement >
			) => {
				const inputData = getInputData( event );
				if ( ! inputData ) {
					return;
				}
				const {
					currentValue,
					maxValue,
					minValue,
					step,
					childProductId,
					inputElement,
				} = inputData;
				const newValue = currentValue - step;

				if ( newValue >= minValue ) {
					const updatedValue = Math.min(
						maxValue ?? Infinity,
						newValue
					);
					addToCartWithOptionsStore.actions.setQuantity(
						updatedValue,
						childProductId
					);
					inputElement.value = updatedValue.toString();
					dispatchChangeEvent( inputElement );
				}
			},
			handleQuantityInput: (
				event: HTMLElementEvent< HTMLInputElement >
			) => {
				const inputData = getInputData( event );
				if ( ! inputData ) {
					return;
				}
				const { childProductId, currentValue } = inputData;

				addToCartWithOptionsStore.actions.setQuantity(
					currentValue,
					childProductId
				);
			},
			handleQuantityChange: (
				event: HTMLElementEvent< HTMLInputElement >
			) => {
				const inputData = getInputData( event );
				if ( ! inputData ) {
					return;
				}
				const { childProductId, maxValue, minValue, currentValue } =
					inputData;

				const newValue = Math.min(
					maxValue ?? Infinity,
					Math.max( minValue, currentValue )
				);

				if ( event.target.value !== newValue.toString() ) {
					addToCartWithOptionsStore.actions.setQuantity(
						newValue,
						childProductId
					);
					event.target.value = newValue.toString();
					dispatchChangeEvent( event.target );
				}
			},
			handleQuantityCheckboxChange: (
				event: HTMLElementEvent< HTMLInputElement >
			) => {
				const inputData = getInputData( event );
				if ( ! inputData ) {
					return;
				}
				const { inputElement, childProductId } = inputData;

				addToCartWithOptionsStore.actions.setQuantity(
					inputElement.checked ? 1 : 0,
					childProductId
				);
			},
			*handleSubmit( event: FormEvent< HTMLFormElement > ) {
				event.preventDefault();

				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				yield import( '@woocommerce/stores/woocommerce/cart' );

				const {
					productId,
					quantity,
					selectedAttributes,
					productType,
					groupedProductIds,
				} = getContext< Context >();

				if (
					productType === 'grouped' &&
					groupedProductIds.length > 0
				) {
					const addedItems: GroupedCartItem[] = [];

					for ( const childProductId of groupedProductIds ) {
						if ( quantity[ childProductId ] === 0 ) {
							continue;
						}

						const newQuantity = getNewQuantity(
							childProductId,
							quantity[ childProductId ]
						);

						addedItems.push( {
							id: childProductId,
							quantity: newQuantity,
							variation: selectedAttributes,
							type: productType,
						} );
					}

					if ( addedItems.length === 0 ) {
						// Todo: Use the module exports instead of `store()` once the store-notices
						// store is public.
						yield import( '@woocommerce/stores/store-notices' );
						const { actions: noticeActions } =
							store< StoreNotices >(
								'woocommerce/store-notices',
								{},
								{
									lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
								}
							);

						const errorMessage =
							wooState?.errorMessages
								?.groupedProductAddToCartMissingItems;

						if ( errorMessage ) {
							noticeActions.addNotice( {
								notice: errorMessage,
								type: 'error',
								dismissible: true,
							} );
						}

						return;
					}

					const { actions } = store< WooCommerce >(
						'woocommerce',
						{},
						{ lock: universalLock }
					);

					yield actions.batchAddCartItems( addedItems );
				} else {
					const { variationId } = addToCartWithOptionsStore.state;
					const id = variationId || productId;
					const newQuantity = getNewQuantity(
						id,
						quantity[ id ],
						selectedAttributes
					);

					const { actions } = store< WooCommerce >(
						'woocommerce',
						{},
						{ lock: universalLock }
					);
					yield actions.addCartItem( {
						id,
						quantity: newQuantity,
						variation: selectedAttributes,
						type: productType,
					} );
				}
			},
		},
		callbacks: {
			setSelectedVariationId: () => {
				const { availableVariations, selectedAttributes } =
					getContext< Context >();
				const matchedVariation = getMatchedVariation(
					availableVariations,
					selectedAttributes
				);

				const { actions } = store< ProductDataStore >(
					'woocommerce/product-data',
					{},
					{ lock: universalLock }
				);
				const matchedVariationId = matchedVariation?.variation_id;
				actions.setVariationId( matchedVariationId ?? null );
			},
		},
	},
	{ lock: true }
);

export type AddToCartWithOptionsStore = typeof addToCartWithOptionsStore;
