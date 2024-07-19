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
	// Is the form in editing mode.
	isEditing?: boolean;
}

interface AddressFieldData {
	// Form fields.
	field?: KeyedFormField | undefined;
	// Field value.
	value?: string | undefined;
}

export interface AddressLineFieldsProps< T >
	extends Omit< AddressFormProps< T >, 'fields' | 'values' | 'onChange' > {
	// Overwriting the id for the fields.
	formId: string;
	// Address 1 fields and value.
	address1: AddressFieldData;
	// Address 2 fields and value.
	address2: AddressFieldData;
	// Overwriting the address type for the fields.
	addressType: FormType;
	// Called with the new address data when the address form changes. This is only called when all required fields are filled and there are no validation errors.
	onChange: ( key: keyof T, value: string ) => void;
}

export interface AddressLineFieldProps< T > {
	// Form fields.
	field: KeyedFormField;
	// Props for the form field.
	props?: FieldProps | undefined;
	// Called with the new address data when the address form changes. This is only called when all required fields are filled and there are no validation errors.
	onChange: ( key: keyof T, value: string ) => void;
	// Value for field.
	value?: string | undefined;
}
