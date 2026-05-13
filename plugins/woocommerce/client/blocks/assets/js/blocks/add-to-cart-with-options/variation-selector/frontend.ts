/**
 * External dependencies
 */
import {
	store,
	getContext,
	getConfig,
	getElement,
	getServerContext,
} from '@wordpress/interactivity';
import { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type {
	AddToCartWithOptionsStore,
	Context as FormContext,
} from '../frontend';
import type { SelectableItem } from '../../../types/type-defs/selectable-items';
import {
	normalizeAttributeName,
	attributeNamesMatch,
	getVariationAttributeValue,
} from '../../../base/utils/variations/attribute-matching';
import setStyles from './set-styles';

type VariationOptionItem = {
	id: string;
	label: string;
	value: string;
	ariaLabel?: string;
};

type VariationAttributeRowContext = FormContext & {
	name: string;
	selectedValue: string | null;
	variationAttributeOptions: VariationOptionItem[];
	autoselect: boolean;
	disabledAttributesAction?: 'disable' | 'hide';
};

type ToggleContext = VariationAttributeRowContext & {
	item?: SelectableItem;
};

setStyles();

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

function getVariationRowContext(): VariationAttributeRowContext | undefined {
	const client = getContext< VariationAttributeRowContext >();
	if (
		client &&
		Array.isArray( client.variationAttributeOptions ) &&
		client.variationAttributeOptions.length > 0
	) {
		return client;
	}
	const server =
		typeof getServerContext === 'function'
			? getServerContext< VariationAttributeRowContext >()
			: undefined;
	if (
		server &&
		Array.isArray( server.variationAttributeOptions ) &&
		server.variationAttributeOptions.length > 0
	) {
		return server;
	}
	return undefined;
}

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

	return product.variations.some( ( variation ) => {
		const variationAttrValue = getVariationAttributeValue(
			variation,
			attributeName
		);

		if (
			variationAttrValue !== attributeValue &&
			variationAttrValue !== null
		) {
			return false;
		}

		const matchingAttributes = selectedAttributes.filter(
			( selectedAttribute ) => {
				const availableVariationAttributeValue =
					getVariationAttributeValue(
						variation,
						selectedAttribute.attribute
					);
				if (
					availableVariationAttributeValue === selectedAttribute.value
				) {
					return true;
				}
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
			selectableItems: readonly SelectableItem[];
		};
		actions: {
			setAttribute: ( attribute: string, value: string ) => void;
			removeAttribute: ( attribute: string ) => void;
			toggle: ( item?: SelectableItem ) => void;
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
				const context = getContext< FormContext >();
				if ( ! context ) {
					return [];
				}
				return context.selectedAttributes || [];
			},
			get selectableItems(): readonly SelectableItem[] {
				const ctx = getVariationRowContext();
				if ( ! ctx ) {
					return [];
				}
				const raw = ctx.variationAttributeOptions;
				const { name, disabledAttributesAction } = ctx;
				const selectedAttributes = state.selectedAttributes;
				const hideInvalid = disabledAttributesAction === 'hide';

				return raw.map( ( row, index ) => {
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
					};
				} );
			},
		},
		actions: {
			setAttribute( attribute: string, value: string ) {
				const { selectedAttributes } = getContext< FormContext >();
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
				const { selectedAttributes } = getContext< FormContext >();
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
			toggle( itemArg?: SelectableItem | Event ) {
				const context = getContext< ToggleContext >();
				const item =
					itemArg && ! ( itemArg instanceof Event )
						? itemArg
						: context.item;
				if ( ! item || item.hidden ) {
					return;
				}
				if ( item.disabled ) {
					return;
				}

				const rowCtx = getVariationRowContext();
				if ( ! rowCtx ) {
					return;
				}

				const { name } = rowCtx;
				const selectedAttributes = state.selectedAttributes;
				const isCurrentlySelected = selectedAttributes.some(
					( attrObject ) =>
						attributeNamesMatch( attrObject.attribute, name ) &&
						attrObject.value === item.value
				);

				if ( isCurrentlySelected ) {
					rowCtx.selectedValue = '';
					actions.setAttribute( name, '' );
				} else {
					rowCtx.selectedValue = item.value;
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
				const rowCtx = getVariationRowContext();
				if ( ! rowCtx || ! rowCtx.autoselect ) {
					return;
				}

				const selectedAttributes = state.selectedAttributes;

				const { mainProductInContext: product } = productsState;
				if ( ! product ) {
					return;
				}

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
				const ctx = getVariationRowContext();
				if ( ! ctx ) {
					return;
				}

				if ( ctx.selectedValue ) {
					actions.setAttribute( ctx.name, ctx.selectedValue );
				}
				actions.autoselectAttributes( {
					includedAttributes: [ ctx.name ],
				} );
			},
			setSelectedVariationId: () => {
				const { mainProductInContext: product } = productsState;

				if ( ! product?.variations?.length ) {
					return;
				}

				const { selectedAttributes } = getContext< FormContext >();
				const result = productsState.findProduct( {
					id: product.id,
					selectedAttributes,
				} );
				const matchedVariation =
					result && result.id !== product.id ? result : null;

				const variationId = matchedVariation?.id ?? null;
				const productContext = getContext< {
					variationId?: number | null;
				} >( 'woocommerce/products' );

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

				const { selectedAttributes } = getContext< FormContext >();
				const result = productsState.findProduct( {
					id: product.id,
					selectedAttributes,
				} );
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

				const variationData =
					productsState.productVariations[ matchedVariation.id ];

				if ( ! variationData ) {
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
			watchQuantityConstraints() {
				const { ref } = getElement();

				if ( ! ( ref instanceof HTMLInputElement ) ) {
					return;
				}

				if ( ref === document.activeElement ) {
					return;
				}

				const { productVariationInContext: variation } = productsState;

				if ( ! variation ) {
					return;
				}

				const { minimum, maximum } = variation.add_to_cart;

				const { quantity } = getContext< FormContext >();
				const currentValue = quantity[ variation.id ];

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

export { state };
