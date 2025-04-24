/**
 * Returns an error message if the input is empty.
 */
export const validateRequiredField = ( value: string ): string | undefined => {
	return value.trim() === '' ? 'This field is required.' : undefined;
};

/**
 * Returns an error message if the input is not numeric.
 */
export const validateNumericField = ( value: string ): string | undefined => {
	return /^\d+$/.test( value.trim() )
		? undefined
		: 'This field must be numeric.';
};
