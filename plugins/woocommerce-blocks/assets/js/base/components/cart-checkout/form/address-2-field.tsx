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
import { __, sprintf } from '@wordpress/i18n';

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

	const isFieldRequired = field ? field.required : false;

	const [ isFieldVisible, setFieldVisible ] = useState(
		() => hasFieldValue || isFieldRequired
	);

	const toggleFieldVisibility = useCallback(
		( event: React.MouseEvent< HTMLElement > ) => {
			event.preventDefault();
			setFieldVisible( ( prevVisibility: boolean ) => ! prevVisibility );
		},
		[]
	);

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
					{ sprintf(
						// translators: %s: address 2 field label.
						__( 'Add %s', 'woocommerce' ),
						field.label.toLowerCase()
					) }
				</button>
			) }
		</Fragment>
	);
};

export default Address2Field;
