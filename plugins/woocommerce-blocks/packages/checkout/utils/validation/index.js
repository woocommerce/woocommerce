/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Checks if value passed is a string, throws an error if not.
 *
 * @param {string} value Value to be validated.
 *
 * @return {Error|true} Error if value is not string, true otherwise.
 */
export const mustBeString = ( value ) => {
	if ( typeof value !== 'string' ) {
		throw Error(
			sprintf(
				/* translators: %s is type of value passed */
				__(
					'Returned value must be a string, you passed "%s"',
					'woo-gutenberg-products-block'
				),
				typeof value
			)
		);
	}
	return true;
};

/**
 * Checks if value passed contain passed label
 *
 * @param {string} value Value to be validated.
 * @param {string} label Label to be searched for.
 *
 * @return {Error|true} Error if value contains label, true otherwise.
 */
export const mustContain = ( value, label ) => {
	if ( ! value.includes( label ) ) {
		throw Error(
			sprintf(
				/* translators: %1$s value passed to filter, %2$s : value that must be included. */
				__(
					'Returned value must include %1$s, you passed "%2$s"',
					'woo-gutenberg-products-block'
				),
				value,
				label
			)
		);
	}
	return true;
};

/**
 * A function that always return true.
 * We need to have a single instance of this function so it doesn't
 * invalidate our memo comparison.
 *
 *
 * @return {true} Returns true.
 */
export const returnTrue = () => true;
