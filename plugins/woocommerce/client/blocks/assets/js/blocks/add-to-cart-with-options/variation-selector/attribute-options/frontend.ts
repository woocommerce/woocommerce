/**
 * External dependencies
 */
import type { ChangeEvent, KeyboardEvent } from 'react';
import { store, getContext, getElement } from '@wordpress/interactivity';
import type { CartVariationItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsStoreContext,
	AvailableVariation,
} from '../../frontend';
import setStyles from './set-styles';

type Option = {
	value: string;
	label: string;
	isSelected: boolean;
};

type Context = {
	name: string;
	selectedValue: string | null;
	option: Option;
	options: Option[];
};

type PillsContext = Context & {
	focused?: string;
};

// Set selected pill styles for proper contrast.
setStyles();

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { actions: wooAddToCartWithOptions } = store< AddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{},
	{ lock: universalLock }
);

function setAttribute( name: string, value: string | null ) {
	if ( value ) {
		wooAddToCartWithOptions.setAttribute( name, value );
	} else {
		wooAddToCartWithOptions.removeAttribute( name );
	}
}

function setDefaultSelectedAttribute() {
	const context = getContext< PillsContext >();
	setAttribute( context.name, context.selectedValue );
}

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
	availableVariations,
}: {
	attributeName: string;
	attributeValue: string;
	selectedAttributes: CartVariationItem[];
	availableVariations: AvailableVariation[];
} ) => {
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

	// Check if there is at least one available variation matching the current
	// selected attributes and the attribute value being checked.
	return availableVariations.some( ( availableVariation ) => {
		// Skip variations that don't match the current attribute value.
		if (
			availableVariation.attributes[
				'attribute_' + attributeName.toLowerCase()
			] !== attributeValue &&
			availableVariation.attributes[
				'attribute_' + attributeName.toLowerCase()
			] !== '' // "" is used for "any".
		) {
			return false;
		}

		// Count how many of the selected attributes match the variation.
		const matchingAttributes = selectedAttributes.filter(
			( selectedAttribute ) => {
				const availableVariationAttributeValue =
					availableVariation.attributes[
						'attribute_' + selectedAttribute.attribute.toLowerCase()
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
						selectedAttribute.attribute.toLowerCase() !==
							attributeName.toLowerCase() ||
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

const { state, actions } = store(
	'woocommerce/add-to-cart-with-options-variation-selector-attribute-options__pills',
	{
		state: {
			get isPillSelected() {
				const { selectedValue, option } = getContext< PillsContext >();
				return selectedValue === option.value;
			},
			get isPillDisabled() {
				const { name, option } = getContext< PillsContext >();
				const { variation, availableVariations } =
					getContext< AddToCartWithOptionsStoreContext >(
						'woocommerce/add-to-cart-with-options'
					);

				return ! isAttributeValueValid( {
					attributeName: name,
					attributeValue: option.value,
					selectedAttributes: variation,
					availableVariations,
				} );
			},
			get pillTabIndex() {
				const { selectedValue, focused, option, options } =
					getContext< PillsContext >();

				if ( state.isPillDisabled ) {
					return -1;
				}

				// Allow the first pill to be focused when no option is selected.
				if (
					! selectedValue &&
					! focused &&
					options[ 0 ]?.value === option.value
				) {
					return 0;
				}

				if ( state.isPillSelected || focused === option.value ) {
					return 0;
				}

				return -1;
			},
			get index() {
				const context = getContext< PillsContext >();
				return context.options.findIndex(
					( option ) => option.value === context.option.value
				);
			},
		},
		actions: {
			toggleSelected() {
				if ( state.isPillDisabled ) {
					return;
				}
				const context = getContext< PillsContext >();
				if ( context.selectedValue === context.option.value ) {
					context.selectedValue = '';
				} else {
					context.selectedValue = context.option.value;
				}
				context.focused = context.option.value;
				setAttribute( context.name, context.selectedValue );
			},
			handleKeyDown( event: KeyboardEvent< HTMLElement > ) {
				let keyWasProcessed = false;

				switch ( event.key ) {
					case ' ':
						keyWasProcessed = true;
						actions.toggleSelected();
						break;

					case 'Up':
					case 'ArrowUp':
					case 'Left':
					case 'ArrowLeft': {
						keyWasProcessed = true;
						const context = getContext< PillsContext >();
						const { variation, availableVariations } =
							getContext< AddToCartWithOptionsStoreContext >(
								'woocommerce/add-to-cart-with-options'
							);
						const { index } = state;
						if ( index <= 0 ) {
							return;
						}

						for ( let i = index - 1; i >= 0; i-- ) {
							if (
								isAttributeValueValid( {
									attributeName: context.name,
									attributeValue: context.options[ i ].value,
									selectedAttributes: variation,
									availableVariations,
								} )
							) {
								context.selectedValue =
									context.options[ i ].value;
								context.focused = context.selectedValue;

								setAttribute(
									context.name,
									context.selectedValue
								);

								return;
							}
						}
						break;
					}

					case 'Down':
					case 'ArrowDown':
					case 'Right':
					case 'ArrowRight': {
						keyWasProcessed = true;
						const context = getContext< PillsContext >();
						const { variation, availableVariations } =
							getContext< AddToCartWithOptionsStoreContext >(
								'woocommerce/add-to-cart-with-options'
							);
						const { index } = state;
						if ( index >= context.options.length - 1 ) {
							return;
						}

						for (
							let i = index + 1;
							i < context.options.length;
							i++
						) {
							if (
								isAttributeValueValid( {
									attributeName: context.name,
									attributeValue: context.options[ i ].value,
									selectedAttributes: variation,
									availableVariations,
								} )
							) {
								context.selectedValue =
									context.options[ i ].value;
								context.focused = context.selectedValue;

								setAttribute(
									context.name,
									context.selectedValue
								);

								return;
							}
						}
						break;
					}
					default:
						break;
				}

				if ( keyWasProcessed ) {
					event.stopPropagation();
					event.preventDefault();
				}
			},
		},
		callbacks: {
			setDefaultSelectedAttribute,
			watchSelected() {
				const { focused } = getContext< PillsContext >();

				if ( state.pillTabIndex === 0 && focused ) {
					const { ref } = getElement();
					ref?.focus();
				}
			},
		},
	},
	{ lock: true }
);

store(
	'woocommerce/add-to-cart-with-options-variation-selector-attribute-options__dropdown',
	{
		state: {
			get isOptionDisabled() {
				const { name, option } = getContext< PillsContext >();

				if ( option.value === '' ) {
					return false;
				}

				const { variation, availableVariations } =
					getContext< AddToCartWithOptionsStoreContext >(
						'woocommerce/add-to-cart-with-options'
					);

				return ! isAttributeValueValid( {
					attributeName: name,
					attributeValue: option.value,
					selectedAttributes: variation,
					availableVariations,
				} );
			},
		},
		actions: {
			handleChange( event: ChangeEvent< HTMLSelectElement > ) {
				const context = getContext< Context >();
				context.selectedValue = event.currentTarget.value;
				setAttribute( context.name, context.selectedValue );
			},
		},
		callbacks: {
			setDefaultSelectedAttribute,
		},
	},
	{ lock: true }
);
