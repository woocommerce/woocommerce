/**
 * External dependencies
 */
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { AddressFormValues, ContactFormValues } from '@woocommerce/settings';
import {
	useCallback,
	useEffect,
	useMemo,
	useState,
	Fragment,
} from '@wordpress/element';
import { objectHasProp } from '@woocommerce/types';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { AddressFieldProps } from './types';

const Address2Field = < T extends AddressFormValues | ContactFormValues >( {
	field,
	fieldProps,
	fieldsRef,
	onChange,
	values,
}: AddressFieldProps< T > ): JSX.Element => {
	const hasFieldValue = useMemo( () => {
		return objectHasProp( values, 'address_2' ) && values.address_2 !== '';
	}, [ values ] );

	const [ hasFieldBeenModified, setHasFieldBeenModified ] = useState( false );

	const isFieldRequired = field ? field.required : false;

	const [ isFieldVisible, setFieldVisible ] = useState(
		hasFieldValue || isFieldRequired
	);

	const toggleFieldVisibility = useCallback(
		( event: React.MouseEvent< HTMLElement > ) => {
			event.preventDefault();
			setFieldVisible( ( prevVisibility: boolean ) => ! prevVisibility );
		},
		[]
	);

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
					{ ...fieldProps }
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
