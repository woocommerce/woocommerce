/**
 * External dependencies
 */
import type {
	AddressFormValues,
	ContactFormValues,
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

/**
 * Internal dependencies
 */
import { FieldProps } from './utils';

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
	onChange: ( newValue: AddressFormValues | ContactFormValues ) => void;
	// Values for fields.
	values: T;
	// support inserting children at end of form
	children?: React.ReactNode;
}

// export interface AddressFieldsProps< T > extends AddressFormProps< T > {
export interface AddressFieldsProps< T >
	extends Omit< AddressFormProps< T >, 'fields' > {
	// Overwriting the id for the fields.
	id: string;
	// Overwriting the address type for the fields.
	addressType: FormType;
	// Address 1 fields.
	address1?: KeyedFormField | undefined;
	// Address 2 fields.
	address2?: KeyedFormField | undefined;
	// Address form fields.
	addressFormFields: AddressFormFields;
}

export interface AddressFieldProps< T > {
	// The specific form field.
	field: KeyedFormField;
	// Props for the form field.
	fieldProps: FieldProps;
	// Called with the new address data when the address form changes. This is only called when all required fields are filled and there are no validation errors.
	onChange: ( newValue: T ) => void;
	// Values for fields.
	values: T;
}
