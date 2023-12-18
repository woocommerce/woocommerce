/**
 * External dependencies
 */
import { ComboboxControlOption } from '@woocommerce/base-components/combobox';

/**
 * Internal dependencies
 */
import { getSetting } from './utils';

export interface AddressField {
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
}

export interface LocaleSpecificAddressField extends Partial< AddressField > {
	priority?: number | undefined;
}

export interface CoreAddressFields {
	first_name: AddressField;
	last_name: AddressField;
	company: AddressField;
	address_1: AddressField;
	address_2: AddressField;
	country: AddressField;
	city: AddressField;
	state: AddressField;
	postcode: AddressField;
	phone: AddressField;
}

export type AddressFields = CoreAddressFields & Record< string, AddressField >;

export type AddressType = 'billing' | 'shipping';

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

export type ShippingAddress = CoreAddress & Record< string, string | boolean >;

export type KeyedAddressField = AddressField & {
	key: keyof AddressFields;
	errorMessage?: string;
};
export interface BillingAddress extends ShippingAddress {
	email: string;
}
export type CountryAddressFields = Record< string, AddressFields >;

/**
 * Default field properties.
 */
export const defaultFields: AddressFields =
	getSetting< AddressFields >( 'defaultFields' );

export default defaultFields;
