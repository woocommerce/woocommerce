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
import '@woocommerce/stores/woocommerce/products';
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
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsStoreContext,
} from '../frontend';
import type { SelectableItem } from '../../../types/type-defs/selectable-items';
import type { VisualAttributeTerm } from '../../../base/utils/visual-attribute-terms';
import {
	getContextProductId,
	getDraftQuantity,
	setDraftVariation,
} from '../cart-drafts';

type VariationOptionItem = {
	id: string;
	label: string;
	value: string;
	ariaLabel?: string;
	visual?: VisualAttributeTerm;
};

type Context = AddToCartWithOptionsStoreContext & {
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

/**
 * Mirror the shopper's attribute selection into the shared-store draft's
 * `variation` — the submission/pairing truth the cart POST reads. This is the
 * FIRST half of the "double write" the selection performs (schema: "Selection UI
 * layering"): the SECOND half is `setSelectedVariationId`, which resolves the
 * selection to a variation and writes `variationId` into the `woocommerce/products`
 * context — the DERIVATION truth `productVariationInContext` reads. The two
 * planes are deliberately separate: the products store derives from its own
 * `variationId` and reads NOTHING from the cart draft (T12).
 *
 * The block's `context.selectedAttributes` stays authoritative for the block
 * family's own UI (valid-option computation) and for out-of-scope consumers
 * (Product Button, Add to Wishlist Button) that still read it; this one-way
 * mirror keeps the draft in step on every selection change. A shallow copy is
 * written so the draft holds its own array rather than aliasing the reactive
 * context array.
 *
 * @param selectedAttributes The current selection from the block context.
 */
const mirrorSelectionToDraft = (
	selectedAttributes: SelectedAttributes[]
): void => {
	const productId = getContextProductId();
	if ( productId === undefined ) {
		return;
	}
	setDraftVariation( productId, [ ...selectedAttributes ] );
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
			removeAttribute: ( attribute: string ) => void;
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
			setSelectedVariationId: () => void;
			validateVariation: () => void;
			watchQuantityConstraints: () => void;
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
				// The shopper's selection is read from the block's iAPI context
				// (the compatibility surface out-of-scope consumers still read);
				// the source of truth for the cart mirrors into the draft (see
				// `setAttribute` / `removeAttribute`).
				const selectedAttributes = context.selectedAttributes || [];
				const hideInvalid = disabledAttributesAction === 'hide';

				if ( ! Array.isArray( variationAttributeOptions ) ) {
					return [];
				}

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
			setAttribute( attribute: string, value: string ) {
				const { selectedAttributes } = getContext< Context >();
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
					mirrorSelectionToDraft( selectedAttributes );
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

				mirrorSelectionToDraft( selectedAttributes );
			},
			removeAttribute( attribute: string ) {
				const { selectedAttributes } = getContext< Context >();
				const index = selectedAttributes.findIndex(
					( selectedAttribute ) =>
						attributeNamesMatch(
							selectedAttribute.attribute,
							attribute
						)
				);
				if ( index >= 0 ) {
					selectedAttributes.splice( index, 1 );
				}

				mirrorSelectionToDraft( selectedAttributes );
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
				const selectedAttributes = context.selectedAttributes || [];
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

				const selectedAttributes = context.selectedAttributes || [];

				const { mainProductInContext: product } = productsState;
				if ( ! product ) {
					return;
				}

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
			// The DERIVATION-TRUTH half of the selection's double write (schema:
			// "Selection UI layering"). A `data-wp-watch` callback: it re-runs on
			// every selection change, resolves the shopper's attribute selection
			// to a variation via `findProduct` (deterministic, mirrors the server
			// — identity rule 6), and writes the resolved `variationId` into the
			// `woocommerce/products` context (or global state out of context).
			// That id is the ONLY thing `productVariationInContext` derives from —
			// the products store never reads the cart draft (T12). The submission/
			// pairing half is `mirrorSelectionToDraft` (draft `variation`).
			setSelectedVariationId: () => {
				const { mainProductInContext: product } = productsState;

				if ( ! product?.variations?.length ) {
					return;
				}

				const { selectedAttributes } = getContext< Context >();
				const result = productsState.findProduct( {
					id: product.id,
					selectedAttributes,
				} );
				// findProduct returns the parent when no variation
				// matches — only accept an actual variation.
				const matchedVariation =
					result && result.id !== product.id ? result : null;

				const variationId = matchedVariation?.id ?? null;
				const productContext = getContext< {
					variationId?: number | null;
				} >( 'woocommerce/products' );

				// If there is context, update the context. Otherwise, update the state directly.
				( productContext
					? productContext
					: productsState
				).variationId = variationId;
			},
			validateVariation() {
				actions.clearErrors( 'variable-product' );

				const { mainProductInContext: product } = productsState;

				if ( ! product?.variations?.length ) {
					return;
				}

				const { selectedAttributes } = getContext< Context >();
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

				const { productVariationInContext: variation } = productsState;

				if ( ! variation ) {
					return;
				}

				const { minimum, maximum } = variation.add_to_cart;

				// Quantity now lives on the shared-store draft, keyed by the
				// main/context product id and variation-independent (one draft
				// per product). Read and write it under that id rather than the
				// per-variation key the old `context.quantity` map used.
				const productId = getContextProductId();
				if ( productId === undefined ) {
					return;
				}
				const currentValue = getDraftQuantity( productId );

				let newValue = currentValue;
				if ( currentValue < minimum ) {
					newValue = minimum;
				} else if ( currentValue > maximum ) {
					newValue = maximum;
				}

				if (
					newValue !== ref.valueAsNumber ||
					newValue !== currentValue
				) {
					actions.setQuantity( productId, newValue );
				}
			},
		},
	},
	{ lock: universalLock }
);
