/**
 * External dependencies
 */
import type {
	FormFields,
	AddressFormValues,
	AddressFormType,
	AddressForm,
	ContactFormValues,
	OrderFormValues,
} from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { FieldProps } from './utils';

export interface FormProps<
	T extends AddressFormValues | ContactFormValues | OrderFormValues
> {
	// Id for component.
	id?: string;
	// Type of form (billing or shipping).
	addressType?: AddressFormType;
	// aria-describedby attribute to add to the input.
	ariaDescribedBy?: string;
	// Array of fields in form.
	fields: ( keyof FormFields )[];
	// Called with the new address data when the address form changes. This is only called when all required fields are filled and there are no validation errors.
	onChange: ( newValue: T ) => void;
	// Values for fields.
	values: T;
	// support inserting children at end of form
	children?: React.ReactNode;
	// Is the form in editing mode.
	isEditing?: boolean;
}

export interface AddressLineFieldsProps
	extends Omit<
		FormProps< AddressFormValues >,
		'fields' | 'values' | 'onChange'
	> {
	// Overwriting the id for the fields.
	formId: string;
	// Address 1 fields and value.
	address1: {
		field: AddressForm[ 'address_1' ] & {
			key: 'address_1';
			errorMessage?: string;
		};
		value: AddressFormValues[ 'address_1' ];
	};
	// Address 2 fields and value.
	address2: {
		field: AddressForm[ 'address_2' ] & {
			key: 'address_2';
			errorMessage?: string;
		};
		value: AddressFormValues[ 'address_2' ];
	};
	// Overwriting the address type for the fields.
	addressType: AddressFormType;
	// Called with the new address data when the address form changes. This is only called when all required fields are filled and there are no validation errors.
	onChange: ( key: 'address_1' | 'address_2', value: string ) => void;
}

export interface AddressLineFieldProps {
	// Form fields.
	field: AddressForm[ 'address_2' ] & {
		key: 'address_2';
		errorMessage?: string;
	};
	// Props for the form field.
	props?: FieldProps | undefined;
	// Called with the new address data when the address form changes. This is only called when all required fields are filled and there are no validation errors.
	onChange: ( value: string ) => void;
	// Value for field.
	value?: string | undefined;
}
