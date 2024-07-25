/**
 * External dependencies
 */
import {
	AddressFormValues,
	ContactFormValues,
	KeyedFormField,
} from '@woocommerce/settings';
import { objectHasProp } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { AddressFormFields } from './types';

export interface FieldProps {
	id: string;
	errorId: string;
	label: string | undefined;
	autoCapitalize: string | undefined;
	autoComplete: string | undefined;
	errorMessage: string | undefined;
	required: boolean | undefined;
	placeholder: string | undefined;
	className: string;
}

export const createFieldProps = (
	field: KeyedFormField,
	formId: string,
	fieldAddressType: string
): FieldProps => ( {
	id: `${ formId }-${ field?.key }`.replaceAll( '/', '-' ), // Replace all slashes with hyphens to avoid invalid HTML ID.
	errorId: `${ fieldAddressType }_${ field?.key }`,
	label: field?.required ? field?.label : field?.optionalLabel,
	autoCapitalize: field?.autocapitalize,
	autoComplete: field?.autocomplete,
	errorMessage: field?.errorMessage,
	required: field?.required,
	placeholder: field?.placeholder,
	className: `wc-block-components-address-form__${ field?.key }`.replaceAll(
		'/',
		'-'
	), // Replace all slashes with hyphens to avoid invalid HTML classes.,
	...field?.attributes,
} );

export const createCheckboxFieldProps = ( fieldProps: FieldProps ) => {
	const {
		errorId,
		errorMessage,
		autoCapitalize,
		autoComplete,
		placeholder,
		...rest
	} = fieldProps;
	return rest;
};
export const getFieldData = < T extends AddressFormValues | ContactFormValues >(
	key: 'address_1' | 'address_2',
	fields: AddressFormFields[ 'fields' ],
	values: T
) => {
	const addressField = fields.find( ( field ) => field.key === key );
	const addressValue = objectHasProp( values, key )
		? values[ key ]
		: undefined;

	return { field: addressField, value: addressValue };
};
