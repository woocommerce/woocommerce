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
		? createFieldProps( address1.field, id, addressType )
		: undefined;
	const address2FieldProps = address2
		? createFieldProps( address2.field, id, addressType )
		: undefined;

	return (
		<>
			{ address1 && (
				<ValidatedTextInput
					{ ...address1FieldProps }
					type={ address1.field?.type }
					label={ address1.field?.label }
					className={ `wc-block-components-address-form__${ address1.field?.key }` }
					value={ address1.value }
					onChange={ ( newValue: string ) =>
						onChange( address1.field?.key as keyof T, newValue )
					}
				/>
			) }
			{ address2?.field && ! address2?.field?.hidden && (
				<Address2Field
					field={ address2.field }
					props={ address2FieldProps }
					onChange={ onChange }
					value={ address2?.value }
				/>
			) }
		</>
	);
};

export default AddressFields;
