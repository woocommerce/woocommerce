/**
 * External dependencies
 */
import {
	store,
	getContext,
	getConfig,
	getElement,
} from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/cart';
import type {
	SelectedAttributes,
	Store as WooCommerce,
} from '@woocommerce/stores/woocommerce/cart';
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
	/**
	 * Whether this surface has ever had a non-empty attribute selection of
	 * its own. Set by `callbacks.setSelectedVariationId` the first time it
	 * observes `selectedAttributes.length > 0`, and never unset — it marks
	 * this surface as "the one configuring the product" for the rest of its
	 * lifetime, even if the shopper later clears the selection back to
	 * empty. Absent (falsy) until then.
	 */
	hasSelectedAttribute?: boolean;
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

// Todo: Use the module exports instead of `store()` once the woocommerce
// store is public.
const { state: cartState, actions: wooActions } = store< WooCommerce >(
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

	const { baseProductInContext: product } = productsState;

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
			selectedAttributes: SelectedAttributes[];
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

const { actions, state } = store< VariableProductAddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{
		state: {
			get selectedAttributes(): SelectedAttributes[] {
				const context = getContext< Context >();
				if ( ! context ) {
					return [];
				}

				// Prefer the current scope's cart draft for the in-context
				// product/variation: the value every surface sharing the
				// scope writes to and reads from, so an attribute picked on
				// one surface is reflected on every other. Falls back to
				// this instance's own locally-tracked selection when the
				// scope holds no draft yet, or the draft carries no
				// `variation` (e.g. a simple product's draft).
				const draftVariation = cartState.itemInContext.draft?.variation;
				if ( draftVariation ) {
					return draftVariation;
				}

				return context.selectedAttributes || [];
			},
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
				const { selectedAttributes } = state;
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
						attributeNamesMatch(
							selectedAttribute.attribute,
							attribute
						)
				);
				if ( index >= 0 ) {
					selectedAttributes.splice( index, 1 );
				}
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
				const { selectedAttributes } = state;
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

				const { selectedAttributes } = state;

				const { baseProductInContext: product } = productsState;
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
			setSelectedVariationId: () => {
				const { baseProductInContext: product } = productsState;

				if ( ! product?.variations?.length ) {
					return;
				}

				const context = getContext< Context >();
				const { selectedAttributes, quantity } = context;
				const productContext = getContext< {
					variationId?: number | null;
				} >( 'woocommerce/products' );
				const currentVariationId = productContext
					? productContext.variationId
					: productsState.variationId;

				// `data-wp-watch` reruns this callback whenever any
				// reactive value it reads changes — not only this
				// surface's own `selectedAttributes` (e.g. variation data
				// completing an async load re-triggers it on every mounted
				// surface sharing the page, whether or not the shopper is
				// using that particular one). A surface with no attribute
				// selection of its own, that never had one, must not
				// clobber another surface's already-resolved shared
				// selection with its own "nothing selected" default — the
				// same initialize-if-absent discipline `seedDraftIfAbsent`
				// follows for the draft itself. A surface that *did* make a
				// real selection at some point keeps writing from then on,
				// including clearing it back to empty — that is a genuine
				// edit on this surface, not a bystander's stale
				// re-evaluation.
				if (
					selectedAttributes.length === 0 &&
					! context.hasSelectedAttribute &&
					currentVariationId !== null &&
					currentVariationId !== undefined
				) {
					return;
				}
				if ( selectedAttributes.length > 0 ) {
					context.hasSelectedAttribute = true;
				}

				const result = productsState.findProduct( {
					id: product.id,
					selectedAttributes,
				} );
				// findProduct returns the parent when no variation
				// matches — only accept an actual variation.
				const matchedVariation =
					result && result.id !== product.id ? result : null;

				const variationId = matchedVariation?.id ?? null;

				// If there is context, update the context. Otherwise, update the state directly.
				( productContext
					? productContext
					: productsState
				).variationId = variationId;

				// Mirror the attribute selection into the resolved
				// product/variation's cart draft — the id `itemInContext`
				// will resolve at submit time. `quantity` rides along so a
				// variation drafted for the first time here always has one
				// (an id-only new draft is rejected). This surface's own
				// locally-tracked quantity for the resolved id (see the
				// quantity selector's `idsToUpdate` sync) is only guaranteed
				// to match what the shopper actually sees when this is the
				// surface being edited — a sibling surface sharing the page
				// that never itself received a quantity edit carries a
				// stale local default here instead. That is not a problem
				// for this initial upsert (a brand new draft has no
				// competing value to protect); `watchQuantityConstraints`
				// is the one that must not let a bystander's stale local
				// value overwrite a draft another surface already resolved.
				const currentProductId = variationId ?? product.id;
				wooActions.upsertDraftItem(
					{
						quantity: quantity[ currentProductId ],
						variation: selectedAttributes,
					},
					{ id: currentProductId }
				);
			},
			validateVariation() {
				actions.clearErrors( 'variable-product' );

				const { baseProductInContext: product } = productsState;

				if ( ! product?.variations?.length ) {
					return;
				}

				// Read through the same draft-backed source
				// `state.selectedAttributes` gives the display (attribute
				// chips, `selectableItems`): a surface whose chips render
				// the scope's resolved selection as checked must validate —
				// and therefore gate its own Add to cart — against that
				// same selection, not this instance's local context, which
				// a sibling surface never editing its own chips would
				// otherwise validate against forever, dead-ending its
				// submit even though it displays a complete configuration.
				const { selectedAttributes } = state;
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

				// Clamp the shared scope draft's own quantity — the same
				// value `resolveDisplayQuantity` prefers for every surface's
				// display — rather than this surface's local `quantity`
				// map. `data-wp-watch` reruns this callback on every
				// surface sharing the page whenever the globally-resolved
				// variation changes, whether or not the shopper is using
				// that particular surface; a surface that never received a
				// quantity edit of its own carries a stale local default in
				// its own map, and "correcting" the draft back to that
				// default the instant another surface resolves a variation
				// would destroy the editing surface's genuine quantity —
				// the same bystander-clobber class `setSelectedVariationId`
				// guards against. Falls back to the local value only when
				// no draft exists yet for the resolved variation (nothing
				// else to clamp).
				const { quantity } = getContext< Context >();
				const draftQuantity = cartState.itemInContext.draft?.quantity;
				const currentValue =
					typeof draftQuantity === 'number'
						? draftQuantity
						: quantity[ variation.id ];

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
					actions.setQuantity( variation.id, newValue );
				}
			},
		},
	},
	{ lock: universalLock }
);
