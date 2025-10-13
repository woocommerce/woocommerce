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
	label: string;
	autoCapitalize: string | undefined;
	autoComplete: string | undefined;
	errorMessage?: string;
	required: boolean;
	placeholder: string | undefined;
	className: string;
}

export const createFieldProps = (
	field: KeyedParsedFormFields[ number ],
	formId: string,
	fieldAddressType: string
): FieldProps => {
	// Prefix autocomplete with billing/shipping for address fields
	let autoComplete = field?.autocomplete;
	if ( autoComplete && ( fieldAddressType === 'billing' || fieldAddressType === 'shipping' ) ) {
		// Fields that should have the billing/shipping prefix
		const fieldsToPrefix = [
			'organization',
			'address-line1',
			'address-line2',
			'address-line3',
			'postal-code',
			'tel',
		];
		if ( fieldsToPrefix.includes( autoComplete ) ) {
			autoComplete = `${ fieldAddressType } ${ autoComplete }`;
		}
	}

	return {
		id: `${ formId }-${ field?.key }`.replaceAll( '/', '-' ), // Replace all slashes with hyphens to avoid invalid HTML ID.
		errorId: `${ fieldAddressType }_${ field?.key }`,
		label: ( field?.required ? field?.label : field?.optionalLabel ) || '',
		autoCapitalize: field?.autocapitalize,
		autoComplete,
		errorMessage: field?.errorMessage || '',
		required: field?.required,
		placeholder: field?.placeholder,
		className: `wc-block-components-address-form__${ field?.key }`.replaceAll(
			'/',
			'-'
		), // Replace all slashes with hyphens to avoid invalid HTML classes.,
		...field?.attributes,
	};
};

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
