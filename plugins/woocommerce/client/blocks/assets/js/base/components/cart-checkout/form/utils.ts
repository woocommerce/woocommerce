/**
 * External dependencies
 */
import { DocumentObject } from '@woocommerce/base-hooks';
import {
	AddressForm,
	AddressFormValues,
	Field,
	KeyedParsedFormFields,
} from '@woocommerce/settings';
import { isObject, objectHasProp } from '@woocommerce/types';
import type { JSONSchemaType } from 'ajv';

export interface FieldProps {
	id: string;
	errorId: string;
	name: string;
	label: string;
	autoCapitalize: string | undefined;
	autoComplete: string | undefined;
	errorMessage?: string;
	required: boolean;
	placeholder: string | undefined;
	className: string;
}

const SECTIONED_ADDRESS_TYPES = [ 'billing', 'shipping' ];

/**
 * Build the `autocomplete` attribute value for a checkout field.
 *
 * Only `shipping` and `billing` are valid in the address type slot, and
 * `on`/`off` cannot be combined with any other token. Browsers drop a value
 * they cannot parse and guess instead, so anything else is passed through bare.
 *
 * @see https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#autofill
 *
 * @param autocomplete     Autofill field name from the field config.
 * @param fieldAddressType Address type of the form the field belongs to.
 * @return The attribute value, or undefined when the field has no hint.
 */
export const getAutoCompleteValue = (
	autocomplete: string | undefined,
	fieldAddressType: string
): string | undefined => {
	// Registered field config reaches us from PHP unvalidated, so neither
	// argument is guaranteed to be the string its type claims.
	if ( typeof autocomplete !== 'string' ) {
		return undefined;
	}

	const value = autocomplete.trim();

	if ( ! value ) {
		return undefined;
	}

	const lowerCaseValue = value.toLowerCase();

	if ( lowerCaseValue === 'on' || lowerCaseValue === 'off' ) {
		return value;
	}

	const addressType =
		typeof fieldAddressType === 'string'
			? fieldAddressType.trim().toLowerCase()
			: '';

	if ( ! SECTIONED_ADDRESS_TYPES.includes( addressType ) ) {
		return value;
	}

	return `section-${ addressType } ${ addressType } ${ value }`;
};

export const createFieldProps = (
	field: KeyedParsedFormFields[ number ],
	formId: string,
	fieldAddressType: string
): FieldProps => ( {
	id: `${ formId }-${ field?.key }`.replaceAll( '/', '-' ), // Replace all slashes with hyphens to avoid invalid HTML ID.
	errorId: `${ fieldAddressType }_${ field?.key }`,
	name: `${ fieldAddressType }_${ field?.key }`,
	label: ( field?.required ? field?.label : field?.optionalLabel ) || '',
	autoCapitalize: field?.autocapitalize,
	autoComplete: getAutoCompleteValue( field?.autocomplete, fieldAddressType ),
	errorMessage: field?.errorMessage || '',
	required: field?.required,
	placeholder: field?.placeholder,
	className: `wc-block-components-address-form__${ field?.key }`.replaceAll(
		'/',
		'-'
	), // Replace all slashes with hyphens to avoid invalid HTML classes.,
	...field?.attributes,
} );

export const createCheckboxFieldProps = ( fieldProps: FieldProps ) => {
	const { autoCapitalize, autoComplete, placeholder, ...rest } = fieldProps;
	return rest;
};
export const getFieldData = < T extends keyof AddressForm >(
	key: T,
	fields: KeyedParsedFormFields,
	values: AddressFormValues
): {
	field: AddressForm[ typeof key ] & {
		key: typeof key;
		errorMessage?: string;
	};
	value: string;
} | null => {
	const addressField = fields.find( ( _field ) => _field.key === key );
	const addressValue = objectHasProp( values, key ) ? values[ key ] : '';
	if ( ! addressField ) {
		return null;
	}

	return {
		field: { ...addressField, key }, // TS won't infer the key type correctly.
		value: addressValue,
	};
};

export const hasSchemaRules = (
	field: Field,
	key: 'required' | 'hidden' | 'validation'
): field is Field & {
	[ K in typeof key ]: JSONSchemaType< DocumentObject< 'global' > >;
} => {
	return isObject( field[ key ] ) && Object.keys( field[ key ] ).length > 0;
};
