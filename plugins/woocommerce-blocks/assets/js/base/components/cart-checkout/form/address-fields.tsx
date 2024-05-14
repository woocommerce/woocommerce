/**
 * External dependencies
 */
import {
	ValidatedTextInput,
	type ValidatedTextInputHandle,
} from '@woocommerce/blocks-components';
import {
	AddressFormValues,
	ContactFormValues,
	KeyedFormField,
} from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import Address2Field from './address-2-field';
import { AddressFormFields } from './types';

type AddressFieldsProps = {
	id: string;
	address1: KeyedFormField | undefined;
	address2: KeyedFormField | undefined;
	addressFormFields: AddressFormFields;
	addressType: string;
	fieldsRef: React.MutableRefObject<
		Record< string, ValidatedTextInputHandle | null >
	>;
	onChange: ( values: AddressFormValues | ContactFormValues ) => void;
	values: AddressFormValues | ContactFormValues;
};

const AddressFields = ( {
	id,
	address1,
	address2,
	addressFormFields,
	addressType,
	fieldsRef,
	onChange,
	values,
}: AddressFieldsProps ): JSX.Element => {
	const createFieldProps = (
		address: KeyedFormField | undefined,
		fieldId: string,
		fieldAddressType: string
	) => ( {
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

	const address1FieldProps = createFieldProps( address1, id, addressType );
	const address2FieldProps = createFieldProps( address2, id, addressType );

	return (
		<>
			{ address1 && (
				<ValidatedTextInput
					{ ...address1FieldProps }
					key={ address1.key }
					ref={ ( el ) => ( fieldsRef.current[ address1.key ] = el ) }
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
					address2={ address2 }
					key={ address2.key }
					addressFormFields={ addressFormFields }
					field={ address2 }
					fieldsRef={ fieldsRef }
					values={ values }
					onChange={ onChange }
					address2FieldProps={ address2FieldProps }
				/>
			) }
		</>
	);
};

export default AddressFields;
