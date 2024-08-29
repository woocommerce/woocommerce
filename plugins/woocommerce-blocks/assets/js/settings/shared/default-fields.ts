/**
 * External dependencies
 */
import { ComboboxControlOption } from '@woocommerce/base-components/combobox';
import type { AllHTMLAttributes, AriaAttributes } from 'react';

/**
 * Internal dependencies
 */
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

export interface FormField {
	// The label for the field.
	label: string;
	// The label for the field if made optional.
	optionalLabel: string;
	// The HTML autocomplete attribute value. See https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/autocomplete
	autocomplete: string;
	// How this field value is capitalized.
	autocapitalize?: string;
	// Set to true if the field is required.
	required: boolean;
	// Set to true if the field should not be rendered.
	hidden: boolean;
	// Fields will be sorted and render in this order, lowest to highest.
	index: number;
	// The type of input to render. Defaults to text.
	type?: string;
	// The options if this is a select field
	options?: ComboboxControlOption[];
	// The placeholder for the field, only applicable for select fields.
	placeholder?: string;
	// Additional attributes added when registering a field. String in key is required for data attributes.
	attributes?: Record< keyof CustomFieldAttributes, string >;
}

export interface LocaleSpecificFormField extends Partial< FormField > {
	priority?: number | undefined;
}

export interface CoreAddressForm {
	first_name: FormField;
	last_name: FormField;
	company: FormField;
	address_1: FormField;
	address_2: FormField;
	country: FormField;
	city: FormField;
	state: FormField;
	postcode: FormField;
	phone: FormField;
}

export interface CoreContactForm {
	email: FormField;
}

export type AddressForm = CoreAddressForm & Record< string, FormField >;
export type ContactForm = CoreContactForm & Record< string, FormField >;
export type FormFields = AddressForm & ContactForm;
export type AddressFormValues = Omit< ShippingAddress, 'email' >;
export type ContactFormValues = { email: string };
export type AdditionalInformationFormValues = Record< string, string >;
export type FormType =
	| 'billing'
	| 'shipping'
	| 'contact'
	| 'additional-information';

export interface CoreAddress {
	first_name: string;
	last_name: string;
	company: string;
	address_1: string;
	address_2: string;
	country: string;
	city: string;
	state: string;
	postcode: string;
	phone: string;
}

export type AdditionalValues = Record<
	Exclude< string, keyof CoreAddress >,
	string | boolean
>;

export type ShippingAddress = CoreAddress;
export interface BillingAddress extends ShippingAddress {
	email: string;
}

export type KeyedFormField = FormField & {
	key: keyof FormFields;
	errorMessage?: string;
};

export type CountryAddressForm = Record< string, FormFields >;

export type FormFieldsConfig = Record< keyof FormFields, Partial< FormField > >;

/**
 * Default field properties.
 */
export const defaultFields: FormFields =
	getSetting< FormFields >( 'defaultFields' );

export default defaultFields;
