/**
 * External dependencies
 */
import {
	ValidatedTextInput,
	isPostcode,
	type ValidatedTextInputHandle,
} from '@woocommerce/blocks-checkout';
import {
	BillingCountryInput,
	ShippingCountryInput,
} from '@woocommerce/base-components/country-input';
import {
	BillingStateInput,
	ShippingStateInput,
} from '@woocommerce/base-components/state-input';
import { useEffect, useMemo, useRef } from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';
import { useShallowEqual } from '@woocommerce/base-hooks';
import { defaultAddressFields } from '@woocommerce/settings';
import isShallowEqual from '@wordpress/is-shallow-equal';

/**
 * Internal dependencies
 */
import {
	AddressFormProps,
	FieldType,
	FieldConfig,
	AddressFormFields,
} from './types';
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
	onChange,
	type = 'shipping',
	values,
}: AddressFormProps ): JSX.Element => {
	const instanceId = useInstanceId( AddressForm );

	// Track incoming props.
	const currentFields = useShallowEqual( fields );
	const currentFieldConfig = useShallowEqual( fieldConfig );
	const currentCountry = useShallowEqual( values.country );

	// Memoize the address form fields passed in from the parent component.
	const addressFormFields = useMemo( (): AddressFormFields => {
		const preparedFields = prepareAddressFields(
			currentFields,
			currentFieldConfig,
			currentCountry
		);
		return {
			fields: preparedFields,
			type,
			required: preparedFields.filter( ( field ) => field.required ),
			hidden: preparedFields.filter( ( field ) => field.hidden ),
		};
	}, [ currentFields, currentFieldConfig, currentCountry, type ] );

	// Stores refs for rendered fields so we can access them later.
	const fieldsRef = useRef<
		Record< string, ValidatedTextInputHandle | null >
	>( {} );

	// Clear values for hidden fields.
	useEffect( () => {
		const newValues = {
			...values,
			...Object.fromEntries(
				addressFormFields.hidden.map( ( field ) => [ field.key, '' ] )
			),
		};
		if ( ! isShallowEqual( values, newValues ) ) {
			onChange( newValues );
		}
	}, [ onChange, addressFormFields, values ] );

	// Maybe validate country when other fields change so user is notified that it's required.
	useEffect( () => {
		if ( type === 'shipping' ) {
			validateShippingCountry( values );
		}
	}, [ values, type ] );

	// Changing country may change format for postcodes.
	useEffect( () => {
		fieldsRef.current?.postcode?.revalidate();
	}, [ currentCountry ] );

	id = id || `${ instanceId }`;

	return (
		<div id={ id } className="wc-block-components-address-form">
			{ addressFormFields.fields.map( ( field ) => {
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
							onChange={ ( newCountry ) => {
								const newValues = {
									...values,
									country: newCountry,
									state: '',
								};
								// Country will impact postcode too. Do we need to clear it?
								if (
									values.postcode &&
									! isPostcode( {
										postcode: values.postcode,
										country: newCountry,
									} )
								) {
									newValues.postcode = '';
								}
								onChange( newValues );
							} }
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
						ref={ ( el ) =>
							( fieldsRef.current[ field.key ] = el )
						}
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

export default AddressForm;
