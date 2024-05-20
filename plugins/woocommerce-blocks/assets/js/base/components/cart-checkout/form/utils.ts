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
	className: string;
}

export const createFieldProps = (
	address: KeyedFormField | undefined,
	fieldId: string,
	fieldAddressType: string
): FieldProps => ( {
	id: `${ fieldId }-${ address?.key }`,
	errorId: `${ fieldAddressType }_${ address?.key }`,
	label: address?.required ? address?.label : address?.optionalLabel,
	autoCapitalize: address?.autocapitalize,
	autoComplete: address?.autocomplete,
	errorMessage: address?.errorMessage,
	required: address?.required,
	className: `wc-block-components-address-form__${ address?.key }`,
	...address?.attributes,
} );

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
