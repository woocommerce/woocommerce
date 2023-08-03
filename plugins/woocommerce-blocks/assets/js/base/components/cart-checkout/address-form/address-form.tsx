/**
 * External dependencies
 */
import { ValidatedTextInput } from '@woocommerce/blocks-checkout';
import {
	BillingCountryInput,
	ShippingCountryInput,
} from '@woocommerce/base-components/country-input';
import {
	BillingStateInput,
	ShippingStateInput,
} from '@woocommerce/base-components/state-input';
import { useEffect, useMemo } from '@wordpress/element';
import { withInstanceId } from '@wordpress/compose';
import { useShallowEqual } from '@woocommerce/base-hooks';
import { defaultAddressFields } from '@woocommerce/settings';
import { useDispatch, dispatch } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { AddressFormProps, FieldType, FieldConfig } from './types';
import prepareAddressFields from './prepare-address-fields';
import validateShippingCountry from './validate-shipping-country';
import customValidationHandler from './custom-validation-handler';

const defaultFields = Object.keys(
	defaultAddressFields
) as unknown as FieldType[];

/**
 * Checkout address form.
 */
const AddressForm = ( {
	id = '',
	fields = defaultFields,
	fieldConfig = {} as FieldConfig,
	instanceId,
	onChange,
	type = 'shipping',
	values,
}: AddressFormProps ): JSX.Element => {
	const { clearValidationError } = useDispatch( VALIDATION_STORE_KEY );

	const currentFields = useShallowEqual( fields );

	const addressFormFields = useMemo( () => {
		return prepareAddressFields(
			currentFields,
			fieldConfig,
			values.country
		);
	}, [ currentFields, fieldConfig, values.country ] );

	// Clear values for hidden fields.
	useEffect( () => {
		addressFormFields.forEach( ( field ) => {
			if ( field.hidden && values[ field.key ] ) {
				onChange( {
					...values,
					[ field.key ]: '',
				} );
			}
		} );
	}, [ addressFormFields, onChange, values ] );

	// Clear postcode validation error if postcode is not required.
	useEffect( () => {
		addressFormFields.forEach( ( field ) => {
			if ( field.key === 'postcode' && field.required === false ) {
				const store = dispatch( 'wc/store/validation' );

				if ( type === 'shipping' ) {
					store.clearValidationError( 'shipping_postcode' );
				}

				if ( type === 'billing' ) {
					store.clearValidationError( 'billing_postcode' );
				}
			}
		} );
	}, [ addressFormFields, type, clearValidationError ] );

	// Maybe validate country when other fields change so user is notified that it's required.
	useEffect( () => {
		if ( type === 'shipping' ) {
			validateShippingCountry( values );
		}
	}, [ values, type ] );

	id = id || instanceId;

	return (
		<div id={ id } className="wc-block-components-address-form">
			{ addressFormFields.map( ( field ) => {
				if ( field.hidden ) {
					return null;
				}

				const fieldProps = {
					id: `${ id }-${ field.key }`,
					errorId: `${ type }_${ field.key }`,
					label: field.required ? field.label : field.optionalLabel,
					autoCapitalize: field.autocapitalize,
					autoComplete: field.autocomplete,
					errorMessage: field.errorMessage,
					required: field.required,
					className: `wc-block-components-address-form__${ field.key }`,
				};

				if ( field.key === 'country' ) {
					const Tag =
						type === 'shipping'
							? ShippingCountryInput
							: BillingCountryInput;
					return (
						<Tag
							key={ field.key }
							{ ...fieldProps }
							value={ values.country }
							onChange={ ( newValue ) =>
								onChange( {
									...values,
									country: newValue,
									state: '',
								} )
							}
						/>
					);
				}

				if ( field.key === 'state' ) {
					const Tag =
						type === 'shipping'
							? ShippingStateInput
							: BillingStateInput;
					return (
						<Tag
							key={ field.key }
							{ ...fieldProps }
							country={ values.country }
							value={ values.state }
							onChange={ ( newValue ) =>
								onChange( {
									...values,
									state: newValue,
								} )
							}
						/>
					);
				}

				return (
					<ValidatedTextInput
						key={ field.key }
						{ ...fieldProps }
						value={ values[ field.key ] }
						onChange={ ( newValue: string ) =>
							onChange( {
								...values,
								[ field.key ]: newValue,
							} )
						}
						customFormatter={ ( value: string ) => {
							if ( field.key === 'postcode' ) {
								return value.trimStart().toUpperCase();
							}
							return value;
						} }
						customValidation={ ( inputObject: HTMLInputElement ) =>
							customValidationHandler(
								inputObject,
								field.key,
								values
							)
						}
					/>
				);
			} ) }
		</div>
	);
};

export default withInstanceId( AddressForm );
