/**
 * External dependencies
 */
import type { ChangeEvent, KeyboardEvent } from 'react';
import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { AddToCartWithOptionsStore } from '../../frontend';
import setStyles from './set-styles';

type Option = {
	value: string;
	label: string;
	isSelected: boolean;
};

type Context = {
	attribute: string;
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

const isAttributeDisabled = ( {
	attributeName,
	attributeValue,
	selectedAttributes,
	availableVariations,
} ) => {
	if ( ! selectedAttributes || selectedAttributes.length === 0 ) {
		return false;
	}

	const isCurrentAttributeSelected = selectedAttributes.some(
		( selectedAttribute ) => selectedAttribute.attribute === attributeName
	);
	const attributesToMatch = isCurrentAttributeSelected
		? selectedAttributes.length - 1
		: selectedAttributes.length;

	return ! availableVariations.some( ( availableVariation ) => {
		// Skip variations that don't match the current pills.
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

		// Count how many attributes from the variation match the
		// currently selected attributes.
		const matchingAttributes = selectedAttributes.filter(
			( selectedAttribute ) => {
				const availableVariationAttributeValue =
					availableVariation.attributes[
						'attribute_' + selectedAttribute.attribute.toLowerCase()
					];
				// If the current available variation matches the selected value.
				if (
					availableVariationAttributeValue === selectedAttribute.value
				) {
					return true;
				}
				// If the current available variation has an empty value (matching any),
				// only count variations that match the currently selected
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
				const { variation, availableVariations } = getContext(
					'woocommerce/add-to-cart-with-options'
				);

				return isAttributeDisabled( {
					attributeName: name,
					attributeValue: option.value,
					selectedAttributes: variation,
					availableVariations: availableVariations,
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
				const context = getContext< PillsContext >();
				if ( state.isPillDisabled ) {
					return;
				}
				if ( context.selectedValue === context.option.value ) {
					context.selectedValue = '';
				} else {
					context.selectedValue = context.option.value;
				}
				context.focused = context.option.value;
				setAttribute( context.name, context.selectedValue );
			},
			handleKeyDown( event: KeyboardEvent< HTMLElement > ) {
				switch ( event.key ) {
					case ' ':
						event.stopPropagation();
						event.preventDefault();
						actions.toggleSelected();
						break;

					case 'Up':
					case 'ArrowUp':
					case 'Left':
					case 'ArrowLeft': {
						event.stopPropagation();
						event.preventDefault();
						const context = getContext< PillsContext >();
						const { variation, availableVariations } = getContext(
							'woocommerce/add-to-cart-with-options'
						);
						const { index } = state;
						if ( index <= 0 ) {
							return;
						}

						for ( let i = index - 1; i >= 0; i-- ) {
							if (
								! isAttributeDisabled( {
									attributeName: context.name,
									attributeValue: context.options[ i ].value,
									selectedAttributes: variation,
									availableVariations: availableVariations,
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
						event.stopPropagation();
						event.preventDefault();
						const context = getContext< PillsContext >();
						const { variation, availableVariations } = getContext(
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
								! isAttributeDisabled( {
									attributeName: context.name,
									attributeValue: context.options[ i ].value,
									selectedAttributes: variation,
									availableVariations: availableVariations,
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
