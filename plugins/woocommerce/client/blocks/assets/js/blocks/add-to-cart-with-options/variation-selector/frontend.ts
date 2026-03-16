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
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { getProductData } from '../frontend';
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsStoreContext,
} from '../frontend';
import {
	getVariationAttributeValue,
	findMatchingVariation,
} from '../../../base/utils/variations/attribute-matching';
import type { AttributeSlugToLabel } from '../../../base/utils/variations/attribute-matching';
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
	attributeSlugToLabel: AttributeSlugToLabel;
};

// Set selected pill styles for proper contrast.
setStyles();

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

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
	slugToLabel,
}: {
	attributeName: string;
	attributeValue: string;
	selectedAttributes: SelectedAttributes[];
	slugToLabel: AttributeSlugToLabel;
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

	const product = productsState.products[ productDataState.productId ];

	if ( ! product?.variations?.length ) {
		return false;
	}

	// Check if there is at least one available variation matching the current
	// selected attributes and the attribute value being checked.
	return product.variations.some( ( variation ) => {
		const variationAttrValue = getVariationAttributeValue(
			variation,
			attributeName,
			slugToLabel
		);

		// Skip variations that don't match the current attribute value.
		if (
			variationAttrValue !== attributeValue &&
			variationAttrValue !== null // null is used for "any".
		) {
			return false;
		}

		// Count how many of the selected attributes match the variation.
		const matchingAttributes = selectedAttributes.filter(
			( selectedAttribute ) => {
				const availableVariationAttributeValue =
					getVariationAttributeValue(
						variation,
						selectedAttribute.attribute,
						slugToLabel
					);
				// If the current available variation matches the selected
				// value, count it.
				if (
					availableVariationAttributeValue === selectedAttribute.value
				) {
					return true;
				}
				// If the current available variation has a null value
				// (matching any), count it if it refers to a different
				// attribute or the attribute it refers matches the current
				// selection.
				if ( availableVariationAttributeValue === null ) {
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
 * Return the product attributes and options from Store API format, keyed by
 * attribute slug using the label-to-slug reverse mapping.
 *
 * @param product      The product in Store API format.
 * @param slugToLabel  Mapping of attribute slugs to Store API label names.
 * @return Record of attribute slugs to their available option values.
 */
const getProductAttributesAndOptions = (
	product: ProductResponseItem | null,
	slugToLabel: AttributeSlugToLabel
): Record< string, string[] > => {
	if ( ! product?.variations?.length ) {
		return {};
	}

	// Build a reverse mapping: Store API label → slug.
	const labelToSlug: Record< string, string > = Object.fromEntries(
		Object.entries( slugToLabel ).map( ( [ slug, label ] ) => [
			label,
			slug,
		] )
	);

	const productAttributesAndOptions = {} as Record< string, string[] >;
	product.variations.forEach( ( variation ) => {
		variation.attributes.forEach( ( attr ) => {
			const slug = labelToSlug[ attr.name ];
			if ( ! slug ) {
				return;
			}
			if ( ! Array.isArray( productAttributesAndOptions[ slug ] ) ) {
				productAttributesAndOptions[ slug ] = [];
			}
			if (
				attr.value &&
				! productAttributesAndOptions[ slug ].includes( attr.value )
			) {
				productAttributesAndOptions[ slug ].push( attr.value );
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
				const {
					name,
					option,
					selectedAttributes,
					attributeSlugToLabel,
				} = getContext< Context >();

				if ( option.value === '' ) {
					return false;
				}

				return ! isAttributeValueValid( {
					attributeName: name,
					attributeValue: option.value,
					selectedAttributes,
					slugToLabel: attributeSlugToLabel,
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
				const { autoselect, selectedAttributes, attributeSlugToLabel } =
					getContext< Context >();

				if ( ! autoselect ) {
					return;
				}

				const product =
					productsState.products[ productDataState.productId ];
				if ( ! product ) {
					return;
				}

				const productAttributesAndOptions: Record< string, string[] > =
					getProductAttributesAndOptions(
						product,
						attributeSlugToLabel
					);
				Object.entries( productAttributesAndOptions ).forEach(
					( [ attributeSlug, options ] ) => {
						if (
							includedAttributes.length !== 0 &&
							! includedAttributes.includes( attributeSlug )
						) {
							return;
						}
						if (
							excludedAttributes.length !== 0 &&
							excludedAttributes.includes( attributeSlug )
						) {
							return;
						}
						const validOptions = options.filter( ( option ) =>
							isAttributeValueValid( {
								attributeName: attributeSlug,
								attributeValue: option,
								selectedAttributes,
								slugToLabel: attributeSlugToLabel,
							} )
						);
						if ( validOptions.length === 1 ) {
							actions.setAttribute(
								attributeSlug,
								validOptions[ 0 ]
							);
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
				const product =
					productsState.products[ productDataState.productId ];

				if ( ! product?.variations?.length ) {
					return;
				}

				const { selectedAttributes, attributeSlugToLabel } =
					getContext< Context >();
				const matchedVariation = findMatchingVariation(
					product,
					selectedAttributes,
					attributeSlugToLabel
				);

				const { actions: productDataActions } =
					store< ProductDataStore >(
						'woocommerce/product-data',
						{},
						{ lock: universalLock }
					);
				productDataActions.setVariationId(
					matchedVariation?.id ?? null
				);
			},
			validateVariation() {
				actions.clearErrors( 'variable-product' );

				const product =
					productsState.products[ productDataState.productId ];

				if ( ! product?.variations?.length ) {
					return;
				}

				const { selectedAttributes, attributeSlugToLabel } =
					getContext< Context >();
				const matchedVariation = findMatchingVariation(
					product,
					selectedAttributes,
					attributeSlugToLabel
				);

				const { errorMessages } = getConfig();

				if ( ! matchedVariation?.id ) {
					actions.addError( {
						code: 'variableProductMissingAttributes',
						message:
							errorMessages?.variableProductMissingAttributes ||
							'',
						group: 'variable-product',
					} );
					return;
				}

				// Check stock status from productVariations store.
				const variationData =
					productsState.productVariations[ matchedVariation.id ];

				if ( ! variationData ) {
					// Variation data not loaded - this is a data consistency issue.
					// Return early; getProductData already returns null for this case,
					// which prevents add-to-cart from proceeding.
					return;
				}

				if ( ! variationData.is_in_stock ) {
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

				const { selectedAttributes, attributeSlugToLabel } =
					getContext< Context >();

				const productObject = getProductData(
					productDataState.productId,
					selectedAttributes,
					attributeSlugToLabel
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
