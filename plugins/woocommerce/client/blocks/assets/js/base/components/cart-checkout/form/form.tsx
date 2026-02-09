/**
 * External dependencies
 */
import {
	BillingCountryInput,
	ShippingCountryInput,
} from '@woocommerce/base-components/country-input';
import {
	BillingStateInput,
	ShippingStateInput,
} from '@woocommerce/base-components/state-input';
import { useCheckoutAddress } from '@woocommerce/base-context';
import { usePrevious, useShallowEqual } from '@woocommerce/base-hooks';
import { validationStore } from '@woocommerce/block-data';
import {
	CheckboxControl,
	ValidatedCheckboxControl,
	ValidatedTextInput,
	type ValidatedTextInputHandle,
} from '@woocommerce/blocks-components';
import {
	AddressFormValues,
	ContactFormValues,
	OrderFormValues,
} from '@woocommerce/settings';
import { isNull } from '@woocommerce/types';
import { useInstanceId } from '@wordpress/compose';
import { dispatch, select } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import isShallowEqual from '@wordpress/is-shallow-equal';
import clsx from 'clsx';
import fastDeepEqual from 'fast-deep-equal/es6';

/**
 * Internal dependencies
 */
import { Select } from '../../select';
import AddressLineFields from './address-line-fields';
import { FormProps } from './types';
import { useFormFields } from './use-form-fields';
import { useFormValidation } from './use-form-validation';
import {
	createCheckboxFieldProps,
	createFieldProps,
	getFieldData,
} from './utils';
import validateCountry from './validate-country';
import { validateState } from './validate-state';
/**
 * Checkout form.
 */
const Form = <
	T extends AddressFormValues | ContactFormValues | OrderFormValues
>( {
	id = '',
	fields,
	onChange,
	addressType = 'shipping',
	values,
	children,
	isEditing,
	ariaDescribedBy = '',
}: FormProps< T > ): JSX.Element => {
	const instanceId = useInstanceId( Form );
	const isFirstRender = useRef( true );
	const { defaultFields } = useCheckoutAddress(); // We read from here because `useCheckoutAddress` can adapt to being in the editor or frontend.
	// Track incoming props.
	const currentFields = useShallowEqual( fields );
	const currentCountry = useShallowEqual< string >(
		'country' in values ? values.country : ''
	);

	// Prepare address form fields by combining fields from the locale and default fields.
	const formFields = useFormFields(
		currentFields,
		defaultFields,
		addressType,
		currentCountry
	);

	// Store previous fields to track changes.
	const previousFormFields = usePrevious( formFields );
	const previousIsEditing = usePrevious( isEditing );
	const previousValues = usePrevious( values );

	// Stores refs for rendered inputs so we can access them later.
	const inputsRef = useRef<
		Record< string, ValidatedTextInputHandle | null >
	>( {} );

	const { errors, previousErrors } = useFormValidation(
		formFields,
		addressType,
		// Temporary override for shipping calculator address form.
		addressType === 'shipping' ? ( values as AddressFormValues ) : undefined
	);

	useEffect( () => {
		Object.entries( errors ).forEach( ( [ key, error ] ) => {
			const inputRef = inputsRef.current[ key ];
			if ( ! error ) {
				return;
			}
			inputRef?.setErrorMessage( error );
			const hasValidationError = select(
				validationStore
			).getValidationError( `${ addressType }_${ key }` );

			// Check if this field already has a validation error, prevents up from surfacing already hidden errors.
			if ( hasValidationError ) {
				return;
			}
			dispatch( validationStore ).setValidationErrors( {
				[ `${ addressType }_${ key }` ]: {
					message: error,
					hidden: !! inputRef?.isFocused(),
				},
			} );
		} );

		// Previous errors are cleared when they're no longer present.
		if ( previousErrors ) {
			const errorsToClear: string[] = [];
			Object.entries( previousErrors ).forEach( ( [ key ] ) => {
				const inputRef = inputsRef.current[ key ];

				// If error was previously set but no longer exists, clear it.
				if ( ! ( key in errors ) ) {
					errorsToClear.push( `${ addressType }_${ key }` );
					inputRef?.setErrorMessage( '' );
				}
			} );
			if ( errorsToClear.length ) {
				dispatch( validationStore ).clearValidationErrors(
					errorsToClear
				);
			}
		}
	}, [ errors, previousErrors, addressType, values ] );

	// Changing country may change format for postcodes.
	useEffect( () => {
		inputsRef.current?.postcode?.revalidate();
	}, [ currentCountry ] );

	// Focus the first input when opening the form.
	useEffect( () => {
		let timeoutId: ReturnType< typeof setTimeout >;

		if (
			! isFirstRender.current &&
			isEditing &&
			inputsRef.current &&
			previousIsEditing !== isEditing
		) {
			const firstField = formFields.find(
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
	}, [
		isEditing,
		formFields,
		id,
		instanceId,
		addressType,
		previousIsEditing,
	] );

	// Clear values for hidden fields when fields change.
	useEffect( () => {
		if ( fastDeepEqual( previousFormFields, formFields ) ) {
			return;
		}
		const newValues = {
			...values,
			...Object.fromEntries(
				formFields
					.filter( ( field ) => field.hidden )
					.map( ( field ) => [ field.key, '' ] )
			),
		};
		if ( ! isShallowEqual( values, newValues ) ) {
			onChange( newValues );
		}
	}, [ onChange, formFields, previousFormFields, values ] );

	// Maybe validate country and state when other fields change so user is notified that they're required.
	useEffect( () => {
		if (
			fastDeepEqual( previousFormFields, formFields ) &&
			fastDeepEqual( previousValues, values )
		) {
			return;
		}
		if ( 'country' in values ) {
			validateCountry( addressType, values );
		}
		if ( 'state' in values ) {
			const stateField = formFields.find( ( f ) => f.key === 'state' );

			if ( stateField ) {
				validateState( addressType, values, stateField );
			}
		}
	}, [
		values,
		previousValues,
		addressType,
		formFields,
		previousFormFields,
	] );

	id = id || `${ instanceId }`;

	return (
		<div id={ id } className="wc-block-components-address-form">
			{ formFields.map( ( field ) => {
				if ( !! field.hidden ) {
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
					const value =
						field.key in values
							? values[ field.key as keyof T ]
							: false;
					const checkboxProps = {
						checked: Boolean( value ),
						onChange: ( checked: boolean ) => {
							onChange( {
								...values,
								[ field.key ]: checked,
							} );
						},
						...checkboxFieldProps,
					};
					if ( field.required ) {
						return (
							<ValidatedCheckboxControl
								key={ field.key }
								{ ...( field.errorMessage
									? { errorMessage: field.errorMessage }
									: {} ) }
								{ ...checkboxProps }
							/>
						);
					}

					return (
						<CheckboxControl
							key={ field.key }
							{ ...checkboxProps }
						/>
					);
				}

				// If the current field is 'address_1', we handle both 'address_1' and 'address_2' fields together.
				if ( field.key === 'address_1' && 'address_1' in values ) {
					const address1 = getFieldData< 'address_1' >(
						'address_1',
						formFields,
						values
					);
					const address2 = getFieldData< 'address_2' >(
						'address_2',
						formFields,
						values
					);

					if ( isNull( address1 ) || isNull( address2 ) ) {
						return null;
					}

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

				if ( field.key === 'country' && 'country' in values ) {
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
					'state' in values &&
					'country' in values
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

				if ( field.type === 'select' && 'options' in field ) {
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
							value={
								field.key in values
									? ( values[
											field.key as keyof T
									  ] as string )
									: ''
							}
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
							( inputsRef.current[ field.key ] = el )
						}
						{ ...fieldProps }
						type={ field.type }
						ariaDescribedBy={ ariaDescribedBy }
						value={
							decodeEntities(
								values[ field.key as keyof T ] as string
							) ?? ''
						}
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
					/>
				);
			} ) }
			{ children }
		</div>
	);
};

export default Form;
