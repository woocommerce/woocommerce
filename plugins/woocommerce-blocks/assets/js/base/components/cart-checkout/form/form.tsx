/**
 * External dependencies
 */
import {
	ValidatedTextInput,
	type ValidatedTextInputHandle,
	CheckboxControl,
} from '@woocommerce/blocks-components';
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
import isShallowEqual from '@wordpress/is-shallow-equal';
import clsx from 'clsx';
import {
	AddressFormValues,
	ContactFormValues,
	FormFieldsConfig,
} from '@woocommerce/settings';
import { objectHasProp } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { AddressFormProps, AddressFormFields } from './types';
import prepareFormFields from './prepare-form-fields';
import validateCountry from './validate-country';
import customValidationHandler from './custom-validation-handler';
import AddressLineFields from './address-line-fields';
import {
	createFieldProps,
	createCheckboxFieldProps,
	getFieldData,
} from './utils';
import { Select } from '../../select';
import { validateState } from './validate-state';

/**
 * Checkout form.
 */
const Form = < T extends AddressFormValues | ContactFormValues >( {
	id = '',
	fields,
	fieldConfig = {} as FormFieldsConfig,
	onChange,
	addressType = 'shipping',
	values,
	children,
	isEditing,
}: AddressFormProps< T > ): JSX.Element => {
	const instanceId = useInstanceId( Form );
	const isFirstRender = useRef( true );

	// Track incoming props.
	const currentFields = useShallowEqual( fields );
	const currentFieldConfig = useShallowEqual( fieldConfig );
	const currentCountry = useShallowEqual(
		objectHasProp( values, 'country' ) ? values.country : ''
	);

	// Memoize the address form fields passed in from the parent component.
	const addressFormFields = useMemo( (): AddressFormFields => {
		const preparedFields = prepareFormFields(
			currentFields,
			currentFieldConfig,
			currentCountry
		);
		return {
			fields: preparedFields,
			addressType,
			required: preparedFields.filter( ( field ) => field.required ),
			hidden: preparedFields.filter( ( field ) => field.hidden ),
		};
	}, [ currentFields, currentFieldConfig, currentCountry, addressType ] );

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

	// Maybe validate country and state when other fields change so user is notified that they're required.
	useEffect( () => {
		if ( objectHasProp( values, 'country' ) ) {
			validateCountry( addressType, values );
		}

		if ( objectHasProp( values, 'state' ) ) {
			const stateField = addressFormFields.fields.find(
				( f ) => f.key === 'state'
			);

			if ( stateField ) {
				validateState( addressType, values, stateField );
			}
		}
	}, [ values, addressType, addressFormFields ] );

	// Changing country may change format for postcodes.
	useEffect( () => {
		fieldsRef.current?.postcode?.revalidate();
	}, [ currentCountry ] );

	// Focus the first input when opening the form.
	useEffect( () => {
		let timeoutId: ReturnType< typeof setTimeout >;

		if ( ! isFirstRender.current && isEditing && fieldsRef.current ) {
			const firstField = addressFormFields.fields.find(
				( field ) => field.hidden === false
			);

			if ( ! firstField ) {
				return;
			}

			const { id: firstFieldId } = createFieldProps(
				firstField,
				id || `${ instanceId }`,
				addressType
			);
			const firstFieldEl = document.getElementById( firstFieldId );

			if ( firstFieldEl ) {
				// Focus the first field after a short delay to ensure the form is rendered.
				timeoutId = setTimeout( () => {
					firstFieldEl.focus();
				}, 300 );
			}
		}

		isFirstRender.current = false;

		return () => {
			clearTimeout( timeoutId );
		};
	}, [ isEditing, addressFormFields, id, instanceId, addressType ] );

	id = id || `${ instanceId }`;

	return (
		<div id={ id } className="wc-block-components-address-form">
			{ addressFormFields.fields.map( ( field ) => {
				if ( field.hidden ) {
					return null;
				}

				const fieldProps = createFieldProps( field, id, addressType );
				const checkboxFieldProps =
					createCheckboxFieldProps( fieldProps );

				if ( field.key === 'email' ) {
					fieldProps.id = 'email';
					fieldProps.errorId = 'billing_email';
				}

				if ( field.type === 'checkbox' ) {
					return (
						<CheckboxControl
							key={ field.key }
							checked={ Boolean( values[ field.key ] ) }
							onChange={ ( checked: boolean ) => {
								onChange( {
									...values,
									[ field.key ]: checked,
								} );
							} }
							{ ...checkboxFieldProps }
						/>
					);
				}

				// If the current field is 'address_1', we handle both 'address_1' and 'address_2' fields together.
				if ( field.key === 'address_1' ) {
					const address1 = getFieldData(
						'address_1',
						addressFormFields.fields,
						values
					);
					const address2 = getFieldData(
						'address_2',
						addressFormFields.fields,
						values
					);

					return (
						<AddressLineFields
							address1={ address1 }
							address2={ address2 }
							addressType={ addressType }
							formId={ id }
							key={ field.key }
							onChange={ ( key, value ) => {
								onChange( {
									...values,
									[ key ]: value,
								} );
							} }
						/>
					);
				}

				// If the current field is 'address_2', we skip it because it's already handled above.
				if ( field.key === 'address_2' ) {
					return null;
				}

				if (
					field.key === 'country' &&
					objectHasProp( values, 'country' )
				) {
					const Tag =
						addressType === 'shipping'
							? ShippingCountryInput
							: BillingCountryInput;
					return (
						<Tag
							key={ field.key }
							{ ...fieldProps }
							value={ values.country }
							onChange={ ( newCountry ) => {
								onChange( {
									...values,
									country: newCountry,
									state: '',
									postcode: '',
								} );
							} }
						/>
					);
				}

				if (
					field.key === 'state' &&
					objectHasProp( values, 'state' )
				) {
					const Tag =
						addressType === 'shipping'
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

				if ( field.type === 'select' ) {
					if ( typeof field.options === 'undefined' ) {
						return null;
					}

					return (
						<Select
							key={ field.key }
							{ ...fieldProps }
							label={ fieldProps.label || '' }
							className={ clsx(
								'wc-block-components-select-input',
								`wc-block-components-select-input-${ field.key }`.replaceAll(
									'/',
									'-'
								)
							) }
							value={ values[ field.key ] }
							onChange={ ( newValue: string ) => {
								onChange( {
									...values,
									[ field.key ]: newValue,
								} );
							} }
							options={ field.options }
							required={ field.required }
							errorMessage={
								fieldProps.errorMessage || undefined
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
						type={ field.type }
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
								objectHasProp( values, 'country' )
									? values.country
									: ''
							)
						}
					/>
				);
			} ) }
			{ children }
		</div>
	);
};

export default Form;
