/**
 * External dependencies
 */
import type { FormEvent, HTMLElementEvent } from 'react';
import { store, getContext } from '@wordpress/interactivity';
import type { Store as WooCommerce } from '@woocommerce/stores/woocommerce/cart';
import type { CartVariationItem } from '@woocommerce/types';
import '@woocommerce/stores/woocommerce/product-data';
import type { ProductDataStore } from '@woocommerce/stores/woocommerce/product-data';

export type AvailableVariation = {
	attributes: Record< string, string >;
	variation_id: number;
	price_html: string;
};

export type Context = {
	productId: number;
	productType: string;
	selectedAttributes: CartVariationItem[];
	availableVariations: AvailableVariation[];
	quantity: Record< number, number >;
	tempQuantity: number;
	groupedProductIds: number[];
};

interface GroupedCartItem {
	id: number;
	quantity: number;
	variation: CartVariationItem[];
}

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: wooState } = store< WooCommerce >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

const getInputElementFromEvent = (
	event: HTMLElementEvent< HTMLButtonElement, HTMLInputElement >
) => {
	let inputElement = null;

	if ( event.target instanceof HTMLButtonElement ) {
		inputElement = event.target.parentElement?.querySelector(
			'.input-text.qty.text'
		);
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
	const parsedMinValue = parseInt( inputElement.min, 10 );
	const parsedMaxValue = parseInt( inputElement.max, 10 );
	const parsedStep = parseInt( inputElement.step, 10 );

	const currentValue = isNaN( parsedValue ) ? 0 : parsedValue;
	const minValue = isNaN( parsedMinValue ) ? 1 : parsedMinValue;
	const maxValue = isNaN( parsedMaxValue ) ? undefined : parsedMaxValue;
	const step = isNaN( parsedStep ) ? 1 : parsedStep;
	const childProductId = parseInt(
		inputElement.name.match( /\[(\d+)\]/ )?.[ 1 ] ?? '0',
		10
	);

	return {
		currentValue,
		minValue,
		maxValue,
		step,
		childProductId,
		inputElement,
	};
};

const getMatchedVariation = (
	availableVariations: AvailableVariation[],
	selectedAttributes: CartVariationItem[]
) => {
	if (
		! Array.isArray( availableVariations ) ||
		! Array.isArray( selectedAttributes ) ||
		availableVariations.length === 0 ||
		selectedAttributes.length === 0
	) {
		return null;
	}
	return availableVariations.find( ( availableVariation ) => {
		return Object.entries( availableVariation.attributes ).every(
			( [ attributeName, attributeValue ] ) => {
				const attributeMatched = selectedAttributes.some(
					( variationAttribute ) => {
						const isSameAttribute =
							variationAttribute.attribute === attributeName;
						if ( ! isSameAttribute ) {
							return false;
						}

						return (
							variationAttribute.value === attributeValue ||
							( variationAttribute.value &&
								attributeValue === '' )
						);
					}
				);

				return attributeMatched;
			}
		);
	} );
};

const getNewQuantity = ( productId: number, quantity: number ) => {
	const product = wooState.cart?.items.find(
		( item ) => item.id === productId
	);
	const currentQuantity = product?.quantity || 0;
	return currentQuantity + quantity;
};

const dispatchChangeEvent = ( inputElement: HTMLInputElement ) => {
	const event = new Event( 'change' );
	inputElement.dispatchEvent( event );
};

const addToCartWithOptionsStore = store(
	'woocommerce/add-to-cart-with-options',
	{
		state: {
			get isFormValid(): boolean {
				const { availableVariations, selectedAttributes, productType } =
					getContext< Context >();
				if ( productType !== 'variable' ) {
					return true;
				}
				const matchedVariation = getMatchedVariation(
					availableVariations,
					selectedAttributes
				);
				return !! matchedVariation?.variation_id;
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
		},
		actions: {
			setQuantity( value: number, childProductId?: number ) {
				const context = getContext< Context >();
				const productId =
					childProductId && childProductId > 0
						? childProductId
						: context.productId;

				context.quantity = {
					...context.quantity,
					[ productId ]: value,
				};
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
					step,
					childProductId,
					inputElement,
				} = inputData;
				const newValue = currentValue + step;

				if ( maxValue === undefined || newValue <= maxValue ) {
					addToCartWithOptionsStore.actions.setQuantity(
						newValue,
						childProductId
					);
					inputElement.value = newValue.toString();
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
					minValue,
					step,
					childProductId,
					inputElement,
				} = inputData;
				const newValue = currentValue - step;

				if ( newValue >= minValue ) {
					addToCartWithOptionsStore.actions.setQuantity(
						newValue,
						childProductId
					);
					inputElement.value = newValue.toString();
					dispatchChangeEvent( inputElement );
				}
			},
			handleQuantityInputChange: (
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
						const newQuantity = getNewQuantity(
							childProductId,
							quantity[ childProductId ]
						);

						if ( newQuantity === 0 ) {
							continue;
						}

						addedItems.push( {
							id: childProductId,
							quantity: newQuantity,
							variation: selectedAttributes,
						} );
					}

					if ( addedItems.length === 0 ) {
						return;
					}

					const { actions } = store< WooCommerce >(
						'woocommerce',
						{},
						{ lock: universalLock }
					);

					yield actions.batchAddCartItems( addedItems );
				} else {
					const newQuantity = getNewQuantity(
						productId,
						quantity[ productId ]
					);

					const { actions } = store< WooCommerce >(
						'woocommerce',
						{},
						{ lock: universalLock }
					);

					yield actions.addCartItem( {
						id: productId,
						quantity: newQuantity,
						variation: selectedAttributes,
					} );
				}
			},
		},
		callbacks: {
			setProductData: () => {
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

				if ( matchedVariation ) {
					actions.setProductData(
						'price_html',
						matchedVariation.price_html
					);
				} else {
					actions.setProductData( 'price_html', null );
				}
			},
		},
	},
	{ lock: true }
);

export type AddToCartWithOptionsStore = typeof addToCartWithOptionsStore;
