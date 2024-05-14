/**
 * External dependencies
 */
import type { ValidatedTextInputHandle } from '@woocommerce/blocks-components';
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
	onChange: ( newValue: T ) => void;
	// Values for fields.
	values: T;
	// support inserting children at end of form
	children?: React.ReactNode;
}

export interface AddressFieldsProps< T > extends AddressFormProps< T > {
	// Overwriting the id for the fields.
	id: string;
	// Overwriting the address type for the fields.
	addressType: FormType;
	// Address 1 fields.
	address1?: KeyedFormField;
	// Address 2 fields.
	address2?: KeyedFormField;
	// Address form fields.
	addressFormFields: AddressFormFields;
	// Ref for fields.
	fieldsRef: React.MutableRefObject<
		Record< string, ValidatedTextInputHandle | null >
	>;
}

export interface AddressFieldProps< T > {
	// The specific form field.
	field: KeyedFormField;
	// Props for the form field.
	fieldProps: FieldProps;
	// Ref for the fields.
	fieldsRef: React.MutableRefObject<
		Record< string, ValidatedTextInputHandle | null >
	>;
	// Called with the new address data when the address form changes. This is only called when all required fields are filled and there are no validation errors.
	onChange: ( newValue: T ) => void;
	// Values for fields.
	values: T;
}
