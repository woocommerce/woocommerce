/**
 * External dependencies
 */
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { AddressFormValues, ContactFormValues } from '@woocommerce/settings';
import { useEffect, useState, Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { AddressFieldProps } from './types';

const Address2Field = < T extends AddressFormValues | ContactFormValues >( {
	field,
	props,
	onChange,
	value,
}: AddressFieldProps< T > ): JSX.Element => {
	const isFieldRequired = field?.required ?? false;

	// Display the input field if it has a value or if it is required.
	const [ isFieldVisible, setFieldVisible ] = useState(
		() => Boolean( value ) || isFieldRequired
	);

	return (
		<Fragment>
			{ isFieldVisible ? (
				<ValidatedTextInput
					{ ...props }
					type={ field.type }
					label={
						isFieldRequired ? field.label : field.optionalLabel
					}
					className={ `wc-block-components-address-form__${ field.key }` }
					value={ value }
					onChange={ ( newValue: string ) =>
						onChange( field.key as keyof T, newValue )
					}
				/>
			) : (
				<button
					className={
						'wc-block-components-address-form__address_2-toggle'
					}
					onClick={ () => setFieldVisible( true ) }
				>
					{ sprintf(
						// translators: %s: address 2 field label.
						__( '+ Add %s', 'woocommerce' ),
						field.label.toLowerCase()
					) }
				</button>
			) }
		</Fragment>
	);
};

export default Address2Field;
