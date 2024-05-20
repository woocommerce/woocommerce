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
	values,
}: AddressFieldsProps< T > ): JSX.Element => {
	const address1FieldProps = createFieldProps( address1, id, addressType );
	const address2FieldProps = createFieldProps( address2, id, addressType );

	return (
		<>
			{ address1 && (
				<ValidatedTextInput
					{ ...address1FieldProps }
					type={ address1.type }
					label={ address1.label }
					className={ `wc-block-components-address-form__${ address1.key }` }
					value={ values[ address1.key ] }
					onChange={ ( newValue: string ) =>
						onChange( {
							...values,
							[ address1.key ]: newValue,
						} )
					}
				/>
			) }
			{ address2 && ! address2.hidden && (
				<Address2Field
					field={ address2 }
					fieldProps={ address2FieldProps }
					onChange={ onChange }
					values={ values }
				/>
			) }
		</>
	);
};

export default AddressFields;
