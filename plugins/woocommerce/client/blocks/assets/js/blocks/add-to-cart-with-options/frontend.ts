/**
 * External dependencies
 */
import type { FormEvent, HTMLElementEvent } from 'react';
import { store, getContext } from '@wordpress/interactivity';
import type {
	Store as WooCommerce,
	SelectedAttributes,
	ProductData,
} from '@woocommerce/stores/woocommerce/cart';
import '@woocommerce/stores/woocommerce/product-data';
import type { Store as StoreNotices } from '@woocommerce/stores/store-notices';

/**
 * Internal dependencies
 */
import {
	getMatchedVariation,
	type AvailableVariation,
} from '../../base/utils/variations/get-matched-variation';
import { doesCartItemMatchAttributes } from '../../base/utils/variations/does-cart-item-match-attributes';
import type { GroupedProductAddToCartWithOptionsStore } from './grouped-product-selector/frontend';
import type { VariableProductAddToCartWithOptionsStore } from './variation-selector/frontend';

export type Context = {
	productId: number;
	productType: string;
	selectedAttributes: SelectedAttributes[];
	availableVariations: AvailableVariation[];
	quantity: Record< number, number >;
	validationErrors: AddToCartError[];
	tempQuantity: number;
	groupedProductIds: number[];
	childProductId: number;
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

const getProductData = (
	id: number,
	productType: string,
	availableVariations: AvailableVariation[],
	selectedAttributes: SelectedAttributes[]
): ( ProductData & { id: number } ) | null => {
	let productId = id;
	let productData: ProductData | undefined;

	if ( productType === 'variable' ) {
		const matchedVariation = getMatchedVariation(
			availableVariations,
			selectedAttributes
		);
		if ( matchedVariation?.variation_id ) {
			productId = matchedVariation.variation_id;
			productData =
				wooState?.products?.[ id ]?.variations?.[
					matchedVariation?.variation_id
				];
		}
	} else {
		productData = wooState?.products?.[ productId ];
	}

	if ( typeof productData !== 'object' || productData === null ) {
		return null;
	}

	// Add default quantity constraint values.
	const defaultMinValue = productType === 'grouped' ? 0 : 1;
	const min =
		typeof productData.min === 'number' ? productData.min : defaultMinValue;
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
	};
};

const getInputData = (
	event: HTMLElementEvent< HTMLButtonElement, HTMLInputElement >
) => {
	const inputElement = getInputElementFromEvent( event );

	if ( ! inputElement ) {
		return;
	}

	const parsedValue = parseInt( inputElement.value, 10 );
	const currentValue = isNaN( parsedValue ) ? 0 : parsedValue;

	return {
		currentValue,
		inputElement,
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

const dispatchChangeEvent = ( inputElement: HTMLInputElement ) => {
	const event = new Event( 'change', { bubbles: true } );
	inputElement.dispatchEvent( event );
};

export type AddToCartWithOptionsStore = {
	state: {
		isFormValid: boolean;
		allowsDecrease: boolean;
		allowsIncrease: boolean;
		noticeIds: string[];
		validationErrors: AddToCartError[];
	};
	actions: {
		setQuantity: ( value: number ) => void;
		addError: ( error: AddToCartError ) => string;
		clearErrors: ( group?: string ) => void;
		increaseQuantity: (
			event: HTMLElementEvent< HTMLButtonElement >
		) => void;
		decreaseQuantity: (
			event: HTMLElementEvent< HTMLButtonElement >
		) => void;
		handleQuantityInput: (
			event: HTMLElementEvent< HTMLInputElement >
		) => void;
		handleQuantityChange: (
			event: HTMLElementEvent< HTMLInputElement >
		) => void;
		handleQuantityCheckboxChange: (
			event: HTMLElementEvent< HTMLInputElement >
		) => void;
		addToCart: () => void;
		handleSubmit: ( event: FormEvent< HTMLFormElement > ) => void;
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
				const context = getContext< Context >();
				if ( ! context ) {
					return true;
				}

				const { productType, quantity } = context;

				if ( productType === 'grouped' ) {
					return Object.values( quantity ).some( ( qty ) => qty > 0 );
				}

				return state.validationErrors.length === 0;
			},
			get allowsDecrease() {
				const {
					quantity,
					childProductId,
					productType,
					productId,
					availableVariations,
					selectedAttributes,
				} = getContext< Context >();

				const productObject = getProductData(
					childProductId || productId,
					productType,
					availableVariations,
					selectedAttributes
				);

				if ( ! productObject ) {
					return true;
				}

				const { id, min, step } = productObject;

				if ( typeof step !== 'number' || typeof min !== 'number' ) {
					return true;
				}

				const currentQuantity = quantity[ id ] || 0;

				return currentQuantity - step >= min;
			},
			get allowsIncrease() {
				const {
					quantity,
					childProductId,
					productType,
					productId,
					availableVariations,
					selectedAttributes,
				} = getContext< Context >();

				const productObject = getProductData(
					childProductId || productId,
					productType,
					availableVariations,
					selectedAttributes
				);

				if ( ! productObject ) {
					return true;
				}

				const { id, max, step } = productObject;

				if ( typeof step !== 'number' || typeof max !== 'number' ) {
					return true;
				}

				const currentQuantity = quantity[ id ] || 0;

				return currentQuantity + step <= max;
			},
		},
		actions: {
			setQuantity( value: number ) {
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
					const id = context.childProductId || context.productId;

					context.quantity = {
						...context.quantity,
						[ id ]: value,
					};
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
			increaseQuantity: (
				event: HTMLElementEvent< HTMLButtonElement >
			) => {
				const inputData = getInputData( event );
				if ( ! inputData ) {
					return;
				}
				const { currentValue, inputElement } = inputData;

				const {
					childProductId,
					productType,
					productId,
					availableVariations,
					selectedAttributes,
				} = getContext< Context >();

				const productObject = getProductData(
					childProductId || productId,
					productType,
					availableVariations,
					selectedAttributes
				);

				if ( ! productObject ) {
					return;
				}

				const { max, min, step } = productObject;

				if (
					typeof step !== 'number' ||
					typeof min !== 'number' ||
					typeof max !== 'number'
				) {
					return;
				}

				const newValue = currentValue + step;

				if ( newValue <= max ) {
					const updatedValue = Math.max( min, newValue );
					actions.setQuantity( updatedValue );
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
				const { currentValue, inputElement } = inputData;

				const {
					childProductId,
					productType,
					productId,
					availableVariations,
					selectedAttributes,
				} = getContext< Context >();

				const productObject = getProductData(
					childProductId || productId,
					productType,
					availableVariations,
					selectedAttributes
				);

				if ( ! productObject ) {
					return;
				}

				const { min, max, step } = productObject;

				if (
					typeof step !== 'number' ||
					typeof min !== 'number' ||
					typeof max !== 'number'
				) {
					return;
				}

				const newValue = currentValue - step;

				if ( newValue >= min ) {
					const updatedValue = Math.min( max ?? Infinity, newValue );
					actions.setQuantity( updatedValue );
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
				const { currentValue } = inputData;

				actions.setQuantity( currentValue );
			},
			handleQuantityChange: (
				event: HTMLElementEvent< HTMLInputElement >
			) => {
				const inputData = getInputData( event );
				if ( ! inputData ) {
					return;
				}

				const { childProductId } = getContext< Context >();
				const { currentValue } = inputData;

				const {
					productType,
					productId,
					availableVariations,
					selectedAttributes,
				} = getContext< Context >();

				const id = childProductId || productId;
				const productObject = getProductData(
					id,
					productType,
					availableVariations,
					selectedAttributes
				);

				if ( ! productObject ) {
					return;
				}

				const { min, max, step } = productObject;

				if (
					typeof step !== 'number' ||
					typeof min !== 'number' ||
					typeof max !== 'number'
				) {
					return;
				}

				const newValue = Math.min(
					max ?? Infinity,
					Math.max( min, currentValue )
				);

				if ( event.target.value !== newValue.toString() ) {
					actions.setQuantity( newValue );
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
				const { inputElement } = inputData;

				actions.setQuantity( inputElement.checked ? 1 : 0 );
			},
			*addToCart() {
				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				yield import( '@woocommerce/stores/woocommerce/cart' );

				const { productId, quantity, selectedAttributes, productType } =
					getContext< Context >();

				const { variationId } = state;
				const id = variationId || productId;
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
				yield wooActions.addCartItem( {
					id,
					quantity: newQuantity,
					variation: selectedAttributes,
					type: productType,
				} );
			},
			*handleSubmit( event: FormEvent< HTMLFormElement > ) {
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
