/**
 * External dependencies
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';
import type {
	SelectedAttributes,
	Store as WooCommerce,
} from '@woocommerce/stores/woocommerce/cart';
import '@woocommerce/stores/woocommerce/products';
import '@woocommerce/stores/woocommerce/cart';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import {
	normalizeAttributeName,
	attributeNamesMatch,
	getVariationAttributeValue,
} from '../../../base/utils/variations/attribute-matching';
import type { AddToCartWithOptionsStore } from '../frontend';
import type { SelectableItem } from '../../../types/type-defs/selectable-items';
import type { VisualAttributeTerm } from '../../../base/utils/visual-attribute-terms';

type VariationOptionItem = {
	id: string;
	label: string;
	value: string;
	ariaLabel?: string;
	visual?: VisualAttributeTerm;
};

type Context = {
	name: string;
	selectedValue: string | null;
	variationAttributeOptions: VariationOptionItem[];
	autoselect: boolean;
	disabledAttributesAction?: 'disable' | 'hide';
};

type ToggleContext = Context & {
	item?: SelectableItem< { visual?: VisualAttributeTerm } >;
};

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

const { state: cartState, actions: cartActions } = store< WooCommerce >(
	'woocommerce/cart',
	{},
	{ lock: universalLock }
);

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
		( selectedAttribute ) =>
			attributeNamesMatch( selectedAttribute.attribute, attributeName )
	);
	const attributesToMatch = isCurrentAttributeSelected
		? selectedAttributes.length - 1
		: selectedAttributes.length;

	const { mainProductInContext: product } = productsState;

	if ( ! product?.variations?.length ) {
		return false;
	}

	// Check if there is at least one available variation matching the current
	// selected attributes and the attribute value being checked.
	return product.variations.some( ( variation ) => {
		const variationAttrValue = getVariationAttributeValue(
			variation,
			attributeName
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
						selectedAttribute.attribute
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
						! attributeNamesMatch(
							selectedAttribute.attribute,
							attributeName
						) ||
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
 * Return the product attributes and options from Store API format.
 *
 * @param product The product in Store API format.
 * @return Record of attribute names to their available option values.
 */
const getProductAttributesAndOptions = (
	product: ProductResponseItem | null
): Record< string, string[] > => {
	if ( ! product?.variations?.length ) {
		return {};
	}

	const productAttributesAndOptions = {} as Record< string, string[] >;
	product.variations.forEach( ( variation ) => {
		variation.attributes.forEach( ( attr ) => {
			if ( ! Array.isArray( productAttributesAndOptions[ attr.name ] ) ) {
				productAttributesAndOptions[ attr.name ] = [];
			}
			if (
				attr.value &&
				! productAttributesAndOptions[ attr.name ].includes(
					attr.value
				)
			) {
				productAttributesAndOptions[ attr.name ].push( attr.value );
			}
		} );
	} );

	return productAttributesAndOptions;
};

export type VariableProductAddToCartWithOptionsStore =
	AddToCartWithOptionsStore & {
		state: {
			selectableItems: readonly SelectableItem< {
				visual?: VisualAttributeTerm;
			} >[];
		};
		actions: {
			setAttribute: ( attribute: string, value: string ) => void;
			toggle: (
				item?: SelectableItem< { visual?: VisualAttributeTerm } >
			) => void;
			autoselectAttributes: ( args: {
				includedAttributes?: string[];
				excludedAttributes?: string[];
			} ) => void;
		};
		callbacks: {
			setDefaultSelectedAttribute: () => void;
			validateVariation: () => void;
		};
	};

const { actions } = store< VariableProductAddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{
		state: {
			get selectableItems(): readonly SelectableItem< {
				visual?: VisualAttributeTerm;
			} >[] {
				const context = getContext< Context >();
				if ( ! context ) {
					return [];
				}
				const {
					name,
					disabledAttributesAction,
					variationAttributeOptions,
				} = context;

				if ( ! Array.isArray( variationAttributeOptions ) ) {
					return [];
				}

				const selectedAttributes = ( cartState.itemInContext.draft
					?.variation ?? [] ) as SelectedAttributes[];
				const hideInvalid = disabledAttributesAction === 'hide';

				return variationAttributeOptions.map( ( row, index ) => {
					const disabled = ! isAttributeValueValid( {
						attributeName: name,
						attributeValue: row.value,
						selectedAttributes,
					} );
					const selected = selectedAttributes.some(
						( attrObject ) =>
							attributeNamesMatch( attrObject.attribute, name ) &&
							attrObject.value === row.value
					);
					return {
						id: row.id,
						label: row.label,
						value: row.value,
						ariaLabel: row.ariaLabel || row.label,
						index,
						selected,
						disabled,
						hidden: hideInvalid && disabled,
						...( row.visual !== undefined && {
							visual: row.visual,
						} ),
					};
				} );
			},
		},
		actions: {
			// The single choke point for every attribute change. It computes the
			// next selection, upserts it into the context draft's `variation`
			// (submission/pairing truth), then resolves and writes `variationId`
			// into the products context (derivation truth). An empty value removes
			// the attribute (absorbing the former `removeAttribute`).
			setAttribute( attribute: string, value: string ) {
				const selectedAttributes = ( cartState.itemInContext.draft
					?.variation ?? [] ) as SelectedAttributes[];
				const index = selectedAttributes.findIndex(
					( selectedAttribute ) =>
						attributeNamesMatch(
							selectedAttribute.attribute,
							attribute
						)
				);

				if ( value === '' ) {
					if ( index >= 0 ) {
						selectedAttributes.splice( index, 1 );
					}
				} else if ( index >= 0 ) {
					selectedAttributes[ index ] = { attribute, value };
				} else {
					selectedAttributes.push( { attribute, value } );
				}

				cartActions.upsertDraftItem( {
					variation: selectedAttributes,
				} );

				// Derivation-truth half of the selection's "double write":
				// resolve the selection to a variation and write the resolved
				// `variationId` into the `woocommerce/products` context (or
				// global state when there is no context — the single-product-page
				// flow keeps the write global for the Product Gallery). The
				// products store derives `productVariationInContext` from this
				// `variationId` alone — it never reads the cart draft.
				const { mainProductInContext: product } = productsState;
				if ( ! product?.variations?.length ) {
					return;
				}

				const result = productsState.findProduct( {
					id: product.id,
					selectedAttributes,
				} );
				// findProduct returns the parent when no variation matches —
				// only accept an actual variation.
				const matchedVariation =
					result && result.id !== product.id ? result : null;

				const productContext = getContext< {
					variationId?: number | null;
				} >( 'woocommerce/products' );

				( productContext ?? productsState ).variationId =
					matchedVariation?.id ?? null;
			},
			toggle(
				itemArg?:
					| SelectableItem< { visual?: VisualAttributeTerm } >
					| Event
			) {
				const context = getContext< ToggleContext >();
				const item =
					itemArg && ! ( itemArg instanceof Event )
						? itemArg
						: context.item;
				if ( ! item || item.hidden || item.disabled ) {
					return;
				}

				const { name } = context;
				const selectedAttributes = ( cartState.itemInContext.draft
					?.variation ?? [] ) as SelectedAttributes[];
				const isCurrentlySelected = selectedAttributes.some(
					( attrObject ) =>
						attributeNamesMatch( attrObject.attribute, name ) &&
						attrObject.value === item.value
				);

				if ( isCurrentlySelected ) {
					context.selectedValue = '';
					actions.setAttribute( name, '' );
				} else {
					context.selectedValue = item.value;
					actions.setAttribute( name, item.value );
					actions.autoselectAttributes( {
						excludedAttributes: [ name ],
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
				const context = getContext< Context >();
				if ( ! context || ! context.autoselect ) {
					return;
				}

				const { mainProductInContext: product } = productsState;
				if ( ! product ) {
					return;
				}

				const selectedAttributes = ( cartState.itemInContext.draft
					?.variation ?? [] ) as SelectedAttributes[];

				// Normalize included/excluded attributes to lowercase for comparison
				// with Store API labels (e.g., "Color" vs "attribute_pa_color" → "color").
				const normalizedIncluded = includedAttributes.map( ( attr ) =>
					normalizeAttributeName( attr )
				);
				const normalizedExcluded = excludedAttributes.map( ( attr ) =>
					normalizeAttributeName( attr )
				);

				const productAttributesAndOptions: Record< string, string[] > =
					getProductAttributesAndOptions( product );
				Object.entries( productAttributesAndOptions ).forEach(
					( [ attribute, options ] ) => {
						const attributeLower =
							normalizeAttributeName( attribute );
						if (
							normalizedIncluded.length !== 0 &&
							! normalizedIncluded.includes( attributeLower )
						) {
							return;
						}
						if (
							normalizedExcluded.length !== 0 &&
							normalizedExcluded.includes( attributeLower )
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
							// Use the context's attribute name format for consistency.
							// Find the matching context name by comparing normalized versions.
							const contextName =
								includedAttributes.find(
									( attr ) =>
										normalizeAttributeName( attr ) ===
										attributeLower
								) || attribute;
							actions.setAttribute( contextName, validOption );
						}
					}
				);
			},
		},
		callbacks: {
			// Client-side default-selection mechanism, run on each attribute
			// block's init. It seeds the initial selection into the context draft
			// (drafts are client-only — this is where a variable product's draft
			// is born) through the same `setAttribute` choke point, so the initial
			// `variationId` resolution happens for free.
			setDefaultSelectedAttribute() {
				const context = getContext< Context >();
				if ( ! context.name ) {
					return;
				}

				if ( context.selectedValue ) {
					actions.setAttribute( context.name, context.selectedValue );
				}

				actions.autoselectAttributes( {
					includedAttributes: [ context.name ],
				} );
			},
			validateVariation() {
				actions.clearErrors( 'variable-product' );

				const { mainProductInContext: product } = productsState;

				if ( ! product?.variations?.length ) {
					return;
				}

				const selectedAttributes = ( cartState.itemInContext.draft
					?.variation ?? [] ) as SelectedAttributes[];
				const result = productsState.findProduct( {
					id: product.id,
					selectedAttributes,
				} );
				// findProduct returns the parent when no variation
				// matches — only accept an actual variation.
				const matchedVariation =
					result && result.id !== product.id ? result : null;

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
		},
	},
	{ lock: universalLock }
);
