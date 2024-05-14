/**
 * External dependencies
 */
import { KeyedFormField } from '@woocommerce/settings';

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
