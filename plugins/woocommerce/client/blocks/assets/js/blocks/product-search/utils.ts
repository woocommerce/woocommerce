/**
 * Internal dependencies
 */
import {
	PositionOptions,
	SEARCH_BLOCK_NAME,
	SEARCH_VARIATION_NAME,
} from './constants';
import { ButtonPositionProps, ProductSearchBlockProps } from './types';

/**
 * Identifies if a block is a Search block variation from our conventions
 *
 * We are extending Gutenberg's core Search block with our variations, and
 * also adding extra namespaced attributes. If those namespaced attributes
 * are present, we can be fairly sure it is our own registered variation.
 *
 * @param {ProductSearchBlockProps} block - A WooCommerce block.
 */
export function isWooSearchBlockVariation( block: ProductSearchBlockProps ) {
	return (
		block.name === SEARCH_BLOCK_NAME &&
		block.attributes?.namespace === SEARCH_VARIATION_NAME
	);
}

/**
 * Checks if the given button position is a valid option for input and button placement.
 *
 * The function verifies if the provided `buttonPosition` matches one of the predefined
 * values for placing a button either inside or outside an input field.
 *
 * @param {string} buttonPosition - The position of the button to check.
 */
export function isInputAndButtonOption( buttonPosition: string ): boolean {
	return (
		buttonPosition === 'button-outside' ||
		buttonPosition === 'button-inside'
	);
}

/**
 * Returns the option for the selected button position
 *
 * Based on the provided `buttonPosition`, the function returns a predefined option
 * if the position is valid for input and button placement. If the position is not
 * one of the predefined options, it returns the original `buttonPosition`.
 *
 * @param {string} buttonPosition - The position of the button to evaluate.
 */
export function getSelectedRadioControlOption(
	buttonPosition: string
): string {
	if ( isInputAndButtonOption( buttonPosition ) ) {
		return PositionOptions.INPUT_AND_BUTTON;
	}
	return buttonPosition;
}

/**
 * Returns the appropriate option for input and button placement based on the given value
 *
 * This function checks if the provided `value` is a valid option for placing a button either
 * inside or outside an input field. If the `value` is valid, it is returned as is. If the `value`
 * is not valid, the function returns a default option.
 *
 * @param {ButtonPositionProps} value - The position of the button to evaluate.
 */
export function getInputAndButtonOption( value: ButtonPositionProps ) {
	if ( isInputAndButtonOption( value ) ) {
		return value;
	}
	// The default value is 'inside' for input and button.
	return PositionOptions.OUTSIDE;
}
