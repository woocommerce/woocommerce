/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { isPostcode } from '@woocommerce/blocks-checkout';

/**
 * Custom validation handler for fields with field specific handling.
 */
const customValidationHandler = (
	inputObject: HTMLInputElement,
	field: string,
	customValues: {
		country: string;
	}
): boolean => {
	// Pass validation if the field is not required and is empty.
	if ( ! inputObject.required && ! inputObject.value ) {
		return true;
	}

	if (
		field === 'postcode' &&
		customValues.country &&
		! isPostcode( {
			postcode: inputObject.value,
			country: customValues.country,
		} )
	) {
		inputObject.setCustomValidity(
			__(
				'Please enter a valid postcode',
				'woo-gutenberg-products-block'
			)
		);
		return false;
	}
	return true;
};

export default customValidationHandler;
