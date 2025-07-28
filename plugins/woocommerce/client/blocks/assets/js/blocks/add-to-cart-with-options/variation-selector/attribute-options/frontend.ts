/**
 * External dependencies
 */
import type { ChangeEvent } from 'react';
import { store, getContext } from '@wordpress/interactivity';
import { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsStoreContext,
} from '../../frontend';
import { type AvailableVariation } from '../../../../base/utils/variations/get-matched-variation';
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
	const context = getContext< Context >();

	if ( context.selectedValue ) {
		setAttribute( context.name, context.selectedValue );
	}
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
	selectedAttributes: SelectedAttributes[];
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

const { state } = store(
	'woocommerce/add-to-cart-with-options-variation-selector-attribute-options__pills',
	{
		state: {
			get isPillSelected() {
				const { selectedValue, option } = getContext< Context >();
				return selectedValue === option.value;
			},
			get isPillDisabled() {
				const { name, option } = getContext< Context >();
				const { selectedAttributes, availableVariations } =
					getContext< AddToCartWithOptionsStoreContext >(
						'woocommerce/add-to-cart-with-options'
					);

				return ! isAttributeValueValid( {
					attributeName: name,
					attributeValue: option.value,
					selectedAttributes,
					availableVariations,
				} );
			},
			get index() {
				const context = getContext< Context >();
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
				const context = getContext< Context >();
				if ( context.selectedValue === context.option.value ) {
					context.selectedValue = '';
				} else {
					context.selectedValue = context.option.value;
				}
				setAttribute( context.name, context.selectedValue );
			},
		},
		callbacks: {
			setDefaultSelectedAttribute,
		},
	},
	{ lock: true }
);

store(
	'woocommerce/add-to-cart-with-options-variation-selector-attribute-options__dropdown',
	{
		state: {
			get isOptionDisabled() {
				const { name, option } = getContext< Context >();

				if ( option.value === '' ) {
					return false;
				}

				const { selectedAttributes, availableVariations } =
					getContext< AddToCartWithOptionsStoreContext >(
						'woocommerce/add-to-cart-with-options'
					);

				return ! isAttributeValueValid( {
					attributeName: name,
					attributeValue: option.value,
					selectedAttributes,
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
