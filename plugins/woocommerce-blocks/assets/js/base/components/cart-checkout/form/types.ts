/**
 * External dependencies
 */
import type {
	FormFields,
	FormType,
	KeyedFormField,
	FormFieldsConfig,
} from '@woocommerce/settings';

export type AddressFormFields = {
	fields: KeyedFormField[];
	addressType: FormType;
	required: KeyedFormField[];
	hidden: KeyedFormField[];
};

export interface AddressFormProps< T > {
	// Id for component.
	id?: string;
	// Type of form (billing or shipping).
	addressType?: FormType;
	// Array of fields in form.
	fields: ( keyof FormFields )[];
	// Field configuration for fields in form.
	fieldConfig?: FormFieldsConfig;
	// Called with the new address data when the address form changes. This is only called when all required fields are filled and there are no validation errors.
	onChange: ( newValue: T ) => void;
	// Values for fields.
	values: T;
	// support inserting children at end of form
	children?: React.ReactNode;
}
