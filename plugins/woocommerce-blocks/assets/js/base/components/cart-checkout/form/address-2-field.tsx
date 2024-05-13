/**
 * External dependencies
 */
import {
	ValidatedTextInput,
	type ValidatedTextInputHandle,
} from '@woocommerce/blocks-components';
import {
	useCallback,
	useEffect,
	useMemo,
	useState,
	Fragment,
} from '@wordpress/element';
import {
	AddressFormValues,
	ContactFormValues,
	KeyedFormField,
} from '@woocommerce/settings';
import { objectHasProp } from '@woocommerce/types';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { AddressFormFields } from './types';
import customValidationHandler from './custom-validation-handler';

const Address2Field = ( {
	addressFormFields,
	field,
	fieldProps,
	fieldsRef,
	onChange,
	values,
}: {
	addressFormFields: AddressFormFields;
	field: KeyedFormField;
	fieldProps: Record< string, undefined >;
	fieldsRef: React.MutableRefObject<
		Record< string, ValidatedTextInputHandle | null >
	>;
	onChange: ( values: AddressFormValues | ContactFormValues ) => void;
	values: AddressFormValues | ContactFormValues;
} ): JSX.Element => {
	const address2Field = addressFormFields.fields.find(
		( customField ) => customField.key === 'address_2'
	);

	const hasAddress2FieldValue = useMemo( () => {
		return objectHasProp( values, 'address_2' ) && values.address_2 !== '';
	}, [ values ] );

	const isAddress2FieldRequired = address2Field
		? address2Field.required
		: false;

	const [ isAddress2FieldVisible, setIsAddress2FieldVisible ] = useState(
		hasAddress2FieldValue || isAddress2FieldRequired
	);

	const toggleAddress2FieldVisibility = useCallback(
		( event: React.MouseEvent< HTMLElement > ) => {
			event.preventDefault();
			setIsAddress2FieldVisible( ( prevVisibility ) => ! prevVisibility );
		},
		[]
	);

	const [ userChangedAddress2Field, setUserChangedAddress2Field ] =
		useState( false );

	// Toggle address 2 visibility.
	useEffect( () => {
		setIsAddress2FieldVisible(
			isAddress2FieldRequired ||
				hasAddress2FieldValue ||
				userChangedAddress2Field
		);
	}, [
		isAddress2FieldRequired,
		hasAddress2FieldValue,
		userChangedAddress2Field,
	] );

	return (
		<Fragment key={ field.key }>
			{ isAddress2FieldVisible ? (
				<ValidatedTextInput
					key={ field.key }
					ref={ ( el ) => ( fieldsRef.current[ field.key ] = el ) }
					{ ...fieldProps }
					type={ field.type }
					label={
						isAddress2FieldRequired
							? field.label
							: field.optionalLabel
					}
					value={ values[ field.key ] }
					onChange={ ( newValue: string ) => {
						onChange( {
							...values,
							[ field.key ]: newValue,
						} );
						setUserChangedAddress2Field( true );
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
			) : (
				<button
					key={ field.key }
					className={
						'wc-block-components-address-form__address_2-toggle'
					}
					onClick={ toggleAddress2FieldVisibility }
				>
					{ __( '+ Add apartment, suite, etc.', 'woocommerce' ) }
				</button>
			) }
		</Fragment>
	);
};

export default Address2Field;
