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

	// Optional properties - validate type if present
	if ( field.autocomplete !== undefined && typeof field.autocomplete !== 'string' ) {
		return false;
	}

	if ( field.autocapitalize !== undefined && typeof field.autocapitalize !== 'string' ) {
		return false;
	}

	if ( field.type !== undefined && typeof field.type !== 'string' ) {
		return false;
	}

	// Validation can be boolean, array, or JSON schema object
	if ( field.validation !== undefined && 
		 typeof field.validation !== 'boolean' &&
		 ! Array.isArray( field.validation ) &&
		 typeof field.validation !== 'object' ) {
		return false;
	}

	// For select fields
	if ( field.options !== undefined && ! Array.isArray( field.options ) ) {
		return false;
	}

	if ( field.placeholder !== undefined && typeof field.placeholder !== 'string' ) {
		return false;
	}

	// Attributes should be an object if present
	if ( field.attributes !== undefined && 
		 ( typeof field.attributes !== 'object' || field.attributes === null || Array.isArray( field.attributes ) ) ) {
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
		'phone'
	];

	// We'll check for a minimum subset to allow flexibility
	// but still ensure it's a valid checkout form structure
	const minimumRequiredFields = [ 'first_name', 'last_name', 'email' ];
	
	if ( ! minimumRequiredFields.every( ( field ) => field in fields ) ) {
		return false;
	}

	// Validate each field has the proper Field structure
	for ( const [ fieldKey, fieldValue ] of Object.entries( fields ) ) {
		if ( ! isField( fieldValue ) ) {
			return false;
		}

		// Additional validation for specific core fields
		if ( fieldKey === 'email' ) {
			const emailField = fieldValue as Record< string, unknown >;
			if ( emailField.type !== undefined && emailField.type !== 'email' ) {
				return false;
			}
		}

		if ( fieldKey === 'phone' ) {
			const phoneField = fieldValue as Record< string, unknown >;
			if ( phoneField.type !== undefined && phoneField.type !== 'tel' ) {
				return false;
			}
		}
	}

	return true;
};