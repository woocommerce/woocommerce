/**
 * External dependencies
 */
import type { FormFields, Field } from '@woocommerce/settings';

/**
 * Type guard to check if a value is a valid Field object.
 * Based on the Field interface and CheckoutFields::get_core_fields() in PHP.
 *
 * @param value - The value to check.
 * @return Whether the value is a valid Field object.
 */
const isField = ( value: unknown ): value is Field => {
	if ( typeof value !== 'object' || value === null ) {
		return false;
	}

	const field = value as Record< string, unknown >;

	// Required properties that must always be present
	if (
		typeof field.label !== 'string' ||
		typeof field.optionalLabel !== 'string' ||
		typeof field.required !== 'boolean' ||
		typeof field.hidden !== 'boolean' ||
		typeof field.index !== 'number'
	) {
		return false;
	}

	return true;
};

/**
 * Type guard to check if a value is a valid FormFields object.
 * Validates that the object has the expected structure with proper field definitions.
 * Based on CheckoutFields::get_core_fields() which defines the core checkout fields.
 *
 * @param value - The value to check.
 * @return Whether the value is a valid FormFields object.
 */
export const isFormFields = ( value: unknown ): value is FormFields => {
	if (
		typeof value !== 'object' ||
		value === null ||
		Array.isArray( value )
	) {
		return false;
	}

	const fields = value as Record< string, unknown >;

	// Check if it has all core fields from CheckoutFields::get_core_fields()
	// These are the fields that should always be present
	const coreFields = [
		'email',
		'country',
		'first_name',
		'last_name',
		'company',
		'address_1',
		'address_2',
		'city',
		'state',
		'postcode',
		'phone',
	];

	if ( ! coreFields.every( ( field ) => field in fields ) ) {
		return false;
	}

	// Validate each field has the proper Field structure
	for ( const [ fieldId, fieldValue ] of Object.entries( fields ) ) {
		// If not included in core fields, it's an additional field we don't need to consider.
		if ( ! coreFields.includes( fieldId ) ) {
			continue;
		}
		if ( ! isField( fieldValue ) ) {
			return false;
		}
	}

	return true;
};
