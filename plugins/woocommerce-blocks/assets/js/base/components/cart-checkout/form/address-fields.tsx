/**
 * External dependencies
 */
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { AddressFormValues, ContactFormValues } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import Address2Field from './address-2-field';
import { AddressFieldsProps } from './types';
import { createFieldProps } from './utils';

const AddressFields = < T extends AddressFormValues | ContactFormValues >( {
	id,
	address1,
	address2,
	addressType,
	onChange,
}: AddressFieldsProps< T > ): JSX.Element => {
	const address1FieldProps = address1
		? createFieldProps( address1.fields, id, addressType )
		: undefined;
	const address2FieldProps = address2
		? createFieldProps( address2.fields, id, addressType )
		: undefined;

	return (
		<>
			{ address1 && (
				<ValidatedTextInput
					{ ...address1FieldProps }
					type={ address1.fields?.type }
					label={ address1.fields?.label }
					className={ `wc-block-components-address-form__${ address1.fields?.key }` }
					value={ address1.values }
					onChange={ ( newValue: string ) =>
						onChange( address1.fields?.key as keyof T, newValue )
					}
				/>
			) }
			{ address2?.fields && ! address2?.fields?.hidden && (
				<Address2Field
					fields={ address2.fields }
					props={ address2FieldProps }
					onChange={ onChange }
					value={ address2?.values }
				/>
			) }
		</>
	);
};

export default AddressFields;
