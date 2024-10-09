/**
 * External dependencies
 */
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { AddressFormValues, ContactFormValues } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import AddressLine2Field from './address-line-2-field';
import { AddressLineFieldsProps } from './types';
import { createFieldProps } from './utils';
import { getAddressSuggestionProvider } from '../../../../blocks-registry/autocomplete-providers';

const AddressLineFields = < T extends AddressFormValues | ContactFormValues >( {
	formId,
	address1,
	address2,
	addressType,
	onChange,
	suggestions,
}: AddressLineFieldsProps< T > ): JSX.Element => {
	const addressProvider = getAddressSuggestionProvider();

	const address1FieldProps = address1
		? createFieldProps( address1.field, formId, addressType )
		: undefined;
	const address2FieldProps = address2
		? createFieldProps( address2.field, formId, addressType )
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
			{ suggestions.length > 0 && (
				<div className="wc-block-components-address-form__suggestions">
					{ suggestions.map( ( suggestion ) => (
						<button
							key={ suggestion.label }
							onClick={ () => {
								const address =
									addressProvider?.getAddress( suggestion );
								console.log( address );
							} }
						>
							{ suggestion.label }
						</button>
					) ) }
				</div>
			) }
			{ address2?.field && ! address2?.field?.hidden && (
				<AddressLine2Field
					field={ address2.field }
					props={ address2FieldProps }
					onChange={ onChange }
					value={ address2?.value }
				/>
			) }
		</>
	);
};

export default AddressLineFields;
