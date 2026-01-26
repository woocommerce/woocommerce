/**
 * External dependencies
 */
import {
	store,
	getContext,
	getConfig,
	getElement,
} from '@wordpress/interactivity';
import {
	SelectedAttributes,
	VariationData,
} from '@woocommerce/stores/woocommerce/cart';
import type { ChangeEvent } from 'react';
import type { ProductDataStore } from '@woocommerce/stores/woocommerce/product-data';

/**
 * Internal dependencies
 */
import { getProductData } from '../frontend';
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsStoreContext,
} from '../frontend';
import type { NormalizedProductData } from '../types';
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
	autoselect: boolean;
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

	if ( ! products || ! products[ productDataState.productId ] ) {
		return false;
	}

	const availableVariations = Object.values(
		products[ productDataState.productId ].variations || {}
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

/**
 * Return the product attributes and options.
 */
const getProductAttributesAndOptions = (
	productObject: NormalizedProductData | null
): Record< string, string[] > => {
	if ( ! productObject?.variations ) {
		return {};
	}

	const variations: VariationData[] = Object.values(
		productObject.variations
	);
	const productAttributesAndOptions = {} as Record< string, string[] >;
	variations.forEach( ( variation: VariationData ) => {
		if ( ! variation?.attributes ) {
			return;
		}
		Object.entries( variation.attributes ).forEach( ( [ key, value ] ) => {
			if ( typeof key !== 'string' || typeof value !== 'string' ) {
				return;
			}
			if ( ! Array.isArray( productAttributesAndOptions[ key ] ) ) {
				productAttributesAndOptions[ key ] = [];
			}
			if ( ! productAttributesAndOptions[ key ].includes( value ) ) {
				productAttributesAndOptions[ key ].push( value );
			}
		} );
	} );

	return productAttributesAndOptions;
};

export type VariableProductAddToCartWithOptionsStore =
	AddToCartWithOptionsStore & {
		state: {
			selectedAttributes: SelectedAttributes[];
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
			autoselectAttributes: ( args: {
				includedAttributes?: string[];
				excludedAttributes?: string[];
			} ) => void;
		};
		callbacks: {
			setDefaultSelectedAttribute: () => void;
			setSelectedVariationId: () => void;
			validateVariation: () => void;
			watchQuantityConstraints: () => void;
		};
	};

const { state: productDataState } = store< ProductDataStore >(
	'woocommerce/product-data',
	{},
	{ lock: universalLock }
);

const { actions, state } = store< VariableProductAddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{
		state: {
			get selectedAttributes(): SelectedAttributes[] {
				const context = getContext< Context >();
				if ( ! context ) {
					return [];
				}
				return context.selectedAttributes;
			},
			get isOptionSelected() {
				const { selectedAttributes, option, name } =
					getContext< Context >();

				return selectedAttributes.some( ( attrObject ) => {
					return (
						attrObject.attribute === name &&
						attrObject.value === option.value
					);
				} );
			},
			get isOptionDisabled() {
				const { name, option, selectedAttributes } =
					getContext< Context >();

				if ( option.value === '' ) {
					return false;
				}

				return ! isAttributeValueValid( {
					attributeName: name,
					attributeValue: option.value,
					selectedAttributes,
				} );
			},
		},
		actions: {
			setAttribute( attribute: string, value: string ) {
				const { selectedAttributes } = getContext< Context >();
				const index = selectedAttributes.findIndex(
					( selectedAttribute ) =>
						selectedAttribute.attribute === attribute
				);

				if ( value === '' ) {
					if ( index >= 0 ) {
						selectedAttributes.splice( index, 1 );
					}
					return;
				}

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
			handlePillClick() {
				const context = getContext< Context >();

				if ( state.isOptionSelected ) {
					context.selectedValue = '';
				} else {
					context.selectedValue = context.option.value;
				}
				actions.setAttribute( context.name, context.selectedValue );
				if ( context.selectedValue !== '' ) {
					actions.autoselectAttributes( {
						excludedAttributes: [ context.name ],
					} );
				}
			},
			handleDropdownChange( event: ChangeEvent< HTMLSelectElement > ) {
				const context = getContext< Context >();
				context.selectedValue = event.currentTarget.value;
				actions.setAttribute( context.name, context.selectedValue );
				if ( context.selectedValue !== '' ) {
					actions.autoselectAttributes( {
						excludedAttributes: [ context.name ],
					} );
				}
			},
			autoselectAttributes( {
				includedAttributes = [],
				excludedAttributes = [],
			}: {
				includedAttributes?: Array< string >;
				excludedAttributes?: Array< string >;
			} = {} ) {
				const { autoselect, selectedAttributes } =
					getContext< Context >();

				if ( ! autoselect ) {
					return;
				}

				const productObject: NormalizedProductData | null =
					getProductData( productDataState.productId, [] );
				if ( ! productObject ) {
					return;
				}
				const productAttributesAndOptions: Record< string, string[] > =
					getProductAttributesAndOptions( productObject );
				Object.entries( productAttributesAndOptions ).forEach(
					( [ attribute, options ] ) => {
						if (
							includedAttributes.length !== 0 &&
							! includedAttributes.includes( attribute )
						) {
							return;
						}
						if (
							excludedAttributes.length !== 0 &&
							excludedAttributes.includes( attribute )
						) {
							return;
						}
						const validOptions = options.filter( ( option ) =>
							isAttributeValueValid( {
								attributeName: attribute,
								attributeValue: option,
								selectedAttributes,
							} )
						);
						if ( validOptions.length === 1 ) {
							const validOption = validOptions[ 0 ];
							actions.setAttribute( attribute, validOption );
						}
					}
				);
			},
		},
		callbacks: {
			setDefaultSelectedAttribute() {
				const context = getContext< Context >();

				if ( context.selectedValue ) {
					actions.setAttribute( context.name, context.selectedValue );
				}
				actions.autoselectAttributes( {
					includedAttributes: [ context.name ],
				} );
			},
			setSelectedVariationId: () => {
				const { products } = getConfig( 'woocommerce' );

				const variations =
					products?.[ productDataState.productId ].variations;

				const { selectedAttributes } = getContext< Context >();

				const matchedVariation = getMatchedVariation(
					variations,
					selectedAttributes
				);

				const { actions: productDataActions } =
					store< ProductDataStore >(
						'woocommerce/product-data',
						{},
						{ lock: universalLock }
					);
				const matchedVariationId =
					matchedVariation?.variation_id || null;
				productDataActions.setVariationId( matchedVariationId );
			},
			validateVariation() {
				actions.clearErrors( 'variable-product' );

				const { products } = getConfig( 'woocommerce' );

				if ( ! products || ! products[ productDataState.productId ] ) {
					return;
				}

				const variations =
					products[ productDataState.productId ].variations;

				const { selectedAttributes } = getContext< Context >();

				const matchedVariation = getMatchedVariation(
					variations,
					selectedAttributes
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

				const { selectedAttributes } = getContext< Context >();

				const productObject = getProductData(
					productDataState.productId,
					selectedAttributes
				);

				if ( productObject ) {
					const { quantity } = getContext< Context >();
					const currentValue = quantity[ productObject.id ];
					const { min, max } = productObject;

					let newValue = currentValue;
					if ( currentValue < min ) {
						newValue = min;
					} else if ( currentValue > max ) {
						newValue = max;
					}

					if (
						newValue !== ref.valueAsNumber ||
						newValue !== currentValue
					) {
						actions.setQuantity(
							productDataState.productId,
							newValue
						);
					}
				}
			},
		},
	},
	{ lock: universalLock }
);
