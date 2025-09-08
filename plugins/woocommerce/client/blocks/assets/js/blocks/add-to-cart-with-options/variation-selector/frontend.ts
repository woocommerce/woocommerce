/**
 * External dependencies
 */
import {
	store,
	getContext,
	getConfig,
	getElement,
} from '@wordpress/interactivity';
import { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';
import type { ChangeEvent } from 'react';
import type { ProductDataStore } from '@woocommerce/stores/woocommerce/product-data';

/**
 * Internal dependencies
 */
import { dispatchChangeEvent, getProductData } from '../frontend';
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsStoreContext,
} from '../frontend';
import { getMatchedVariation } from '../../../base/utils/variations/get-matched-variation';
import setStyles from './set-styles';

type Option = {
	value: string;
	label: string;
	isSelected: boolean;
};

type Context = AddToCartWithOptionsStoreContext & {
	name: string;
	selectedValue: string | null;
	option: Option;
	options: Option[];
};

// Set selected pill styles for proper contrast.
setStyles();

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

/**
 * Check if the attribute value is valid given the other selected attributes and
 * the available variations.
 *
 * To know if an attribute value is valid given the other selected attributes,
 * we make sure there is at least one available variation matching the current
 * selected attributes and the attribute value being checked.
 */
const isAttributeValueValid = ( {
	attributeName,
	attributeValue,
	selectedAttributes,
}: {
	attributeName: string;
	attributeValue: string;
	selectedAttributes: SelectedAttributes[];
} ) => {
	if (
		! attributeName ||
		! attributeValue ||
		! Array.isArray( selectedAttributes )
	) {
		return false;
	}

	// If the current attribute is selected, we require one less attribute to
	// match, this allows shoppers to switch between attributes. For example,
	// if "Blue" and "Small" are selected, we want "Blue" and "Medium" to be
	// valid, that's why we subtract one from the total number of attributes to
	// match.
	const isCurrentAttributeSelected = selectedAttributes.some(
		( selectedAttribute ) => selectedAttribute.attribute === attributeName
	);
	const attributesToMatch = isCurrentAttributeSelected
		? selectedAttributes.length - 1
		: selectedAttributes.length;

	const { products } = getConfig( 'woocommerce' );
	const availableVariations = Object.values(
		products?.[ productDataState.productId ].variations
	);

	// Check if there is at least one available variation matching the current
	// selected attributes and the attribute value being checked.
	return availableVariations.some( ( availableVariation ) => {
		// Skip variations that don't match the current attribute value.
		if (
			availableVariation.attributes[ attributeName ] !== attributeValue &&
			availableVariation.attributes[ attributeName ] !== '' // "" is used for "any".
		) {
			return false;
		}

		// Count how many of the selected attributes match the variation.
		const matchingAttributes = selectedAttributes.filter(
			( selectedAttribute ) => {
				const availableVariationAttributeValue =
					availableVariation.attributes[
						selectedAttribute.attribute
					];
				// If the current available variation matches the selected
				// value, count it.
				if (
					availableVariationAttributeValue === selectedAttribute.value
				) {
					return true;
				}
				// If the current available variation has an empty value
				// (matching any), count it if it refers to a different
				// attribute or the attribute it refers matches the current
				// selection.
				if ( availableVariationAttributeValue === '' ) {
					if (
						selectedAttribute.attribute !== attributeName ||
						attributeValue === selectedAttribute.value
					) {
						return true;
					}
				}
				return false;
			}
		).length;

		return matchingAttributes >= attributesToMatch;
	} );
};

export type VariableProductAddToCartWithOptionsStore =
	AddToCartWithOptionsStore & {
		state: {
			isOptionSelected: boolean;
			isOptionDisabled: boolean;
		};
		actions: {
			setAttribute: ( attribute: string, value: string ) => void;
			removeAttribute: ( attribute: string ) => void;
			handlePillClick: () => void;
			handleDropdownChange: (
				event: ChangeEvent< HTMLSelectElement >
			) => void;
		};
		callbacks: {
			setDefaultSelectedAttribute: () => void;
			setSelectedVariationId: () => void;
			validateVariation: () => void;
			watchQuantityConstraints: () => void;
		};
	};

const { actions: productDataActions, state: productDataState } =
	store< ProductDataStore >(
		'woocommerce/product-data',
		{},
		{ lock: universalLock }
	);

const { actions, state } = store< VariableProductAddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{
		state: {
			get isOptionSelected() {
				const { selectedValue, option } = getContext< Context >();
				return selectedValue === option.value;
			},
			get isOptionDisabled() {
				const { name, option } = getContext< Context >();

				if ( option.value === '' ) {
					return false;
				}

				return ! isAttributeValueValid( {
					attributeName: name,
					attributeValue: option.value,
					selectedAttributes: productDataState.selectedAttributes,
				} );
			},
		},
		actions: {
			handlePillClick() {
				if ( state.isOptionDisabled ) {
					return;
				}
				const context = getContext< Context >();
				if ( context.selectedValue === context.option.value ) {
					context.selectedValue = '';
				} else {
					context.selectedValue = context.option.value;
				}
				productDataActions.setAttribute(
					context.name,
					context.selectedValue
				);
			},
			handleDropdownChange( event: ChangeEvent< HTMLSelectElement > ) {
				const context = getContext< Context >();
				context.selectedValue = event.currentTarget.value;
				productDataActions.setAttribute(
					context.name,
					context.selectedValue
				);
			},
		},
		callbacks: {
			setDefaultSelectedAttribute() {
				const context = getContext< Context >();

				if ( context.selectedValue ) {
					productDataActions.setAttribute(
						context.name,
						context.selectedValue
					);
				}
			},
			validateVariation() {
				actions.clearErrors( 'variable-product' );

				const { products } = getConfig( 'woocommerce' );

				const variations =
					products?.[ productDataState.productId ].variations;

				const matchedVariation = getMatchedVariation(
					variations,
					productDataState.selectedAttributes
				);

				const { errorMessages } = getConfig();

				if ( ! matchedVariation?.variation_id ) {
					actions.addError( {
						code: 'variableProductMissingAttributes',
						message:
							errorMessages?.variableProductMissingAttributes ||
							'',
						group: 'variable-product',
					} );
					return;
				}

				if ( ! matchedVariation?.is_in_stock ) {
					actions.addError( {
						code: 'variableProductOutOfStock',
						message: errorMessages?.variableProductOutOfStock || '',
						group: 'variable-product',
					} );
				}
			},
			// Quantity constraints might change dynamically when switching
			// variations. Based on this, we might need to update the quantity.
			watchQuantityConstraints() {
				const { ref } = getElement();

				if ( ! ( ref instanceof HTMLInputElement ) ) {
					return;
				}

				// Let's not do anything if the user is typing in the input.
				if ( ref === document.activeElement ) {
					return;
				}

				const productObject = getProductData(
					productDataState.productId,
					productDataState.selectedAttributes
				);

				if ( productObject ) {
					const { quantity } = getContext< Context >();
					const currentValue = quantity[ productObject.id ];
					const { min, max } = productObject;

					let newValue = currentValue;
					if ( quantity[ productObject.id ] < min ) {
						newValue = min;
					} else if ( quantity[ productObject.id ] > max ) {
						newValue = max;
					}

					if (
						newValue !== ref.valueAsNumber ||
						newValue !== quantity[ productObject.id ]
					) {
						actions.setQuantity( newValue );

						ref.value = newValue.toString();
						dispatchChangeEvent( ref );
					}
				}
			},
		},
	},
	{ lock: universalLock }
);
