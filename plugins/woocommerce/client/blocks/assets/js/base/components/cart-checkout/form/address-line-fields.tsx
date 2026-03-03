/**
 * External dependencies
 */
import { getSettingWithCoercion } from '@woocommerce/settings';
import { ServerAddressAutocompleteProvider } from '@woocommerce/type-defs/address-autocomplete';
import { ValidatedTextInput } from '@woocommerce/blocks-components';

/**
 * Internal dependencies
 */
import AddressLine2Field from './address-line-2-field';
import { AddressLineFieldsProps } from './types';
import { createFieldProps } from './utils';
import { AddressAutocomplete } from '../address-autocomplete/address-autocomplete';

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

	const serverProviders = getSettingWithCoercion<
		ServerAddressAutocompleteProvider[]
	>(
		'addressAutocompleteProviders',
		[],
		( type: unknown ): type is ServerAddressAutocompleteProvider[] => {
			if ( ! Array.isArray( type ) ) {
				return false;
			}

			return type.every( ( item ) => {
				return (
					typeof item.name === 'string' &&
					typeof item.id === 'string' &&
					typeof item.branding_html === 'string'
				);
			} );
		}
	);

	const Address1Component =
		serverProviders.length > 0 ? AddressAutocomplete : ValidatedTextInput;

	return (
		<>
			{ address1 && (
				<Address1Component
					{ ...address1FieldProps }
					type={ address1.field.type }
					{ ...( serverProviders.length > 0 ? { addressType } : {} ) }
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
