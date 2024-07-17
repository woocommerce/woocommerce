/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { isPostcode } from '@woocommerce/blocks-checkout';
import { isEmail } from '@wordpress/url';

/**
 * Custom validation handler for fields with field specific handling.
 */
const customValidationHandler = (
	inputObject: HTMLInputElement,
	field: string,
	country: string
): boolean => {
	// Pass validation if the field is not required and is empty.
	if ( ! inputObject.required && ! inputObject.value ) {
		return true;
	}

	if (
		field === 'postcode' &&
		country &&
		! isPostcode( {
			postcode: inputObject.value,
			country,
		} )
	) {
		inputObject.setCustomValidity(
			__( 'Please enter a valid postcode', 'woocommerce' )
		);
		return false;
	}

	if ( field === 'email' && ! isEmail( inputObject.value ) ) {
		inputObject.setCustomValidity(
			__( 'Please enter a valid email address', 'woocommerce' )
		);
		return false;
	}

	return true;
};

export default customValidationHandler;
