/**
 * Returns an error message if the input is empty.
 */
export const validateRequiredField = ( value: string ): string | undefined => {
	return value.trim() === '' ? 'This field is required.' : undefined;
};
