/**
 * External dependencies
 */
import type { DocumentObject } from '@woocommerce/base-hooks';
import type { JSONSchemaType } from 'ajv';
import type { AllHTMLAttributes, AriaAttributes } from 'react';

/**
 * Internal dependencies
 */
import { SelectOption } from '../../base/components';
import { getSetting } from './utils';

// A list of attributes that can be added to a custom field when registering it.
type CustomFieldAttributes = Pick<
	AllHTMLAttributes< HTMLInputElement >,
	| 'maxLength'
	| 'readOnly'
	| 'pattern'
	| 'title'
	| 'autoCapitalize'
	| 'autoComplete'
> &
	AriaAttributes;

/**
 * A field definition, on its raw form, passed to us from the store settings.
 */
export interface Field {
	// The label for the field.
	label: string;
	// The label for the field if made optional.
	optionalLabel: string;
	// The HTML autocomplete attribute value. See https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/autocomplete
	autocomplete: string;
	// How this field value is capitalized.
	autocapitalize?: string;
	// Set to true if the field is required or a JSON schema object.
	required: boolean | JSONSchemaType< DocumentObject< 'global' > > | [];
	// Set to true if the field should not be rendered or a JSON schema object.
	hidden: boolean | JSONSchemaType< DocumentObject< 'global' > > | [];
	// A JSON schema object for validation.
	validation: JSONSchemaType< DocumentObject< 'global' > > | [];
	// Fields will be sorted and render in this order, lowest to highest.
	index: number;
	// The type of input to render. Defaults to text.
	type?: string;
	// The options if this is a select field
	options?: SelectOption[];
	// The placeholder for the field, only applicable for select fields.
	placeholder?: string;
	// Additional attributes added when registering a field. String in key is required for data attributes.
	attributes?: Record< keyof CustomFieldAttributes, string >;
}

/**
 * Countries can override fields props depending on the country, a fieldLocaleOverrides contains those overrides.
 */
export type FieldLocaleOverrides = Partial< Field >;

/**
 * Shipping and billing address form fields.
 */
export interface AddressForm {
	first_name: Field;
	last_name: Field;
	company: Field;
	address_1: Field;
	address_2: Field;
	country: Field;
	city: Field;
	state: Field;
	postcode: Field;
	phone: Field;
	[ x: `${ string }/${ string }` ]: Field; // Additional fields are named like: namespace/field_name
}

/**
 * Contact form fields.
 */
export interface ContactForm {
	email: Field;
	[ x: `${ string }/${ string }` ]: Field;
}

/**
 * Order form fields.
 */
export interface OrderForm {
	[ x: `${ string }/${ string }` ]: Field;
}

/**
 * All possible fields for all forms.
 */
export type FormFields = AddressForm & ContactForm & OrderForm;

/**
 * KeyedFormFields is the array shape of FormFields object with the key added to each field.
 */
export type KeyedFormFields = Array<
	FormFields[ keyof FormFields ] & {
		key: keyof FormFields;
		errorMessage?: string;
	}
>;

/**
 * KeyedParsedFormFields is the array shape of FormFields object with the key added to each field.
 */
export type KeyedParsedFormFields = Array<
	FormFields[ keyof FormFields ] & {
		key: keyof FormFields;
		errorMessage?: string;
		hidden: boolean;
		required: boolean;
	}
>;
/**
 * All possible values for a form.
 */
export type FormValues = Record< keyof FormFields, string > & {
	[ x: `${ string }/${ string }` ]: string | boolean;
};

export type AddressFormValues = Pick< FormValues, keyof AddressForm >;
export type ContactFormValues = Pick< FormValues, keyof ContactForm >;
export type OrderFormValues = Pick< FormValues, keyof OrderForm >;

export type FormType = 'billing' | 'shipping' | 'contact' | 'order';

export type ShippingAddress = AddressFormValues;
export interface BillingAddress extends AddressFormValues {
	email: string;
}

export type CountryAddressFields = Record< string, FormFields >;

/**
 * Default field properties.
 */
export const defaultFields: FormFields =
	getSetting< FormFields >( 'defaultFields' );

export default defaultFields;
