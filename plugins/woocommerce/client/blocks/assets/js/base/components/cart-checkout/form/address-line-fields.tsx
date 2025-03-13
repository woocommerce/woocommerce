/**
 * External dependencies
 */
import { ValidatedTextInput } from '@woocommerce/blocks-components';

/**
 * Internal dependencies
 */
import AddressLine2Field from './address-line-2-field';
import { AddressLineFieldsProps } from './types';
import { createFieldProps } from './utils';

const AddressLineFields = ( {
	formId,
	address1,
	address2,
	addressType,
	onChange,
}: AddressLineFieldsProps ): JSX.Element => {
	const address1FieldProps = createFieldProps(
		address1.field,
		formId,
		addressType
	);
	const address2FieldProps = createFieldProps(
		address2.field,
		formId,
		addressType
	);

	return (
		<>
			{ address1 && (
				<ValidatedTextInput
					{ ...address1FieldProps }
					type={ address1.field.type }
					label={ address1.field.label }
					className={ `wc-block-components-address-form__address_1` }
					value={ address1.value }
					onChange={ ( newValue: string ) =>
						onChange( 'address_1', newValue )
					}
				/>
			) }
			{ address2.field && ! address2.field.hidden && (
				<AddressLine2Field
					field={ address2.field }
					props={ address2FieldProps }
					onChange={ ( newValue: string ) =>
						onChange( 'address_2', newValue )
					}
					value={ address2.value }
				/>
			) }
		</>
	);
};

export default AddressLineFields;
