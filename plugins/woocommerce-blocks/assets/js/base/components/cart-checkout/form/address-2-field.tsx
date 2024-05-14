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

type Address2FieldProps = {
	address2: KeyedFormField | undefined;
	address2FieldProps: Record< string, undefined >;
	field: KeyedFormField;
	fieldsRef: React.MutableRefObject<
		Record< string, ValidatedTextInputHandle | null >
	>;
	onChange: ( values: AddressFormValues | ContactFormValues ) => void;
	values: AddressFormValues | ContactFormValues;
};

const Address2Field = ( {
	address2,
	address2FieldProps,
	field,
	fieldsRef,
	onChange,
	values,
}: Address2FieldProps ): JSX.Element => {
	const hasFieldValue = useMemo( () => {
		return objectHasProp( values, 'address_2' ) && values.address_2 !== '';
	}, [ values ] );

	const isFieldRequired = address2 ? address2.required : false;

	const [ isFieldVisible, setFieldVisible ] = useState(
		hasFieldValue || isFieldRequired
	);

	const toggleFieldVisibility = useCallback(
		( event: React.MouseEvent< HTMLElement > ) => {
			event.preventDefault();
			setFieldVisible( ( prevVisibility ) => ! prevVisibility );
		},
		[]
	);

	const [ hasFieldBeenModified, setHasFieldBeenModified ] = useState( false );

	// Toggle address 2 visibility.
	useEffect( () => {
		/**
		 * Determine if the address 2 input field should be shown based on the following conditions:
		 *
		 * 1. If the address 2 field is required.
		 * 2. If the address 2 field has a value.
		 * 3. If the seller has edited the address 2 field.
		 */
		const shouldShowField =
			isFieldRequired || hasFieldValue || hasFieldBeenModified;

		setFieldVisible( shouldShowField );
	}, [ isFieldRequired, hasFieldValue, hasFieldBeenModified ] );

	return (
		<Fragment key={ field.key }>
			{ isFieldVisible ? (
				<ValidatedTextInput
					{ ...address2FieldProps }
					key={ field.key }
					ref={ ( el ) => ( fieldsRef.current[ field.key ] = el ) }
					type={ field.type }
					label={
						isFieldRequired ? field.label : field.optionalLabel
					}
					className={ `wc-block-components-address-form__${ field.key }` }
					value={ values[ field.key ] }
					onChange={ ( newValue: string ) => {
						onChange( {
							...values,
							[ field.key ]: newValue,
						} );
						setHasFieldBeenModified( true );
					} }
				/>
			) : (
				<button
					key={ field.key }
					className={
						'wc-block-components-address-form__address_2-toggle'
					}
					onClick={ toggleFieldVisibility }
				>
					{ __( 'Add apartment, suite, etc.', 'woocommerce' ) }
				</button>
			) }
		</Fragment>
	);
};

export default Address2Field;
